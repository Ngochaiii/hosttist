<?php

namespace App\Console\Commands;

use App\Models\Products;
use App\Services\LegacyServiceBackfiller;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Backfill CustomerService cho các "dịch vụ đã bán" legacy nằm ở bảng products
 * (có customer_id) nhưng chưa có bản ghi customer_services — nên không hiện ở
 * /admin/services. Tạo CustomerService để admin xem & quản lý được.
 *
 * Idempotent: chạy lại nhiều lần không tạo trùng.
 * Chạy: php artisan services:backfill-legacy-products [--dry-run]
 */
class BackfillLegacyProducts extends Command
{
    protected $signature   = 'services:backfill-legacy-products {--dry-run : Chỉ liệt kê, không ghi DB}';
    protected $description = 'Tạo CustomerService cho sản phẩm legacy (products có customer_id) còn thiếu, để admin quản lý được';

    public function handle(LegacyServiceBackfiller $backfiller): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $this->info($dryRun ? '🔍 DRY-RUN: chỉ liệt kê, không ghi DB.' : '⚙️  Bắt đầu backfill...');

        $products = Products::whereNotNull('customer_id')->orderBy('id')->get();
        $this->info("Có {$products->count()} sản phẩm đã bán (customer_id != null).");

        $created = 0;
        $skipped = 0;
        $failed  = 0;

        foreach ($products as $product) {
            if ($backfiller->existingFor($product)) {
                $skipped++;
                continue;
            }

            $label = "Products#{$product->id} [{$product->type}] {$product->name} (customer {$product->customer_id})";

            if ($dryRun) {
                $this->line("  + sẽ tạo: {$label}");
                $created++;
                continue;
            }

            try {
                DB::transaction(fn () => $backfiller->create($product));
                $this->line("  ✓ đã tạo: {$label}");
                $created++;
            } catch (\Throwable $e) {
                $this->error("  ✗ lỗi {$label}: {$e->getMessage()}");
                Log::error('BackfillLegacyProducts failed', [
                    'product_id' => $product->id,
                    'error'      => $e->getMessage(),
                ]);
                $failed++;
            }
        }

        $this->newLine();
        $this->info("✅ Xong. Tạo: {$created}, bỏ qua (đã có): {$skipped}, lỗi: {$failed}.");
        if ($dryRun) {
            $this->warn('⚠️  DRY-RUN — chạy lại không có --dry-run để ghi DB thật.');
        }

        return self::SUCCESS;
    }
}
