<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Process recurring transactions daily at midnight
Schedule::command('transactions:process-recurring')->daily();

// Prune activity log entries older than ACTIVITYLOG_RETENTION_DAYS.
// --force: the command asks for confirmation in production otherwise.
Schedule::command('activitylog:clean --force')->daily();

// Delete Sanctum API tokens that expired more than 24 hours ago.
Schedule::command('sanctum:prune-expired --hours=24')->daily();

// Delete personal data export zips whose download link has expired.
Schedule::command('privacy:purge-exports')->daily();

// Shared-hosting fallback (QUEUE_RUN_VIA_SCHEDULER=true): drain the queue from
// the single schedule:run cron line. --max-time keeps each run under a minute.
if (config('queue.run_via_scheduler')) {
    Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')
        ->everyMinute()
        ->withoutOverlapping();
}
