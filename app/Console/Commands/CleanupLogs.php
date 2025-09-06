<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProvisionLog;
use Carbon\Carbon;

class CleanupLogs extends Command
{
    protected $signature = 'logs:cleanup {--days=90}';
    protected $description = 'Clean up old provision logs';

    public function handle()
    {
        $days = $this->option('days');
        $cutoffDate = Carbon::now()->subDays($days);
        
        $deletedCount = ProvisionLog::where('created_at', '<', $cutoffDate)
            ->where('severity', '!=', 'error') // Keep error logs longer
            ->delete();
        
        $this->info("Deleted {$deletedCount} log entries older than {$days} days.");
        
        return 0;
    }
}