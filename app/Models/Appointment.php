<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id', 'user_id', 'organizer_id', 'title', 'description', 'scheduled_at', 'duration',
        'location', 'meeting_url', 'type', 'status', 'timezone', 'reminder_minutes',
        'completion_notes', 'completion_outcome'
    ];

    protected $casts = [
        'scheduled_at' => 'datetime'
    ];

    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function participants(): HasMany { return $this->hasMany(AppointmentParticipant::class); }
    public function interaction(): MorphOne { return $this->morphOne(ClientInteraction::class, 'reference'); }

    protected static function boot()
    {
        parent::boot();
        static::created(function ($appointment) { $appointment->createInteraction(); });
        static::updated(function ($appointment) { $appointment->updateInteraction(); });
    }

    public function createInteraction()
    {
        $this->interaction()->create([
            'client_id' => $this->client_id, 'user_id' => $this->user_id, 'type' => 'appointment',
            'title' => $this->title, 'summary' => strip_tags(substr($this->description ?? '', 0, 200)),
            'importance_level' => $this->type === 'demo' ? 'high' : 'normal',
            'privacy_level' => 'public', 'occurred_at' => $this->scheduled_at,
            'metadata' => ['duration' => $this->duration, 'type' => $this->type, 'status' => $this->status, 'location' => $this->location]
        ]);
    }

    public function updateInteraction()
    {
        $this->interaction?->update([
            'title' => $this->title, 'summary' => strip_tags(substr($this->description ?? '', 0, 200)),
            'importance_level' => $this->type === 'demo' ? 'high' : 'normal',
            'occurred_at' => $this->scheduled_at,
            'metadata' => ['duration' => $this->duration, 'type' => $this->type, 'status' => $this->status, 'location' => $this->location]
        ]);
    }
}
