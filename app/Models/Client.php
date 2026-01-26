<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'name',
        'type',
        'email',
        'phone',
        'address',
        'siret',
        'sector',
        'website',
        'notes',
        'is_active',
        'created_by'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    /**
     * Generate unique client ID on creation
     */
    protected static function booted()
    {
        static::creating(function ($client) {
            if (empty($client->client_id)) {
                $client->client_id = 'CLI-' . strtoupper(Str::random(10));
            }
        });
    }

    /**
     * Get the user who created this client
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the contacts for this client
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class)->where('is_active', true);
    }

    /**
     * Get the categories assigned to this client
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'client_categories')
                    ->withPivot('assigned_by', 'assigned_at')
                    ->withTimestamps();
    }

    /**
     * Scope for active clients
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the primary contact for this client
     */
    public function primaryContact(): HasMany
    {
        return $this->contacts()->where('is_primary', true);
    }

    /**
     * Get categories by type for this client
     */
    public function categoriesByType($type): BelongsToMany
    {
        return $this->categories()->where('categories.type', $type);
    }

    /**
     * Check if client has a specific category
     */
    public function hasCategory($categoryId): bool
    {
        return $this->categories()->where('category_id', $categoryId)->exists();
    }

    /**
     * Get categories count for this client
     */
    public function getCategoriesCountAttribute(): int
    {
        return $this->categories()->count();
    }

    /**
     * Get contacts count for this client
     */
    public function getContactsCountAttribute(): int
    {
        return $this->contacts()->count();
    }

    /**
     * Get the notes for this client
     */
    public function notes(): HasMany
    {
        return $this->hasMany(ClientNote::class);
    }

    /**
     * Get the call logs for this client
     */
    public function callLogs(): HasMany
    {
        return $this->hasMany(CallLog::class);
    }

    /**
     * Get the appointments for this client
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Get the interactions for this client
     */
    public function interactions(): HasMany
    {
        return $this->hasMany(ClientInteraction::class);
    }

    /**
     * Get the emails for this client
     */
    public function emails(): HasMany
    {
        return $this->hasMany(ClientEmail::class);
    }

    /**
     * Get the opportunities for this client
     */
    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class);
    }

    /**
     * Get the audit logs for this client
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }
}
