<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class ClientEmail extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id', 'user_id', 'contact_id', 'subject', 'content', 'from_email', 'to_email',
        'cc_emails', 'bcc_emails', 'direction', 'status', 'sent_at', 'attachments'
    ];

    protected $casts = [
        'cc_emails' => 'array',
        'bcc_emails' => 'array',
        'attachments' => 'array',
        'sent_at' => 'datetime'
    ];

    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function contact(): BelongsTo { return $this->belongsTo(Contact::class); }
    public function interaction(): MorphOne { return $this->morphOne(ClientInteraction::class, 'reference'); }

    protected static function boot()
    {
        parent::boot();
        static::created(function ($email) { $email->createInteraction(); });
        static::updated(function ($email) { $email->updateInteraction(); });
    }

    public function createInteraction()
    {
        $this->interaction()->create([
            'client_id' => $this->client_id,
            'user_id' => $this->user_id,
            'type' => 'email',
            'title' => $this->subject,
            'summary' => strip_tags(substr($this->content, 0, 200)),
            'importance_level' => 'normal',
            'privacy_level' => 'public',
            'occurred_at' => $this->sent_at ?? now(),
            'metadata' => [
                'direction' => $this->direction,
                'status' => $this->status,
                'from_email' => $this->from_email,
                'to_email' => $this->to_email,
                'attachments_count' => count($this->attachments ?? [])
            ]
        ]);
    }

    public function updateInteraction()
    {
        $this->interaction?->update([
            'title' => $this->subject,
            'summary' => strip_tags(substr($this->content, 0, 200)),
            'occurred_at' => $this->sent_at ?? now(),
            'metadata' => [
                'direction' => $this->direction,
                'status' => $this->status,
                'from_email' => $this->from_email,
                'to_email' => $this->to_email,
                'attachments_count' => count($this->attachments ?? [])
            ]
        ]);
    }
}
