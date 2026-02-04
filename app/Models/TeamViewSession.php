<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class TeamViewSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_token',
        'user_id',
        'target_user_ids',
        'reason',
        'duration_minutes',
        'activated_at',
        'expires_at',
        'deactivated_at',
        'status',
        'accessed_resources'
    ];

    protected $casts = [
        'target_user_ids' => 'array',
        'accessed_resources' => 'array',
        'activated_at' => 'datetime',
        'expires_at' => 'datetime',
        'deactivated_at' => 'datetime'
    ];

    /**
     * Get the user who activated this session
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Boot model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($session) {
            if (empty($session->session_token)) {
                $session->session_token = \Str::random(64);
            }

            if (empty($session->activated_at)) {
                $session->activated_at = now();
            }

            if (empty($session->expires_at) && $session->duration_minutes) {
                $session->expires_at = now()->addMinutes($session->duration_minutes);
            }
        });
    }

    /**
     * Scope for active sessions
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where('expires_at', '>', now())
                    ->whereNull('deactivated_at');
    }

    /**
     * Scope for expired sessions
     */
    public function scopeExpired($query)
    {
        return $query->where(function($q) {
            $q->where('expires_at', '<=', now())
              ->orWhere('status', 'expired');
        });
    }

    /**
     * Scope for user sessions
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Check if session is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active'
               && $this->expires_at > now()
               && is_null($this->deactivated_at);
    }

    /**
     * Check if session is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at <= now() || $this->status === 'expired';
    }

    /**
     * Deactivate session
     */
    public function deactivate(): bool
    {
        return $this->update([
            'status' => 'deactivated',
            'deactivated_at' => now()
        ]);
    }

    /**
     * Mark session as expired
     */
    public function markExpired(): bool
    {
        return $this->update([
            'status' => 'expired'
        ]);
    }

    /**
     * Add accessed resource to log
     */
    public function logResourceAccess($resourceType, $resourceId, $action = 'view'): void
    {
        $accessedResources = $this->accessed_resources ?? [];

        $accessedResources[] = [
            'type' => $resourceType,
            'id' => $resourceId,
            'action' => $action,
            'timestamp' => now()->toISOString()
        ];

        $this->update(['accessed_resources' => $accessedResources]);
    }

    /**
     * Check if user can view specific user's data
     */
    public function canViewUser($targetUserId): bool
    {
        return $this->isActive() && in_array($targetUserId, $this->target_user_ids);
    }

    /**
     * Get remaining time in minutes
     */
    public function getRemainingMinutes(): int
    {
        if (!$this->isActive()) {
            return 0;
        }

        return max(0, now()->diffInMinutes($this->expires_at, false));
    }

    /**
     * Extend session duration
     */
    public function extend($additionalMinutes): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        return $this->update([
            'expires_at' => $this->expires_at->addMinutes($additionalMinutes),
            'duration_minutes' => $this->duration_minutes + $additionalMinutes
        ]);
    }
}
