<?php

namespace App\Console\Commands;

use App\Mail\ServiceExpiryReminder;
use App\Models\CustomerService;
use App\Services\ServiceLifecycleService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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

        // 2. Gửi nhắc nhở theo các mốc ngày
        $this->sendReminders(30, 'notified_30d_at');
        $this->sendReminders(15, 'notified_15d_at');
        $this->sendReminders(7,  'notified_7d_at');
        $this->sendReminders(1,  'notified_1d_at');

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
        // Lấy dịch vụ hết hạn trong $days ngày tới, chưa gửi nhắc ở mốc này
        $services = CustomerService::active()
            ->expiringSoon($days)
            ->whereNull($notifiedField)
            ->with(['customer.user', 'product'])
            ->get();

        // Lọc chính xác: chỉ lấy dịch vụ cách đúng khoảng ngày đó
        // (expiringSoon(30) lấy tất cả ≤ 30 ngày, cần tránh gửi email trùng cho 15/7/1)
        $exact = $services->filter(function (CustomerService $service) use ($days) {
            $daysLeft = $service->daysUntilExpiry();
            return $daysLeft !== null && $daysLeft <= $days && $daysLeft > ($days === 1 ? 0 : $this->previousMilestone($days));
        });

        foreach ($exact as $service) {
            $email = $service->customer->user->email ?? null;

            if (!$email) {
                Log::warning("CheckServiceExpiry: không tìm thấy email cho service #{$service->id}");
                continue;
            }

            try {
                Mail::to($email)->queue(new ServiceExpiryReminder($service, $service->daysUntilExpiry() ?? $days));
                $service->update([$notifiedField => now()]);
                $this->line("  📧 Đã gửi nhắc {$days}d: service #{$service->id} → {$email}");
            } catch (\Exception $e) {
                Log::error("CheckServiceExpiry: gửi email thất bại cho service #{$service->id}: " . $e->getMessage());
            }
        }

        $this->info("Đã gửi {$exact->count()} email nhắc {$days} ngày.");
    }

    private function previousMilestone(int $days): int
    {
        return match ($days) {
            30 => 15,
            15 => 7,
            7  => 1,
            1  => 0,
            default => 0,
        };
    }
}
