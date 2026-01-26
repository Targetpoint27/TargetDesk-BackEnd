<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Opportunity extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id', 'user_id', 'title', 'description', 'amount', 'currency', 'stage',
        'probability', 'expected_close_date', 'actual_close_date', 'priority', 'source',
        'notes', 'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'expected_close_date' => 'date',
        'actual_close_date' => 'date',
        'amount' => 'decimal:2'
    ];

    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function interaction(): MorphOne { return $this->morphOne(ClientInteraction::class, 'reference'); }

    protected static function boot()
    {
        parent::boot();
        static::created(function ($opportunity) { $opportunity->createInteraction(); });
        static::updated(function ($opportunity) { $opportunity->updateInteraction(); });
    }

    public function createInteraction()
    {
        $importanceLevel = match($this->priority) {
            'urgent' => 'critical',
            'high' => 'high',
            'medium' => 'normal',
            'low' => 'low',
            default => 'normal'
        };

        $this->interaction()->create([
            'client_id' => $this->client_id,
            'user_id' => $this->user_id,
            'type' => 'opportunity',
            'title' => $this->title,
            'summary' => strip_tags(substr($this->description ?? '', 0, 200)),
            'importance_level' => $importanceLevel,
            'privacy_level' => 'public',
            'occurred_at' => $this->created_at,
            'metadata' => [
                'stage' => $this->stage,
                'amount' => $this->amount,
                'currency' => $this->currency,
                'probability' => $this->probability,
                'expected_close_date' => $this->expected_close_date,
                'priority' => $this->priority
            ]
        ]);
    }

    public function updateInteraction()
    {
        $importanceLevel = match($this->priority) {
            'urgent' => 'critical',
            'high' => 'high',
            'medium' => 'normal',
            'low' => 'low',
            default => 'normal'
        };

        $this->interaction?->update([
            'title' => $this->title,
            'summary' => strip_tags(substr($this->description ?? '', 0, 200)),
            'importance_level' => $importanceLevel,
            'occurred_at' => $this->updated_at,
            'metadata' => [
                'stage' => $this->stage,
                'amount' => $this->amount,
                'currency' => $this->currency,
                'probability' => $this->probability,
                'expected_close_date' => $this->expected_close_date,
                'priority' => $this->priority
            ]
        ]);
    }
}
