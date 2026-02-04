<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'is_predefined',
        'is_active',
        'created_by'
    ];

    protected $casts = [
        'is_predefined' => 'boolean',
        'is_active' => 'boolean'
    ];

    /**
     * Get the user who created this role
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the permissions for this role
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')
                    ->withPivot('granted_by', 'granted_at')
                    ->withTimestamps();
    }

    /**
     * Get the users assigned to this role
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles')
                    ->withPivot('assigned_by', 'assigned_at', 'effective_from', 'effective_until', 'is_active')
                    ->withTimestamps();
    }

    /**
     * Scope for active roles
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for predefined roles
     */
    public function scopePredefined($query)
    {
        return $query->where('is_predefined', true);
    }

    /**
     * Scope for custom roles
     */
    public function scopeCustom($query)
    {
        return $query->where('is_predefined', false);
    }

    /**
     * Check if role has a specific permission
     */
    public function hasPermission($permission): bool
    {
        if (is_string($permission)) {
            return $this->permissions()->where('name', $permission)->exists();
        }

        return $this->permissions()->where('id', $permission->id)->exists();
    }

    /**
     * Get permissions grouped by module
     */
    public function getPermissionsByModule(): array
    {
        return $this->permissions()
                    ->select(['module', 'action', 'scope', 'name', 'display_name'])
                    ->get()
                    ->groupBy('module')
                    ->toArray();
    }

    /**
     * Check if role can be deleted
     */
    public function canBeDeleted(): bool
    {
        // Predefined roles cannot be deleted
        if ($this->is_predefined) {
            return false;
        }

        // Roles with assigned users cannot be deleted
        return $this->users()->count() === 0;
    }
}
