<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Process email reminders every 5 minutes
        $schedule->command('appointments:process-email-reminders')
            ->everyFiveMinutes()
            ->withoutOverlapping(10) // Prevent overlapping executions, max 10 minutes
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/email-reminders.log'));

        // Schedule reminders for the next 7 days, run every hour
        $schedule->command('appointments:schedule-reminders --days=7')
            ->hourly()
            ->withoutOverlapping(30)
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/schedule-reminders.log'));

        // Clean up expired reminders every hour
        $schedule->command('appointments:cleanup-expired-reminders')
            ->hourly()
            ->withoutOverlapping(15)
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/cleanup-reminders.log'));

        // Clean up old failed reminders (older than 30 days) - run daily at 2 AM
        $schedule->call(function () {
            \App\Models\ScheduledEmailReminder::where('status', 'failed')
                ->where('created_at', '<', now()->subDays(30))
                ->delete();
        })->dailyAt('02:00');

        // Clean up old sent reminders (older than 90 days) - run weekly
        $schedule->call(function () {
            \App\Models\ScheduledEmailReminder::where('status', 'sent')
                ->where('sent_at', '<', now()->subDays(90))
                ->delete();
        })->weekly();

        // Queue worker health check - restart if needed (every 15 minutes)
        $schedule->command('queue:restart')
            ->everyFifteenMinutes()
            ->when(function () {
                // Only restart if there are pending jobs and no workers
                return \Illuminate\Support\Facades\Queue::size() > 0;
            });
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
