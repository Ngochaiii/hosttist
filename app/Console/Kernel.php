<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('logs:cleanup')->weekly();

        // Kiểm tra dịch vụ hết hạn: chạy hàng ngày lúc 8:00 sáng.
        // withoutOverlapping: nếu lần chạy trước (do retry hoặc nhiều provision) còn treo → bỏ qua.
        // onFailure: ghi log để alert khi job tự crash (nếu không sẽ mất dấu).
        $schedule->command('services:check-expiry')
            ->dailyAt('08:00')
            ->withoutOverlapping(60)
            ->onFailure(function () {
                Log::error('Scheduled job services:check-expiry FAILED', [
                    'time' => now()->toDateTimeString(),
                ]);
            });
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
