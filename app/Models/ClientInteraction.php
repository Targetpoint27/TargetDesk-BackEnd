<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ClientInteraction extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'user_id',
        'type',
        'reference_id',
        'reference_type',
        'title',
        'summary',
        'importance_level',
        'privacy_level',
        'occurred_at',
        'metadata'
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'metadata' => 'array'
    ];

    protected $dates = [
        'occurred_at'
    ];

    /**
     * Relation vers le client
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Relation vers l'utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation polymorphique vers l'enregistrement de référence
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope pour filtrer par type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope pour filtrer par niveau d'importance
     */
    public function scopeImportance($query, $level)
    {
        return $query->where('importance_level', $level);
    }

    /**
     * Scope pour filtrer par confidentialité
     */
    public function scopePrivacy($query, $level)
    {
        return $query->where('privacy_level', $level);
    }

    /**
     * Scope pour filtrer par période
     */
    public function scopeBetweenDates($query, $start, $end)
    {
        return $query->whereBetween('occurred_at', [$start, $end]);
    }

    /**
     * Scope pour les interactions visibles par l'utilisateur
     */
    public function scopeVisibleBy($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('privacy_level', '!=', 'private')
              ->orWhere('user_id', $userId);
        });
    }
}
