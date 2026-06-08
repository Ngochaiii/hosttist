<?php

namespace App\Services;

use App\Models\CustomerService;
use App\Models\Domain;
use App\Models\DomainTld;
use App\Models\ServiceProvision;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Kích hoạt tên miền khi admin hoàn tất provision (đơn khách đặt).
 *
 * Domain có vòng đời riêng so với dịch vụ thường:
 *  - Hạn tính theo NĂM (registered + years), hoặc theo expiry_date admin nhập.
 *  - Giá gia hạn = renew_price của đuôi (KHÁC giá đăng ký).
 * Vì vậy tách khỏi ServiceLifecycleService::activateFromProvision (vốn tính hạn
 * theo billing cycle của product). Hook gọi từ activateFromProvision khi phát hiện domain.
 */
class DomainProvisioningService extends BaseService
{
    /** Provision này có phải đăng ký tên miền không? */
    public function isDomainProvision(ServiceProvision $provision, array $pdata): bool
    {
        return $provision->provision_type === 'domain'
            || ($pdata['service_type'] ?? null) === 'domain'
            || !empty($pdata['tld_id']);
    }

    /**
     * Tạo CustomerService (lifecycle) + bản ghi Domain (tài sản) khi provision hoàn tất.
     * @return CustomerService  để markProvisionCompleted tiếp tục flow chung.
     */
    public function activate(ServiceProvision $provision, array $pdata): CustomerService
    {
        return $this->transaction(function () use ($provision, $pdata) {
            $years      = max(1, (int) ($pdata['years'] ?? $pdata['period'] ?? 1));
            $domainName = strtolower(trim($pdata['domain_name'] ?? $pdata['domain'] ?? ''));

            $registeredAt = now();
            $expiresAt    = !empty($pdata['expiry_date'])
                ? Carbon::parse($pdata['expiry_date'])
                : $registeredAt->copy()->addYears($years);

            $tld  = !empty($pdata['tld_id']) ? DomainTld::find($pdata['tld_id']) : null;
            $cost = (float) ($pdata['cost_price'] ?? 0);
            $sell = (float) ($pdata['sell_price'] ?? 0);

            // Giá gia hạn: renew_price của đuôi × năm; fallback giá bán đăng ký.
            $renewalPrice = $tld && $tld->renew_price
                ? (float) $tld->renew_price * $years
                : ($sell > 0 ? $sell : ($cost > 0 ? $cost : 0));

            $leadDays = (int) config('services.renewal.reminder_lead_days', 7);

            $service = CustomerService::create([
                'customer_id'             => $provision->customer_id,
                'provision_id'            => $provision->id,
                'product_id'              => $provision->product_id,
                'order_item_id'           => $provision->order_item_id,
                'status'                  => 'active',
                'started_at'              => $registeredAt,
                'expires_at'              => $expiresAt,
                'next_renewal_date'       => $expiresAt->copy()->subDays($leadDays),
                'auto_renew'              => (bool) ($pdata['auto_renewal'] ?? $pdata['auto_renew'] ?? false),
                'renewal_price'           => $renewalPrice > 0 ? $renewalPrice : null,
                'renewal_price_locked_at' => now(),
                'billing_cycle'           => 'yearly',
            ]);

            $this->upsertDomain($provision, $pdata, $service, [
                'domain_name' => $domainName,
                'years'       => $years,
                'registered'  => $registeredAt,
                'expires'     => $expiresAt,
                'cost'        => $cost,
                'sell'        => $sell,
                'tld'         => $tld,
            ]);

            $this->logActivity('Domain activated from provision', [
                'provision_id'        => $provision->id,
                'customer_service_id' => $service->id,
                'domain'              => $domainName,
                'expires_at'          => $expiresAt->toDateString(),
            ]);

            return $service;
        });
    }

    private function upsertDomain(ServiceProvision $provision, array $pdata, CustomerService $service, array $c): void
    {
        // Idempotent: không tạo trùng nếu domain (theo order_item) đã tồn tại.
        $existing = $provision->order_item_id
            ? Domain::where('order_item_id', $provision->order_item_id)->first()
            : Domain::where('domain_name', $c['domain_name'])->first();

        $sld = $pdata['sld'] ?? ($c['tld'] && str_ends_with($c['domain_name'], '.' . $c['tld']->tld)
            ? substr($c['domain_name'], 0, -(strlen($c['tld']->tld) + 1))
            : $c['domain_name']);
        $tldStr = $pdata['tld'] ?? ($c['tld']->tld ?? '');

        $attrs = [
            'customer_id'         => $provision->customer_id,
            'order_item_id'       => $provision->order_item_id,
            'customer_service_id' => $service->id,
            'tld_id'              => $pdata['tld_id'] ?? ($c['tld']->id ?? null),
            'domain_name'         => $c['domain_name'],
            'sld'                 => $sld,
            'tld'                 => $tldStr,
            'status'              => Domain::STATUS_ACTIVE,
            'years'               => $c['years'],
            'registered_at'       => $c['registered'],
            'expires_at'          => $c['expires'],
            'cost_price'          => $c['cost'],
            'sell_price'          => $c['sell'],
            'profit'              => round($c['sell'] - $c['cost'], 2),
            'registrant'          => $pdata['registrant'] ?? null,
            'auth_code'           => $pdata['auth_code'] ?? null,
            'registrar'           => $pdata['registrar'] ?? 'nhanhoa',
            'nameservers'         => $pdata['nameservers'] ?? null,
            'auto_renew'          => (bool) ($pdata['auto_renewal'] ?? $pdata['auto_renew'] ?? false),
            'source'              => Domain::SOURCE_ORDER,
        ];

        if ($existing) {
            $existing->update($attrs);
        } else {
            Domain::create($attrs);
        }
    }

    /**
     * Đồng bộ hạn/trạng thái Domain theo CustomerService sau khi gia hạn/hết hạn.
     * Defensive: không bao giờ ném lỗi (tránh rollback luồng tiền gọi nó).
     */
    public function syncFromService(CustomerService $service): void
    {
        try {
            $status = match ($service->status) {
                'expired' => Domain::STATUS_EXPIRED,
                'active'  => Domain::STATUS_ACTIVE,
                default   => null,
            };

            Domain::where('customer_service_id', $service->id)->get()->each(function (Domain $d) use ($service, $status) {
                $d->update(array_filter([
                    'expires_at' => $service->expires_at,
                    'status'     => $status,
                ], fn($v) => $v !== null));
            });
        } catch (\Throwable $e) {
            Log::error('Domain syncFromService failed', [
                'customer_service_id' => $service->id,
                'error'               => $e->getMessage(),
            ]);
        }
    }
}
