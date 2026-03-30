<?php

namespace App\Services;

use App\Models\{CustomerService, ServiceProvision, Customers, Orders};
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class ServiceLifecycleService extends BaseService
{
    public function __construct(
        private PaymentService $paymentService,
        private EmailService   $emailService
    ) {}

    /**
     * Kích hoạt CustomerService khi provision hoàn thành.
     * Gọi từ ProvisionService::markProvisionCompleted().
     */
    public function activateFromProvision(ServiceProvision $provision): CustomerService
    {
        return $this->transaction(function () use ($provision) {
            $product  = $provision->product;
            $orderItem = $provision->orderItem;

            // Tính ngày hết hạn dựa trên chu kỳ sản phẩm
            $billingCycle  = $this->detectBillingCycle($product);
            $expiresAt     = $this->calculateExpiryDate($billingCycle);
            $renewalDate   = $expiresAt->copy()->subDays(7); // nhắc 7 ngày trước

            $service = CustomerService::create([
                'customer_id'       => $provision->customer_id,
                'provision_id'      => $provision->id,
                'product_id'        => $provision->product_id,
                'order_item_id'     => $provision->order_item_id,
                'status'            => 'active',
                'started_at'        => now(),
                'expires_at'        => $expiresAt,
                'next_renewal_date' => $renewalDate,
                'auto_renew'        => false,
                'renewal_price'     => $orderItem?->unit_price ?? $product->price,
                'billing_cycle'     => $billingCycle,
            ]);

            $this->logActivity('CustomerService activated', [
                'customer_service_id' => $service->id,
                'provision_id'        => $provision->id,
                'expires_at'          => $expiresAt->toDateString(),
            ]);

            return $service;
        });
    }

    /**
     * Gia hạn dịch vụ — tạo order mới nếu cần thanh toán, hoặc tự trừ ví.
     */
    public function renew(CustomerService $service, Customers $customer): array
    {
        if (!in_array($service->status, ['active', 'expired'])) {
            return ['success' => false, 'error' => 'Dịch vụ không thể gia hạn từ trạng thái hiện tại.'];
        }

        if (!$service->renewal_price || $service->renewal_price <= 0) {
            return ['success' => false, 'error' => 'Giá gia hạn chưa được cấu hình. Liên hệ admin.'];
        }

        return $this->transaction(function () use ($service, $customer) {
            $amount = $service->renewal_price;

            if (!$customer->hasBalance($amount)) {
                return [
                    'success'         => false,
                    'error'           => 'Số dư ví không đủ để gia hạn.',
                    'need_deposit'    => true,
                    'required_amount' => $amount,
                ];
            }

            // Trừ ví và gia hạn
            $customer->decrement('balance', $amount);

            $newExpiry = $this->extendExpiry($service);

            $service->update([
                'status'             => 'active',
                'expires_at'         => $newExpiry,
                'next_renewal_date'  => $newExpiry->copy()->subDays(7),
                // Reset notification flags để nhắc lại chu kỳ mới
                'notified_30d_at'    => null,
                'notified_15d_at'    => null,
                'notified_7d_at'     => null,
                'notified_1d_at'     => null,
            ]);

            $this->logActivity('CustomerService renewed', [
                'customer_service_id' => $service->id,
                'new_expiry'          => $newExpiry->toDateString(),
                'amount_charged'      => $amount,
            ]);

            return [
                'success'     => true,
                'new_expiry'  => $newExpiry,
                'new_balance' => $customer->fresh()->balance,
            ];
        });
    }

    /**
     * Hủy dịch vụ theo yêu cầu khách.
     */
    public function cancel(CustomerService $service, string $reason = ''): bool
    {
        return $this->transaction(function () use ($service, $reason) {
            $service->update([
                'status' => 'cancelled',
                'notes'  => $reason ?: $service->notes,
            ]);

            $this->logActivity('CustomerService cancelled', [
                'customer_service_id' => $service->id,
                'reason'              => $reason,
            ]);

            return true;
        });
    }

    /**
     * Mark service as expired (gọi bởi CheckServiceExpiry command).
     */
    public function markExpired(CustomerService $service): void
    {
        $service->update(['status' => 'expired']);

        $this->logActivity('CustomerService expired', [
            'customer_service_id' => $service->id,
        ]);
    }

    /**
     * Auto-renew: chỉ áp dụng khi auto_renew = true và đủ số dư.
     * Gọi bởi CheckServiceExpiry command.
     */
    public function attemptAutoRenew(CustomerService $service): bool
    {
        if (!$service->auto_renew) {
            return false;
        }

        $customer = $service->customer;
        $result   = $this->renew($service, $customer);

        if ($result['success']) {
            Log::info('Auto-renew thành công', [
                'customer_service_id' => $service->id,
                'customer_id'         => $customer->id,
            ]);
            return true;
        }

        Log::warning('Auto-renew thất bại (số dư không đủ)', [
            'customer_service_id' => $service->id,
            'required'            => $service->renewal_price,
            'balance'             => $customer->balance,
        ]);
        return false;
    }

    // ===== Private helpers =====

    private function detectBillingCycle($product): string
    {
        // Dựa vào recurring_period hoặc tên sản phẩm
        if (isset($product->recurring_period)) {
            return $product->recurring_period >= 12 ? 'yearly' : 'monthly';
        }
        return 'yearly';
    }

    private function calculateExpiryDate(string $cycle): Carbon
    {
        return match ($cycle) {
            'monthly' => now()->addMonth(),
            'yearly'  => now()->addYear(),
            default   => now()->addYear(),
        };
    }

    private function extendExpiry(CustomerService $service): Carbon
    {
        $base = $service->expires_at && $service->expires_at->isFuture()
            ? $service->expires_at
            : now();

        return match ($service->billing_cycle) {
            'monthly' => $base->copy()->addMonth(),
            'yearly'  => $base->copy()->addYear(),
            default   => $base->copy()->addYear(),
        };
    }
}
