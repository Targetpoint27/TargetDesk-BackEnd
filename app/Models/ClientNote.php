<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class ClientNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'user_id',
        'title',
        'content',
        'type',
        'is_pinned',
        'attachments_count'
    ];

    protected $casts = [
        'is_pinned' => 'boolean'
    ];

    /**
     * Relation vers le client
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Relation vers l'utilisateur créateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation vers les attachements
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(NoteAttachment::class, 'note_id');
    }

    /**
     * Relation vers l'interaction timeline
     */
    public function interaction(): MorphOne
    {
        return $this->morphOne(ClientInteraction::class, 'reference');
    }

    /**
     * Boot du modèle pour auto-création de l'interaction
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($note) {
            $note->createInteraction();
        });

        static::updated(function ($note) {
            $note->updateInteraction();
        });
    }

    /**
     * Créer l'entrée dans la timeline des interactions
     */
    public function createInteraction()
    {
        $this->interaction()->create([
            'client_id' => $this->client_id,
            'user_id' => $this->user_id,
            'type' => 'note',
            'title' => $this->title,
            'summary' => strip_tags(substr($this->content, 0, 200)),
            'importance_level' => $this->type === 'important' ? 'high' : 'normal',
            'privacy_level' => $this->type === 'private' ? 'private' : 'public',
            'occurred_at' => $this->created_at,
            'metadata' => [
                'attachments_count' => $this->attachments_count,
                'is_pinned' => $this->is_pinned
            ]
        ]);
    }

    /**
     * Mettre à jour l'interaction existante
     */
    public function updateInteraction()
    {
        $interaction = $this->interaction;
        if ($interaction) {
            $interaction->update([
                'title' => $this->title,
                'summary' => strip_tags(substr($this->content, 0, 200)),
                'importance_level' => $this->type === 'important' ? 'high' : 'normal',
                'privacy_level' => $this->type === 'private' ? 'private' : 'public',
                'metadata' => [
                    'attachments_count' => $this->attachments_count,
                    'is_pinned' => $this->is_pinned
                ]
            ]);
        }
    }

    /**
     * Scope pour les notes importantes
     */
    public function scopeImportant($query)
    {
        return $query->where('type', 'important');
    }

    /**
     * Scope pour les notes privées
     */
    public function scopePrivate($query)
    {
        return $query->where('type', 'private');
    }

    /**
     * Scope pour les notes épinglées
     */
    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }
}
