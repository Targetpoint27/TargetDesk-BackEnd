<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'name',
        'type',
        'email',
        'phone',
        'address',
        'siret',
        'sector',
        'website',
        'notes',
        'relation_type',
        'payment_terms',
        'delivery_delay',
        'currency',
        'is_active',
        'created_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'delivery_delay' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $attributes = [
        'is_active' => true,
        'currency' => 'EUR',
        'relation_type' => 'fournisseur'
    ];

    // Boot method pour générer l'ID automatiquement
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($supplier) {
            if (empty($supplier->supplier_id)) {
                $supplier->supplier_id = self::generateSupplierId();
            }
        });
    }

    /**
     * Génère un ID unique pour le fournisseur
     */
    public static function generateSupplierId(): string
    {
        do {
            // Génère un ID au format FOUR-XXXXX
            $randomString = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8));
            $supplierId = 'FOUR-' . $randomString;
        } while (self::where('supplier_id', $supplierId)->exists());

        return $supplierId;
    }

    /**
     * Relation avec l'utilisateur qui a créé le fournisseur
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relation avec les contacts du fournisseur
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class)->where('is_active', true);
    }

    /**
     * Scope pour les fournisseurs actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope par type de relation
     */
    public function scopeByRelationType($query, string $type)
    {
        return $query->where('relation_type', $type);
    }

    /**
     * Vérifie si le fournisseur est aussi client
     */
    public function isClientAndSupplier(): bool
    {
        return $this->relation_type === 'client_et_fournisseur';
    }

    /**
     * Obtient le nom d'affichage formaté
     */
    public function getDisplayName(): string
    {
        return $this->name . ' (' . $this->supplier_id . ')';
    }

    /**
     * Obtient le délai de livraison formaté
     */
    public function getFormattedDeliveryDelay(): ?string
    {
        if (!$this->delivery_delay) {
            return null;
        }

        return $this->delivery_delay . ' jour' . ($this->delivery_delay > 1 ? 's' : '');
    }

    /**
     * Get the primary contact for this supplier
     */
    public function primaryContact(): HasMany
    {
        return $this->contacts()->where('is_primary', true);
    }

    /**
     * Get contacts count for this supplier
     */
    public function getContactsCountAttribute(): int
    {
        return $this->contacts()->count();
    }

    /**
     * Relation avec les documents du fournisseur
     */
    public function documents(): HasMany
    {
        return $this->hasMany(SupplierDocument::class);
    }
}
