<?php

namespace App\Console\Commands;

use App\Models\Orders;
use App\Models\Order_items;
use App\Models\ServiceProvision;
use App\Models\CustomerService;
use App\Services\OrderService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Backfill ServiceProvision + CustomerService cho các order đã completed trước khi
 * có provision flow. Khách thấy "đã hoàn thành" ở /customer/orders nhưng /customer/services
 * trống vì không có provision/CS row.
 *
 * Chạy: php artisan services:backfill-orphans [--dry-run]
 */
class BackfillOrphanServices extends Command
{
    protected $signature   = 'services:backfill-orphans {--dry-run : Chỉ liệt kê, không tạo}';
    protected $description = 'Tạo ServiceProvision + CustomerService cho các order completed bị thiếu (legacy data)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $this->info($dryRun ? '🔍 DRY-RUN: chỉ liệt kê, không ghi DB.' : '⚙️  Bắt đầu backfill...');

        $orphans = Orders::where('status', 'completed')
            ->with(['items.product'])
            ->get()
            ->filter(function (Orders $o) {
                $itemIds = $o->items->pluck('id')->all();
                if (empty($itemIds)) return false;
                return !ServiceProvision::whereIn('order_item_id', $itemIds)->exists();
            });

        if ($orphans->isEmpty()) {
            $this->info('✅ Không có order nào cần backfill.');
            return self::SUCCESS;
        }

        $this->info("Tìm thấy {$orphans->count()} order cần backfill.");
        $created = ['provision' => 0, 'customer_service' => 0, 'skipped' => 0];

        foreach ($orphans as $order) {
            foreach ($order->items as $item) {
                if (!$item->product) {
                    $this->warn("  ⚠️  Item #{$item->id} (order #{$order->id}): không có product, bỏ qua.");
                    $created['skipped']++;
                    continue;
                }

                $type = $this->resolveType($item);
                if (!$type) {
                    $this->warn("  ⚠️  Item #{$item->id}: không xác định service_type, bỏ qua.");
                    $created['skipped']++;
                    continue;
                }

                $this->line("  → Order #{$order->id} item #{$item->id} ({$type}: {$item->name})");

                if ($dryRun) {
                    $created['provision']++;
                    $created['customer_service']++;
                    continue;
                }

                try {
                    DB::transaction(function () use ($order, $item, $type, &$created) {
                        // Tạo provision đã completed nhưng credentials trống → admin sẽ
                        // điền sau qua trang admin/provisions. provision_notes flag cho
                        // admin biết đây là backfill.
                        $provision = ServiceProvision::create([
                            'order_item_id'    => $item->id,
                            'product_id'       => $item->product_id,
                            'customer_id'      => $order->customer_id,
                            'provision_type'   => $type,
                            'provision_status' => 'completed',
                            'provision_data'   => json_encode([
                                'service_type'    => $type,
                                'backfilled'      => true,
                                'backfilled_at'   => now()->toIso8601String(),
                                'note'            => 'Backfilled from completed order — admin cần cập nhật credentials',
                                'original_options' => json_decode($item->options, true) ?: [],
                            ]),
                            'provisioned_at'   => $order->updated_at ?? now(),
                            'priority'         => 5,
                            'provision_notes'  => "BACKFILL: Order {$order->order_number} - {$item->name}",
                        ]);
                        $created['provision']++;

                        // Tạo CustomerService gắn với provision này.
                        $period   = $this->resolvePeriod($item);
                        $billing  = $period <= 1 ? 'monthly' : 'yearly';
                        $started  = Carbon::parse($order->updated_at ?? $order->created_at ?? now());
                        $expires  = $billing === 'yearly'
                            ? $started->copy()->addYears($period)
                            : $started->copy()->addMonths($period);

                        $renewalPrice = (float) ($item->price ?? $item->product->price ?? 0);

                        CustomerService::create([
                            'customer_id'             => $order->customer_id,
                            'provision_id'            => $provision->id,
                            'product_id'              => $item->product_id,
                            'order_item_id'           => $item->id,
                            'status'                  => $expires->isPast() ? 'expired' : 'active',
                            'started_at'              => $started,
                            'expires_at'              => $expires,
                            'next_renewal_date'       => $expires->copy()->subDays(
                                (int) config('services.renewal.reminder_lead_days', 7)
                            ),
                            'auto_renew'              => false,
                            'renewal_price'           => $renewalPrice,
                            'renewal_price_locked_at' => $started,
                            'billing_cycle'           => $billing,
                            'notes'                   => "Backfilled từ order {$order->order_number}",
                        ]);
                        $created['customer_service']++;
                    });
                } catch (\Throwable $e) {
                    $this->error("  ❌ Lỗi backfill item #{$item->id}: " . $e->getMessage());
                    Log::error('BackfillOrphanServices failed', [
                        'order_id' => $order->id,
                        'item_id'  => $item->id,
                        'error'    => $e->getMessage(),
                    ]);
                    $created['skipped']++;
                }
            }
        }

        $this->newLine();
        $this->info("✅ Hoàn thành. Tạo {$created['provision']} provision + {$created['customer_service']} customer_service. Skip: {$created['skipped']}.");
        if ($dryRun) {
            $this->warn('⚠️  Đây là DRY-RUN. Chạy lại không có --dry-run để thực sự ghi DB.');
        } else {
            $this->warn('⚠️  Admin cần vào /admin/provisions để cập nhật credentials cho các provision vừa tạo.');
        }

        return self::SUCCESS;
    }

    private function resolveType(Order_items $item): ?string
    {
        $options = json_decode($item->options, true) ?: [];
        $type = $options['service_type']
            ?? ($item->product?->category?->getServiceType())
            ?? $item->product?->type;

        if (!$type) return null;
        $type = strtolower(str_replace('-', '_', $type));
        return in_array($type, OrderService::PROVISIONABLE_TYPES) ? $type : null;
    }

    private function resolvePeriod(Order_items $item): int
    {
        $options = json_decode($item->options, true) ?: [];
        $period = (int) ($options['period'] ?? $item->duration ?? 1);
        return max(1, $period);
    }
}
