<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'supplier_id',
        'civility',
        'first_name',
        'last_name',
        'function',
        'department',
        'is_primary',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'full_name',
        'primary_email',
        'primary_phone',
    ];

    // Relations
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function emails(): HasMany
    {
        return $this->hasMany(ContactEmail::class);
    }

    public function phones(): HasMany
    {
        return $this->hasMany(ContactPhone::class);
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        $civility = $this->civility ? $this->civility . ' ' : '';
        return trim($civility . $this->first_name . ' ' . $this->last_name);
    }

    public function getPrimaryEmailAttribute(): ?string
    {
        $primaryEmail = $this->emails()->where('is_primary', true)->first();
        return $primaryEmail ? $primaryEmail->email : null;
    }

    public function getPrimaryPhoneAttribute(): ?string
    {
        $primaryPhone = $this->phones()->where('is_primary', true)->first();
        return $primaryPhone ? $primaryPhone->phone : null;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeForClient($query, $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeForSupplier($query, $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function scopeOrderedByPrimary($query)
    {
        return $query->orderByDesc('is_primary')
                    ->orderBy('last_name')
                    ->orderBy('first_name');
    }

    // Business methods
    public function makePrimary(): bool
    {
        if ($this->is_primary) {
            return true; // Already primary
        }

        // Remove primary status from other contacts of the same client or supplier
        if ($this->client_id) {
            static::where('client_id', $this->client_id)
                ->where('id', '!=', $this->id)
                ->update(['is_primary' => false]);
        } elseif ($this->supplier_id) {
            static::where('supplier_id', $this->supplier_id)
                ->where('id', '!=', $this->id)
                ->update(['is_primary' => false]);
        }

        // Set this contact as primary
        $this->update(['is_primary' => true]);

        return true;
    }

    public function isEmailUniqueInClient(string $email, ?int $excludeContactId = null): bool
    {
        $query = ContactEmail::whereHas('contact', function ($q) {
            $q->where('client_id', $this->client_id)
              ->where('is_active', true);
        })->where('email', $email);

        if ($excludeContactId) {
            $query->where('contact_id', '!=', $excludeContactId);
        }

        return $query->count() === 0;
    }

    public function isEmailUniqueInSupplier(string $email, ?int $excludeContactId = null): bool
    {
        $query = ContactEmail::whereHas('contact', function ($q) {
            $q->where('supplier_id', $this->supplier_id)
              ->where('is_active', true);
        })->where('email', $email);

        if ($excludeContactId) {
            $query->where('contact_id', '!=', $excludeContactId);
        }

        return $query->count() === 0;
    }

    public function isEmailUniqueInEntity(string $email, ?int $excludeContactId = null): bool
    {
        if ($this->client_id) {
            return $this->isEmailUniqueInClient($email, $excludeContactId);
        } elseif ($this->supplier_id) {
            return $this->isEmailUniqueInSupplier($email, $excludeContactId);
        }

        return true;
    }

    // Helper methods
    public function getEntityType(): string
    {
        return $this->client_id ? 'client' : 'supplier';
    }

    public function getEntityId(): int
    {
        return $this->client_id ?: $this->supplier_id;
    }

    public function getEntity()
    {
        return $this->client_id ? $this->client : $this->supplier;
    }
}