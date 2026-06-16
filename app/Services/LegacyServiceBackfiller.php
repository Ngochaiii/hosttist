<?php

namespace App\Services;

use App\Models\CustomerService;
use App\Models\Products;
use App\Models\ServiceProvision;
use Carbon\Carbon;

/**
 * Tạo CustomerService cho các "dịch vụ đã bán" lưu ở bảng products (legacy, có customer_id)
 * nhưng chưa có bản ghi trong customer_services — nên không hiện/không quản lý được ở admin.
 *
 * Dùng chung bởi:
 *  - Web\ServiceController (backfill on-demand khi khách bấm gia hạn)
 *  - Console\Commands\BackfillLegacyProducts (backfill hàng loạt)
 */
class LegacyServiceBackfiller
{
    /**
     * Đã có CustomerService đại diện cho legacy product này chưa?
     * Mirror logic resolver trong Web\ServiceController::findCustomerService (bước 1 & 2)
     * để không tạo trùng.
     */
    public function existingFor(Products $product): ?CustomerService
    {
        // 1) Đã backfill trước đó (gắn legacy_product_id)
        $byLegacy = CustomerService::where('customer_id', $product->customer_id)
            ->where('legacy_product_id', $product->id)
            ->first();
        if ($byLegacy) {
            return $byLegacy;
        }

        // 2) Đã có CustomerService từ luồng order (match theo template/product_id, chưa gắn legacy)
        $templateId = $product->parent_product_id ?: $product->id;
        return CustomerService::where('customer_id', $product->customer_id)
            ->where('product_id', $templateId)
            ->whereNull('legacy_product_id')
            ->latest()
            ->first();
    }

    /**
     * Tạo CustomerService từ một legacy Products record (đã bán, có customer_id).
     * Không sửa dữ liệu Products cũ; chỉ thêm CustomerService để lifecycle/admin hoạt động.
     */
    public function create(Products $product): CustomerService
    {
        $templateId = $product->parent_product_id ?: $product->id;
        $template   = $product->parent_product_id ? Products::find($product->parent_product_id) : $product;
        $priceBase  = (float) ($template->sale_price ?: $template->price ?: $product->price ?: 0);
        $period     = (int) ($template->recurring_period ?? $product->recurring_period ?? 12);
        $cycle      = $period <= 1 ? 'monthly' : 'yearly';

        $expiresAt  = $product->end_date ? Carbon::parse($product->end_date) : null;
        $startedAt  = $product->start_date ? Carbon::parse($product->start_date) : now();

        return CustomerService::create([
            'customer_id'             => $product->customer_id,
            'provision_id'            => null,
            'product_id'              => $templateId,
            'legacy_product_id'       => $product->id,
            'order_item_id'           => null,
            'status'                  => in_array($product->service_status, ['active', 'expired', 'suspended', 'cancelled'])
                ? $product->service_status : 'active',
            'started_at'              => $startedAt,
            'expires_at'              => $expiresAt,
            'next_renewal_date'       => $expiresAt ? $expiresAt->copy()->subDays(7) : null,
            'auto_renew'              => (bool) $product->auto_renew,
            'renewal_price'           => $priceBase,
            'renewal_price_locked_at' => $startedAt,
            'billing_cycle'           => $cycle,
            'notes'                   => 'Backfilled từ Products#' . $product->id,
        ]);
    }

    /**
     * Đã có CustomerService gắn với provision này chưa?
     */
    public function existingForProvision(ServiceProvision $provision): ?CustomerService
    {
        return CustomerService::where('provision_id', $provision->id)->first();
    }

    /**
     * Tạo CustomerService từ một ServiceProvision đã hoàn tất nhưng thiếu CustomerService
     * (provision có ở /admin/provisions nhưng không hiện ở /admin/services).
     *
     * Mirror logic ServiceLifecycleService::activate() nhưng dùng ngày tháng LỊCH SỬ
     * (provisioned_at) thay vì now(), và không throw khi giá <= 0 (admin sửa sau).
     */
    public function createFromProvision(ServiceProvision $provision): CustomerService
    {
        $product   = $provision->product;
        $orderItem = $provision->orderItem;

        $pdata = is_array($provision->provision_data)
            ? $provision->provision_data
            : (json_decode($provision->provision_data ?? '', true) ?: []);

        // Cùng quy tắc với ServiceLifecycleService::detectBillingCycle()
        $cycle = ($product && isset($product->recurring_period) && $product->recurring_period >= 12)
            ? 'yearly' : 'monthly';

        $started = $provision->provisioned_at
            ? Carbon::parse($provision->provisioned_at)
            : Carbon::parse($provision->created_at ?? now());
        $expires = $cycle === 'yearly'
            ? $started->copy()->addYear()
            : $started->copy()->addMonth();

        $leadDays     = (int) config('services.renewal.reminder_lead_days', 7);
        $renewalPrice = (float) ($orderItem->unit_price ?? $product->price ?? 0);

        return CustomerService::create([
            'customer_id'             => $provision->customer_id,
            'provision_id'            => $provision->id,
            'product_id'              => $provision->product_id,
            'order_item_id'           => $provision->order_item_id,
            'status'                  => $expires->isPast() ? 'expired' : 'active',
            'started_at'              => $started,
            'expires_at'              => $expires,
            'next_renewal_date'       => $expires->copy()->subDays($leadDays),
            'auto_renew'              => (bool) ($pdata['auto_renew'] ?? false),
            'renewal_price'           => $renewalPrice,
            'renewal_price_locked_at' => $started,
            'billing_cycle'           => $cycle,
            'notes'                   => 'Backfilled từ ServiceProvision#' . $provision->id,
        ]);
    }
}
