<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Activitylog\Models\Activity;

class PruneActivityLogs extends Command
{
    protected $signature = 'saas:prune-activity-logs
                            {--days= : Override retention days from config}';

    protected $description = 'Prune activity logs older than the configured retention period';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? config('saas.activity_log_retention_days', 365));

        $cutoff = now()->subDays($days);

        $count = Activity::query()
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->info("Pruned {$count} activity log entries older than {$days} days.");

        return self::SUCCESS;
    }
}
