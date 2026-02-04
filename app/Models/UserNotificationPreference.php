<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'timing',
        'email_enabled',
        'is_active'
    ];

    protected $casts = [
        'timing' => 'array',
        'email_enabled' => 'boolean',
        'is_active' => 'boolean'
    ];

    /**
     * Relation avec l'utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtenir les délais par défaut (en minutes)
     */
    public static function getDefaultTiming(): array
    {
        return [60]; // 1 heure par défaut
    }

    /**
     * Obtenir les préférences d'un utilisateur pour un type donné
     */
    public static function getUserPreferences(int $userId, string $type): ?self
    {
        return static::where('user_id', $userId)
            ->where('type', $type)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Créer ou mettre à jour les préférences d'un utilisateur
     */
    public static function setUserPreferences(int $userId, string $type, array $timing, bool $emailEnabled = true): self
    {
        return static::updateOrCreate(
            ['user_id' => $userId, 'type' => $type],
            [
                'timing' => $timing,
                'email_enabled' => $emailEnabled,
                'is_active' => true
            ]
        );
    }

    /**
     * Obtenir les préférences formatées pour l'affichage
     */
    public function getFormattedTimingAttribute(): array
    {
        return collect($this->timing)->map(function ($minutes) {
            if ($minutes < 60) {
                return $minutes . ' minutes';
            } elseif ($minutes < 1440) {
                $hours = $minutes / 60;
                return $hours . ' heure' . ($hours > 1 ? 's' : '');
            } else {
                $days = $minutes / 1440;
                return $days . ' jour' . ($days > 1 ? 's' : '');
            }
        })->toArray();
    }
}
