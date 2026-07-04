<?php

namespace App\Console\Commands;

use App\Models\CustomerService;
use App\Notifications\CustomerAlert;
use App\Services\ServiceLifecycleService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckServiceExpiry extends Command
{
    protected $signature   = 'services:check-expiry';
    protected $description = 'Kiểm tra dịch vụ sắp hết hạn, gửi nhắc nhở và xử lý auto-renew';

    public function __construct(private ServiceLifecycleService $lifecycle)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $this->info('Bắt đầu kiểm tra dịch vụ hết hạn...');

        // 1. Đánh dấu các dịch vụ đã quá hạn
        $this->markExpiredServices();

        // 2. Gửi nhắc nhở theo các mốc ngày (config-driven).
        // Mapping ngày → cột notified_*d_at; cột phải tồn tại trên customer_services.
        $milestones = config('services.renewal.reminder_milestones', [30, 15, 7, 1]);
        foreach ($milestones as $days) {
            $this->sendReminders((int) $days, "notified_{$days}d_at");
        }

        $this->info('Hoàn thành.');
    }

    private function markExpiredServices(): void
    {
        $expired = CustomerService::expired()->with('customer')->get();

        foreach ($expired as $service) {
            // Thử auto-renew trước khi mark expired
            if ($service->auto_renew) {
                $renewed = $this->lifecycle->attemptAutoRenew($service);
                if ($renewed) {
                    $this->line("  ✅ Auto-renew thành công: service #{$service->id}");
                    continue;
                }
            }

            $this->lifecycle->markExpired($service);
            $this->line("  ⚠️  Đã mark expired: service #{$service->id}");
        }

        $this->info("Đã xử lý {$expired->count()} dịch vụ quá hạn.");
    }

    private function sendReminders(int $days, string $notifiedField): void
    {
        // Exact-day match: chỉ lấy dịch vụ có expires_at rơi vào ngày D+$days.
        // Đơn giản hơn previousMilestone vì cron chạy daily — mỗi service đi qua đủ mốc tự nhiên,
        // không cần "nuốt" nhiều ngày khi cron lỡ chạy (nếu cron chết → ta accept mất 1 mốc, gửi mốc kế).
        $target = now()->addDays($days)->toDateString();

        $services = CustomerService::active()
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', $target)
            ->whereNull($notifiedField)
            ->with(['customer.user', 'product'])
            ->get();

        $sent = 0;
        foreach ($services as $service) {
            $user = $service->customer->user ?? null;

            if (!$user) {
                Log::warning("CheckServiceExpiry: không tìm thấy user cho service #{$service->id}");
                continue;
            }

            try {
                $user->notify(new CustomerAlert(
                    'service_expiry',
                    'Dịch vụ sắp hết hạn (' . $days . ' ngày)',
                    'Dịch vụ ' . ($service->product->name ?? '#' . $service->id)
                        . ' sẽ hết hạn ngày ' . $service->expires_at->format('d/m/Y')
                        . '. Vui lòng gia hạn để không bị gián đoạn.',
                    route('customer.services.service.renew.quote', $service->id),
                    $days <= 7 ? 'warning' : 'info'
                ));
                $service->update([$notifiedField => now()]);
                $this->line("  🔔 Đã nhắc {$days}d: service #{$service->id} → user #{$user->id}");
                $sent++;
            } catch (\Exception $e) {
                Log::error("CheckServiceExpiry: gửi thông báo thất bại cho service #{$service->id}: " . $e->getMessage());
            }
        }

        $this->info("Đã gửi {$sent} thông báo nhắc {$days} ngày.");
    }
}
