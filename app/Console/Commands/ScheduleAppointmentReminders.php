<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\ScheduledEmailReminder;
use App\Models\UserNotificationPreference;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ScheduleAppointmentReminders extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'appointments:schedule-reminders
                            {--days=7 : Number of days ahead to schedule reminders for}
                            {--force : Force reschedule even if reminders already exist}
                            {--dry-run : Show what would be scheduled without actually scheduling}';

    /**
     * The console command description.
     */
    protected $description = 'Schedule email reminders for upcoming appointments';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');

        $this->info("🔍 Scheduling reminders for appointments in the next {$days} days...");

        if ($dryRun) {
            $this->warn("🧪 DRY RUN MODE - No reminders will actually be scheduled");
        }

        // Récupérer les rendez-vous à venir
        $appointments = Appointment::with(['user', 'client'])
            ->where('scheduled_at', '>', now())
            ->where('scheduled_at', '<=', now()->addDays($days))
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->get();

        if ($appointments->isEmpty()) {
            $this->info("✅ No upcoming appointments found in the next {$days} days.");
            return 0;
        }

        $this->info("📅 Found {$appointments->count()} upcoming appointments");

        $scheduled = 0;
        $skipped = 0;
        $errors = 0;

        $progressBar = $this->output->createProgressBar($appointments->count());
        $progressBar->start();

        foreach ($appointments as $appointment) {
            try {
                // Obtenir les préférences de l'utilisateur
                $preferences = UserNotificationPreference::getUserPreferences(
                    $appointment->user_id,
                    'appointment_reminder'
                );

                $timings = $preferences?->timing ?? UserNotificationPreference::getDefaultTiming();

                // Vérifier si l'email est activé
                if ($preferences && !$preferences->email_enabled) {
                    $this->line("⏭️  Skipping {$appointment->id} - Email notifications disabled for user");
                    $skipped++;
                    $progressBar->advance();
                    continue;
                }

                $appointmentScheduled = 0;

                foreach ($timings as $minutes) {
                    $scheduledFor = $appointment->scheduled_at->copy()->subMinutes($minutes);

                    // Ne pas créer de rappels pour des heures passées
                    if ($scheduledFor <= now()) {
                        continue;
                    }

                    $reminderType = "reminder_{$minutes}m";

                    // Vérifier si le rappel existe déjà
                    $exists = ScheduledEmailReminder::where('appointment_id', $appointment->id)
                        ->where('user_id', $appointment->user_id)
                        ->where('type', $reminderType)
                        ->exists();

                    if ($exists && !$force) {
                        continue;
                    }

                    if ($dryRun) {
                        $this->line("Would schedule: {$reminderType} for appointment {$appointment->id} at {$scheduledFor->format('d/m/Y H:i')}");
                        $appointmentScheduled++;
                        continue;
                    }

                    // Supprimer l'ancien si force
                    if ($exists && $force) {
                        ScheduledEmailReminder::where('appointment_id', $appointment->id)
                            ->where('user_id', $appointment->user_id)
                            ->where('type', $reminderType)
                            ->delete();
                    }

                    // Créer le nouveau rappel
                    ScheduledEmailReminder::create([
                        'appointment_id' => $appointment->id,
                        'user_id' => $appointment->user_id,
                        'type' => $reminderType,
                        'scheduled_for' => $scheduledFor,
                        'email_to' => $appointment->user->email,
                        'status' => ScheduledEmailReminder::STATUS_PENDING
                    ]);

                    $appointmentScheduled++;

                    Log::info("Scheduled email reminder", [
                        'appointment_id' => $appointment->id,
                        'user_id' => $appointment->user_id,
                        'type' => $reminderType,
                        'scheduled_for' => $scheduledFor->toDateTimeString()
                    ]);
                }

                $scheduled += $appointmentScheduled;

                if ($appointmentScheduled === 0) {
                    $skipped++;
                }

            } catch (\Exception $e) {
                $errors++;
                $this->error("Error scheduling reminders for appointment {$appointment->id}: {$e->getMessage()}");

                Log::error("Failed to schedule reminders", [
                    'appointment_id' => $appointment->id,
                    'error' => $e->getMessage()
                ]);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Résumé
        $this->info("📊 Scheduling Summary:");
        $this->line("   📅 Appointments processed: {$appointments->count()}");
        $this->line("   ✅ Reminders scheduled: {$scheduled}");

        if ($skipped > 0) {
            $this->line("   ⏭️  Appointments skipped: {$skipped}");
        }

        if ($errors > 0) {
            $this->error("   ❌ Errors: {$errors}");
        }

        if (!$dryRun) {
            $this->info("🚀 Email reminders have been scheduled successfully");
        }

        return 0;
    }
}
