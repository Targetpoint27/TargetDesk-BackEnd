<?php

namespace App\Console\Commands;

use App\Models\ScheduledEmailReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupExpiredReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'appointments:cleanup-expired-reminders
                            {--dry-run : Show what would be cleaned without actually cleaning}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup expired appointment email reminders';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $this->info("🧹 Cleaning up expired email reminders...");

        // Trouver les rappels pour des RDV passés
        $expiredReminders = ScheduledEmailReminder::whereHas('appointment', function ($query) {
            $query->where('scheduled_at', '<=', now());
        })->where('status', ScheduledEmailReminder::STATUS_PENDING)
        ->with('appointment')
        ->get();

        // Trouver les rappels pour des RDV annulés/terminés
        $cancelledReminders = ScheduledEmailReminder::whereHas('appointment', function ($query) {
            $query->whereIn('status', ['cancelled', 'completed']);
        })->where('status', ScheduledEmailReminder::STATUS_PENDING)
        ->with('appointment')
        ->get();

        $totalToClean = $expiredReminders->count() + $cancelledReminders->count();

        if ($totalToClean === 0) {
            $this->info("✅ No expired reminders found to clean up.");
            return 0;
        }

        $this->info("📊 Found {$totalToClean} expired reminders to clean up:");
        $this->line("   📅 Expired appointments: {$expiredReminders->count()}");
        $this->line("   ❌ Cancelled/completed: {$cancelledReminders->count()}");

        if ($dryRun) {
            $this->warn("🧪 DRY RUN MODE - No reminders will actually be cleaned");

            if ($expiredReminders->isNotEmpty()) {
                $this->line("\n📅 Expired reminders:");
                $this->table(
                    ['ID', 'Appointment', 'Scheduled At', 'Type'],
                    $expiredReminders->map(function ($reminder) {
                        return [
                            $reminder->id,
                            $reminder->appointment->title ?? 'N/A',
                            $reminder->appointment->scheduled_at->format('d/m/Y H:i'),
                            $reminder->formatted_type
                        ];
                    })->toArray()
                );
            }

            return 0;
        }

        $cleaned = 0;

        // Nettoyer les rappels expirés
        foreach ($expiredReminders as $reminder) {
            $reminder->markAsFailed('Appointment has passed');
            $cleaned++;
        }

        // Nettoyer les rappels annulés
        foreach ($cancelledReminders as $reminder) {
            $reminder->markAsFailed('Appointment was ' . $reminder->appointment->status);
            $cleaned++;
        }

        $this->info("✅ Cleaned up {$cleaned} expired reminders");

        Log::info("Cleaned up expired email reminders", [
            'expired_count' => $expiredReminders->count(),
            'cancelled_count' => $cancelledReminders->count(),
            'total_cleaned' => $cleaned
        ]);

        return 0;
    }
}
