<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class CallLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id', 'contact_id', 'user_id', 'called_at', 'duration', 'type',
        'phone_number', 'subject', 'summary', 'outcome', 'follow_up_required', 'follow_up_date'
    ];

    protected $casts = [
        'called_at' => 'datetime',
        'follow_up_date' => 'datetime',
        'follow_up_required' => 'boolean'
    ];

    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function contact(): BelongsTo { return $this->belongsTo(Contact::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function interaction(): MorphOne { return $this->morphOne(ClientInteraction::class, 'reference'); }

    protected static function boot()
    {
        parent::boot();
        static::created(function ($call) { $call->createInteraction(); });
        static::updated(function ($call) { $call->updateInteraction(); });
    }

    public function createInteraction()
    {
        $this->interaction()->create([
            'client_id' => $this->client_id, 'user_id' => $this->user_id, 'type' => 'call',
            'title' => $this->subject, 'summary' => $this->summary,
            'importance_level' => $this->outcome === 'positive' ? 'high' : 'normal',
            'privacy_level' => 'public', 'occurred_at' => $this->called_at,
            'metadata' => ['duration' => $this->duration, 'call_type' => $this->type, 'outcome' => $this->outcome]
        ]);
    }

    public function updateInteraction()
    {
        $this->interaction?->update([
            'title' => $this->subject, 'summary' => $this->summary,
            'importance_level' => $this->outcome === 'positive' ? 'high' : 'normal',
            'occurred_at' => $this->called_at,
            'metadata' => ['duration' => $this->duration, 'call_type' => $this->type, 'outcome' => $this->outcome]
        ]);
    }

    public function scopeOutgoing($q) { return $q->where('type', 'outgoing'); }
    public function scopePositive($q) { return $q->where('outcome', 'positive'); }
    public function scopeNeedsFollowUp($q) { return $q->where('follow_up_required', true); }
}