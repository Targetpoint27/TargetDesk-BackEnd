<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'parent_id',
        'color',
        'type',
        'is_active',
        'created_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Types de catégories possibles
    public const TYPES = [
        'secteur' => 'Secteur',
        'taille' => 'Taille',
        'priorite' => 'Priorité',
        'origine' => 'Origine',
        'personnalisee' => 'Personnalisée'
    ];

    // Relation avec le parent (hiérarchisation)
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    // Relation avec les enfants
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')
                    ->where('is_active', true)
                    ->orderBy('name');
    }

    // Relation avec les enfants récursive
    public function allChildren(): HasMany
    {
        return $this->children()->with('allChildren');
    }

    // Relation avec les clients
    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(Client::class, 'client_categories')
                    ->withPivot('assigned_by', 'assigned_at')
                    ->withTimestamps();
    }

    // Relation avec le créateur
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scope pour les catégories actives
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope pour les catégories racines (sans parent)
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    // Scope par type
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Méthode pour obtenir le chemin complet de la catégorie
    public function getFullPathAttribute(): string
    {
        $path = [$this->name];
        $parent = $this->parent;

        while ($parent) {
            array_unshift($path, $parent->name);
            $parent = $parent->parent;
        }

        return implode(' > ', $path);
    }

    // Méthode pour vérifier si la catégorie a des enfants
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    // Méthode pour obtenir le niveau de hiérarchie
    public function getDepthLevel(): int
    {
        $level = 0;
        $parent = $this->parent;

        while ($parent) {
            $level++;
            $parent = $parent->parent;
        }

        return $level;
    }

    // Méthode pour obtenir tous les descendants
    public function getAllDescendants()
    {
        $descendants = collect();

        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->getAllDescendants());
        }

        return $descendants;
    }

    // Validation pour éviter les références circulaires
    public function canHaveParent(Category $potentialParent): bool
    {
        // Ne peut pas être son propre parent
        if ($potentialParent->id === $this->id) {
            return false;
        }

        // Ne peut pas avoir un de ses descendants comme parent
        $descendants = $this->getAllDescendants();
        return !$descendants->contains('id', $potentialParent->id);
    }

    // Méthode pour obtenir le type formaté
    public function getFormattedTypeAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
