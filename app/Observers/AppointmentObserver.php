<?php

namespace App\Observers;

use App\Models\Appointment;
use App\Models\ScheduledEmailReminder;
use App\Models\UserNotificationPreference;
use Illuminate\Support\Facades\Log;

class AppointmentObserver
{
    /**
     * Handle the Appointment "created" event.
     */
    public function created(Appointment $appointment): void
    {
        $this->scheduleEmailReminders($appointment);
    }

    /**
     * Handle the Appointment "updated" event.
     */
    public function updated(Appointment $appointment): void
    {
        // Vérifier si la date ou l'heure a changé
        if ($appointment->isDirty(['scheduled_at', 'status', 'user_id'])) {
            $this->handleAppointmentChanges($appointment);
        }
    }

    /**
     * Handle the Appointment "deleted" event.
     */
    public function deleted(Appointment $appointment): void
    {
        // Supprimer tous les rappels programmés
        ScheduledEmailReminder::where('appointment_id', $appointment->id)->delete();

        Log::info("Deleted email reminders for appointment", [
            'appointment_id' => $appointment->id
        ]);
    }

    /**
     * Handle the Appointment "restored" event.
     */
    public function restored(Appointment $appointment): void
    {
        // Reprogrammer les rappels si le rendez-vous est à venir
        if ($appointment->scheduled_at > now()) {
            $this->scheduleEmailReminders($appointment);
        }
    }

    /**
     * Programmer les rappels email pour un nouveau rendez-vous
     */
    private function scheduleEmailReminders(Appointment $appointment): void
    {
        try {
            // Ne pas programmer pour les rendez-vous passés, annulés ou terminés
            if ($appointment->scheduled_at <= now() ||
                in_array($appointment->status, ['cancelled', 'completed'])) {
                return;
            }

            // 1. Programmer les rappels pour l'organisateur
            $this->scheduleRemindersForOrganizer($appointment);

            // 2. Programmer les rappels pour les participants
            $this->scheduleRemindersForParticipants($appointment);

        } catch (\Exception $e) {
            Log::error("Failed to schedule email reminders", [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Programmer les rappels pour l'organisateur
     */
    private function scheduleRemindersForOrganizer(Appointment $appointment): void
    {
        // Obtenir les préférences de l'organisateur
        $preferences = UserNotificationPreference::getUserPreferences(
            $appointment->user_id,
            'appointment_reminder'
        );

        // Utiliser les timings par défaut si pas de préférences
        $timings = $preferences?->timing ?? UserNotificationPreference::getDefaultTiming();

        // Vérifier si les notifications email sont activées
        if ($preferences && !$preferences->email_enabled) {
            Log::info("Email notifications disabled for organizer", [
                'appointment_id' => $appointment->id,
                'user_id' => $appointment->user_id
            ]);
            return;
        }

        $scheduled = $this->createRemindersForUser(
            $appointment,
            $appointment->user_id,
            $appointment->user->email,
            $timings,
            'organizer'
        );

        Log::info("Scheduled email reminders for organizer", [
            'appointment_id' => $appointment->id,
            'user_id' => $appointment->user_id,
            'reminders_scheduled' => $scheduled,
            'timings' => $timings
        ]);
    }

    /**
     * Programmer les rappels pour tous les participants
     */
    public function scheduleRemindersForParticipants(Appointment $appointment): void
    {
        // Charger les participants du rendez-vous
        $participants = $appointment->participants;

        if ($participants->isEmpty()) {
            return;
        }

        // Utiliser des timings par défaut pour les participants
        $defaultTimings = UserNotificationPreference::getDefaultTiming();
        $totalScheduled = 0;

        foreach ($participants as $participant) {
            // Ne programmer que pour les participants confirmés, en attente ou invités
            if (!in_array($participant->status, ['confirmed', 'pending', 'tentative', 'invited'])) {
                continue;
            }

            // Vérifier que l'email est valide
            if (empty($participant->email) || !filter_var($participant->email, FILTER_VALIDATE_EMAIL)) {
                Log::warning("Invalid email for participant", [
                    'appointment_id' => $appointment->id,
                    'participant_id' => $participant->id,
                    'email' => $participant->email
                ]);
                continue;
            }

            $scheduled = $this->createRemindersForUser(
                $appointment,
                null, // Pas d'user_id pour les participants externes
                $participant->email,
                $defaultTimings,
                'participant',
                $participant->id
            );

            $totalScheduled += $scheduled;
        }

        Log::info("Scheduled email reminders for participants", [
            'appointment_id' => $appointment->id,
            'participants_count' => $participants->count(),
            'reminders_scheduled' => $totalScheduled,
            'timings' => $defaultTimings
        ]);
    }

    /**
     * Créer les rappels pour un utilisateur ou participant
     */
    private function createRemindersForUser(
        Appointment $appointment,
        ?int $userId,
        string $email,
        array $timings,
        string $recipientType,
        ?int $participantId = null
    ): int {
        $scheduled = 0;

        foreach ($timings as $minutes) {
            $scheduledFor = $appointment->scheduled_at->copy()->subMinutes($minutes);

            // Ne pas créer de rappels pour des heures passées
            if ($scheduledFor <= now()) {
                continue;
            }

            $reminderType = "reminder_{$minutes}m";

            // Construire les critères de vérification d'existence
            $existsQuery = ScheduledEmailReminder::where('appointment_id', $appointment->id)
                ->where('type', $reminderType)
                ->where('email_to', $email);

            if ($userId) {
                $existsQuery->where('user_id', $userId);
            } else {
                $existsQuery->whereNull('user_id');
            }

            if (!$existsQuery->exists()) {
                $reminderData = [
                    'appointment_id' => $appointment->id,
                    'user_id' => $userId,
                    'type' => $reminderType,
                    'scheduled_for' => $scheduledFor,
                    'email_to' => $email,
                    'status' => ScheduledEmailReminder::STATUS_PENDING,
                    'recipient_type' => $recipientType
                ];

                if ($participantId) {
                    $reminderData['participant_id'] = $participantId;
                }

                ScheduledEmailReminder::create($reminderData);
                $scheduled++;
            }
        }

        return $scheduled;
    }

    /**
     * Gérer les changements d'un rendez-vous existant
     */
    private function handleAppointmentChanges(Appointment $appointment): void
    {
        try {
            // Si le statut est passé à annulé ou terminé, supprimer les rappels en attente
            if (in_array($appointment->status, ['cancelled', 'completed'])) {
                $deletedCount = ScheduledEmailReminder::where('appointment_id', $appointment->id)
                    ->where('status', ScheduledEmailReminder::STATUS_PENDING)
                    ->delete();

                Log::info("Cancelled pending email reminders", [
                    'appointment_id' => $appointment->id,
                    'status' => $appointment->status,
                    'deleted_reminders' => $deletedCount
                ]);
                return;
            }

            // Si la date/heure a changé, reprogrammer les rappels
            if ($appointment->isDirty('scheduled_at')) {
                // Supprimer les anciens rappels en attente
                ScheduledEmailReminder::where('appointment_id', $appointment->id)
                    ->where('status', ScheduledEmailReminder::STATUS_PENDING)
                    ->delete();

                // Reprogrammer si le rendez-vous est à venir
                if ($appointment->scheduled_at > now()) {
                    $this->scheduleEmailReminders($appointment);
                }

                Log::info("Rescheduled email reminders for appointment", [
                    'appointment_id' => $appointment->id,
                    'new_scheduled_at' => $appointment->scheduled_at->toDateTimeString()
                ]);
            }

            // Si l'utilisateur a changé, reprogrammer avec les nouvelles préférences
            if ($appointment->isDirty('user_id')) {
                // Supprimer les anciens rappels
                ScheduledEmailReminder::where('appointment_id', $appointment->id)
                    ->where('status', ScheduledEmailReminder::STATUS_PENDING)
                    ->delete();

                // Reprogrammer pour le nouvel utilisateur
                if ($appointment->scheduled_at > now()) {
                    $this->scheduleEmailReminders($appointment);
                }

                Log::info("Rescheduled email reminders for new user", [
                    'appointment_id' => $appointment->id,
                    'new_user_id' => $appointment->user_id
                ]);
            }

        } catch (\Exception $e) {
            Log::error("Failed to handle appointment changes", [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
