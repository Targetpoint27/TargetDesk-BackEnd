<?php

namespace App\Jobs;

use App\Mail\AppointmentReminderMail;
use App\Models\ScheduledEmailReminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendAppointmentEmailReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $maxExceptions = 3;
    public int $timeout = 120;

    public function __construct(
        public ScheduledEmailReminder $scheduledReminder
    ) {
        // Délai avant retry en cas d'échec : 2 minutes, puis 5 minutes, puis 10 minutes
        $this->backoff = [120, 300, 600];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Vérifier que le rappel est toujours en attente
            if ($this->scheduledReminder->status !== ScheduledEmailReminder::STATUS_PENDING) {
                Log::info("Reminder {$this->scheduledReminder->id} already processed, skipping");
                return;
            }

            // Charger le rendez-vous avec les relations
            $appointment = $this->scheduledReminder->appointment()
                ->with(['client', 'participants.contact', 'user'])
                ->first();

            if (!$appointment) {
                $this->scheduledReminder->markAsFailed('Appointment not found');
                Log::error("Appointment not found for reminder {$this->scheduledReminder->id}");
                return;
            }

            // Vérifier que le rendez-vous n'est pas annulé
            if (in_array($appointment->status, ['cancelled', 'completed'])) {
                $this->scheduledReminder->markAsFailed('Appointment cancelled or completed');
                Log::info("Appointment {$appointment->id} is {$appointment->status}, skipping reminder");
                return;
            }

            // Détermine le destinataire et les paramètres selon le type
            if ($this->scheduledReminder->recipient_type === 'organizer') {
                // Email pour l'organisateur
                $user = $this->scheduledReminder->user;
                if (!$user || !$user->email) {
                    $this->scheduledReminder->markAsFailed('User email not found');
                    Log::error("User email not found for reminder {$this->scheduledReminder->id}");
                    return;
                }

                $recipientEmail = $user->email;
                $participantName = null;
            } else {
                // Email pour un participant
                $participant = $this->scheduledReminder->participant;
                if (!$participant || !$participant->email) {
                    $this->scheduledReminder->markAsFailed('Participant email not found');
                    Log::error("Participant email not found for reminder {$this->scheduledReminder->id}");
                    return;
                }

                $recipientEmail = $participant->email;
                $participantName = $participant->name;
            }

            // Envoyer l'email de rappel avec les paramètres appropriés
            Mail::to($recipientEmail)->send(
                new AppointmentReminderMail(
                    $appointment,
                    $this->scheduledReminder->type,
                    $this->scheduledReminder->recipient_type,
                    $participantName
                )
            );

            // Marquer comme envoyé
            $this->scheduledReminder->markAsSent();

            Log::info("Email reminder sent successfully", [
                'reminder_id' => $this->scheduledReminder->id,
                'appointment_id' => $appointment->id,
                'recipient_email' => $recipientEmail,
                'recipient_type' => $this->scheduledReminder->recipient_type,
                'type' => $this->scheduledReminder->type
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to send email reminder", [
                'reminder_id' => $this->scheduledReminder->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->scheduledReminder->markAsFailed($e->getMessage());

            // Re-throw pour déclencher le retry automatique de Laravel
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Exception $exception): void
    {
        Log::error("Email reminder job failed permanently", [
            'reminder_id' => $this->scheduledReminder->id,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        $this->scheduledReminder->markAsFailed(
            "Job failed after {$this->attempts()} attempts: " . $exception->getMessage()
        );
    }

    /**
     * Get the unique ID for the job (évite les doublons)
     */
    public function uniqueId(): string
    {
        return "email_reminder_{$this->scheduledReminder->id}";
    }
}
