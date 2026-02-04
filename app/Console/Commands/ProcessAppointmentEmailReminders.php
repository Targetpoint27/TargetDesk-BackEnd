<?php

namespace App\Console\Commands;

use App\Jobs\SendAppointmentEmailReminder;
use App\Models\ScheduledEmailReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessAppointmentEmailReminders extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'appointments:process-email-reminders
                            {--limit=50 : Maximum number of reminders to process}
                            {--dry-run : Show what would be processed without actually processing}';

    /**
     * The console command description.
     */
    protected $description = 'Process pending appointment email reminders';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = $this->option('limit');
        $dryRun = $this->option('dry-run');

        $this->info("🔍 Searching for pending email reminders...");

        $pendingReminders = ScheduledEmailReminder::readyToSend()
            ->with(['appointment.client', 'user'])
            ->limit($limit)
            ->get();

        if ($pendingReminders->isEmpty()) {
            $this->info("✅ No pending email reminders found.");
            return 0;
        }

        $this->info("📧 Found {$pendingReminders->count()} pending email reminders");

        if ($dryRun) {
            $this->warn("🧪 DRY RUN MODE - No emails will actually be sent");

            $this->table(
                ['ID', 'Type', 'User', 'Client', 'Scheduled For', 'Email'],
                $pendingReminders->map(function ($reminder) {
                    return [
                        $reminder->id,
                        $reminder->formatted_type,
                        $reminder->user->name ?? 'N/A',
                        $reminder->appointment->client->name ?? 'N/A',
                        $reminder->scheduled_for->format('d/m/Y H:i'),
                        $reminder->email_to
                    ];
                })->toArray()
            );

            return 0;
        }

        $processed = 0;
        $failed = 0;

        $progressBar = $this->output->createProgressBar($pendingReminders->count());
        $progressBar->start();

        foreach ($pendingReminders as $reminder) {
            try {
                // Vérifications avant dispatch
                if (!$reminder->appointment) {
                    $reminder->markAsFailed('Appointment not found');
                    $failed++;
                    continue;
                }

                if (!$reminder->user || !$reminder->user->email) {
                    $reminder->markAsFailed('User email not found');
                    $failed++;
                    continue;
                }

                // Dispatch du job
                SendAppointmentEmailReminder::dispatch($reminder);

                $processed++;

                Log::info("Dispatched email reminder", [
                    'reminder_id' => $reminder->id,
                    'appointment_id' => $reminder->appointment_id,
                    'user_email' => $reminder->email_to,
                    'type' => $reminder->type
                ]);

            } catch (\Exception $e) {
                $this->error("Failed to dispatch reminder {$reminder->id}: {$e->getMessage()}");
                $reminder->markAsFailed($e->getMessage());
                $failed++;

                Log::error("Failed to dispatch email reminder", [
                    'reminder_id' => $reminder->id,
                    'error' => $e->getMessage()
                ]);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Résumé
        $this->info("📊 Processing Summary:");
        $this->line("   ✅ Processed: {$processed}");

        if ($failed > 0) {
            $this->error("   ❌ Failed: {$failed}");
        }

        $this->info("🚀 All pending email reminders have been dispatched to the queue");

        return 0;
    }
}
