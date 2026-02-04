<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'module',
        'action',
        'scope',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    /**
     * Get the roles that have this permission
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions')
                    ->withPivot('granted_by', 'granted_at')
                    ->withTimestamps();
    }

    /**
     * Scope for active permissions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for permissions by module
     */
    public function scopeByModule($query, $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Scope for permissions by action
     */
    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope for permissions by scope
     */
    public function scopeByScope($query, $scope)
    {
        return $query->where('scope', $scope);
    }

    /**
     * Get all available modules
     */
    public static function getModules(): array
    {
        return self::select('module')
                   ->distinct()
                   ->orderBy('module')
                   ->pluck('module')
                   ->toArray();
    }

    /**
     * Get all available actions
     */
    public static function getActions(): array
    {
        return self::select('action')
                   ->distinct()
                   ->orderBy('action')
                   ->pluck('action')
                   ->toArray();
    }

    /**
     * Get all available scopes
     */
    public static function getScopes(): array
    {
        return self::select('scope')
                   ->distinct()
                   ->orderBy('scope')
                   ->pluck('scope')
                   ->toArray();
    }

    /**
     * Get permissions grouped by module
     */
    public static function getByModules(): array
    {
        return self::active()
                   ->orderBy('module')
                   ->orderBy('action')
                   ->orderBy('scope')
                   ->get()
                   ->groupBy('module')
                   ->toArray();
    }

    /**
     * Check if permission exists
     */
    public static function exists($module, $action, $scope = 'global'): bool
    {
        return self::where('module', $module)
                   ->where('action', $action)
                   ->where('scope', $scope)
                   ->exists();
    }
}
