<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AppointmentReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Appointment $appointment,
        public string $reminderType,
        public string $recipientType = 'organizer',
        public ?string $participantName = null
    ) {}

    /**
     * Build the message.
     */
    public function build()
    {
        $timeUntil = $this->appointment->scheduled_at->diffForHumans();
        $subject = $this->getSubject();

        // URL vers la fiche client
        $clientUrl = url("/api/v1/clients/{$this->appointment->client_id}");

        return $this->markdown('emails.appointment-reminder')
                    ->subject($subject)
                    ->with([
                        'appointment' => $this->appointment,
                        'client' => $this->appointment->client,
                        'timeUntil' => $timeUntil,
                        'clientUrl' => $clientUrl,
                        'participants' => $this->appointment->participants,
                        'reminderType' => $this->reminderType,
                        'recipientType' => $this->recipientType,
                        'participantName' => $this->participantName,
                        'organizerName' => $this->appointment->user->first_name ?? $this->appointment->user->name
                    ]);
    }

    /**
     * Générer l'objet de l'email selon le type de rappel
     */
    private function getSubject(): string
    {
        $clientName = $this->appointment->client->name;
        $prefix = $this->recipientType === 'participant' ? 'Invitation rappel' : 'Rappel';

        return match($this->reminderType) {
            'reminder_15m' => "🕐 {$prefix} : RDV dans 15 minutes avec {$clientName}",
            'reminder_60m' => "⏰ {$prefix} : RDV dans 1 heure avec {$clientName}",
            'reminder_1440m' => "📅 {$prefix} : RDV demain avec {$clientName}",
            default => "📋 {$prefix} de rendez-vous avec {$clientName}"
        };
    }

    /**
     * Get the tags for the message (pour analytics email)
     */
    public function tags(): array
    {
        return [
            'appointment-reminder',
            $this->reminderType,
            'client-' . $this->appointment->client_id
        ];
    }
}
