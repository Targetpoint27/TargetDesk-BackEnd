<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class ScheduledEmailReminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_id',
        'user_id',
        'participant_id',
        'recipient_type',
        'type',
        'scheduled_for',
        'sent_at',
        'status',
        'email_to',
        'failure_reason'
    ];

    protected $casts = [
        'scheduled_for' => 'datetime',
        'sent_at' => 'datetime'
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_SENT = 'sent';
    const STATUS_FAILED = 'failed';

    /**
     * Relation avec le rendez-vous
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Relation avec l'utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation avec le participant
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(AppointmentParticipant::class, 'participant_id');
    }

    /**
     * Marquer le rappel comme envoyé
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
            'failure_reason' => null
        ]);
    }

    /**
     * Marquer le rappel comme échoué
     */
    public function markAsFailed(string $reason): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'failure_reason' => $reason
        ]);
    }

    /**
     * Scope pour les rappels en attente
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope pour les rappels prêts à être envoyés
     */
    public function scopeReadyToSend($query)
    {
        return $query->pending()
            ->where('scheduled_for', '<=', now());
    }

    /**
     * Scope pour les rappels envoyés
     */
    public function scopeSent($query)
    {
        return $query->where('status', self::STATUS_SENT);
    }

    /**
     * Scope pour les rappels échoués
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Vérifier si le rappel est dû
     */
    public function isDue(): bool
    {
        return $this->status === self::STATUS_PENDING &&
               $this->scheduled_for <= now();
    }

    /**
     * Créer des rappels pour un rendez-vous
     */
    public static function createForAppointment(Appointment $appointment, array $timings): void
    {
        foreach ($timings as $minutes) {
            $scheduledFor = $appointment->scheduled_at->copy()->subMinutes($minutes);

            // Ne pas créer de rappels pour des heures passées
            if ($scheduledFor <= now()) {
                continue;
            }

            // Éviter les doublons
            $exists = static::where('appointment_id', $appointment->id)
                ->where('user_id', $appointment->user_id)
                ->where('type', "reminder_{$minutes}m")
                ->exists();

            if (!$exists) {
                static::create([
                    'appointment_id' => $appointment->id,
                    'user_id' => $appointment->user_id,
                    'type' => "reminder_{$minutes}m",
                    'scheduled_for' => $scheduledFor,
                    'email_to' => $appointment->user->email,
                    'status' => self::STATUS_PENDING
                ]);
            }
        }
    }

    /**
     * Obtenir le nom formaté du type de rappel
     */
    public function getFormattedTypeAttribute(): string
    {
        $minutes = (int) str_replace(['reminder_', 'm'], '', $this->type);

        if ($minutes < 60) {
            return $minutes . ' minutes avant';
        } elseif ($minutes < 1440) {
            $hours = $minutes / 60;
            return $hours . ' heure' . ($hours > 1 ? 's' : '') . ' avant';
        } else {
            $days = $minutes / 1440;
            return $days . ' jour' . ($days > 1 ? 's' : '') . ' avant';
        }
    }
}
