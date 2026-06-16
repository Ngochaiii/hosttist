<?php

namespace App\Console\Commands;

use App\Models\ServiceProvision;
use App\Services\LegacyServiceBackfiller;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Backfill CustomerService cho các ServiceProvision đã hoàn tất nhưng thiếu CustomerService.
 * Những provision này hiện ở /admin/provisions nhưng KHÔNG hiện ở /admin/services.
 *
 * Idempotent: chạy lại nhiều lần không tạo trùng (dò theo provision_id).
 * Chạy: php artisan services:backfill-from-provisions [--dry-run] [--status=completed]
 */
class BackfillFromProvisions extends Command
{
    protected $signature   = 'services:backfill-from-provisions
        {--dry-run : Chỉ liệt kê, không ghi DB}
        {--status=completed : Lọc theo provision_status (mặc định completed; "all" = mọi trạng thái)}';
    protected $description = 'Tạo CustomerService cho ServiceProvision còn thiếu, để hiện đủ ở /admin/services';

    public function handle(LegacyServiceBackfiller $backfiller): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $status = $this->option('status');
        $this->info($dryRun ? '🔍 DRY-RUN: chỉ liệt kê, không ghi DB.' : '⚙️  Bắt đầu backfill...');

        // Phân bố trạng thái để bạn thấy bức tranh tổng thể
        $this->line('Phân bố ServiceProvision theo trạng thái:');
        foreach (ServiceProvision::select('provision_status', DB::raw('count(*) as c'))
                     ->groupBy('provision_status')->pluck('c', 'provision_status') as $st => $c) {
            $this->line("  - {$st}: {$c}");
        }

        $query = ServiceProvision::with(['product', 'orderItem']);
        if ($status !== 'all') {
            $query->where('provision_status', $status);
        }
        $provisions = $query->orderBy('id')->get();
        $this->info("Xét {$provisions->count()} provision (status=" . $status . ").");

        $created = 0;
        $skipped = 0;
        $failed  = 0;

        foreach ($provisions as $provision) {
            if ($backfiller->existingForProvision($provision)) {
                $skipped++;
                continue;
            }

            $name  = $provision->product->name ?? ('product#' . $provision->product_id);
            $label = "Provision#{$provision->id} [{$provision->provision_status}] {$name} (customer {$provision->customer_id})";

            if ($dryRun) {
                $this->line("  + sẽ tạo: {$label}");
                $created++;
                continue;
            }

            try {
                DB::transaction(fn () => $backfiller->createFromProvision($provision));
                $this->line("  ✓ đã tạo: {$label}");
                $created++;
            } catch (\Throwable $e) {
                $this->error("  ✗ lỗi {$label}: {$e->getMessage()}");
                Log::error('BackfillFromProvisions failed', [
                    'provision_id' => $provision->id,
                    'error'        => $e->getMessage(),
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
