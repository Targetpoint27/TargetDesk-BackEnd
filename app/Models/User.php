<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'password',
        'status',
        'phone',
        'department',
        'last_login',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login' => 'datetime',
        'status' => 'string',
    ];


    /**
     * Get the roles assigned to this user
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')
                    ->withPivot('assigned_by', 'assigned_at', 'effective_from', 'effective_until', 'is_active')
                    ->withTimestamps()
                    ->wherePivot('is_active', true);
    }

    /**
     * Get all roles including inactive ones
     */
    public function allRoles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')
                    ->withPivot('assigned_by', 'assigned_at', 'effective_from', 'effective_until', 'is_active')
                    ->withTimestamps();
    }

    /**
     * Get the team view sessions for this user
     */
    public function teamViewSessions(): HasMany
    {
        return $this->hasMany(TeamViewSession::class);
    }

    /**
     * Get the notification preferences for this user
     */
    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(UserNotificationPreference::class);
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole($role): bool
    {
        if (is_string($role)) {
            return $this->roles()->where('roles.name', $role)->exists();
        }

        return $this->roles()->where('roles.id', $role->id)->exists();
    }

    /**
     * Check if user has any of the given roles
     */
    public function hasAnyRole(array $roles): bool
    {
        $roleNames = collect($roles)->map(function ($role) {
            return is_string($role) ? $role : $role->name;
        })->toArray();

        return $this->roles()->whereIn('name', $roleNames)->exists();
    }

    /**
     * Check if user has all the given roles
     */
    public function hasAllRoles(array $roles): bool
    {
        $roleNames = collect($roles)->map(function ($role) {
            return is_string($role) ? $role : $role->name;
        })->toArray();

        return $this->roles()->whereIn('name', $roleNames)->count() === count($roleNames);
    }

    /**
     * Check if user has a specific permission
     */
    public function hasPermission($permission): bool
    {
        return $this->getAllPermissions()->contains(function ($p) use ($permission) {
            return is_string($permission) ? $p->name === $permission : $p->id === $permission->id;
        });
    }

    /**
     * Get all permissions for this user through their roles
     */
    public function getAllPermissions()
    {
        return $this->roles()
                    ->with('permissions')
                    ->get()
                    ->pluck('permissions')
                    ->flatten()
                    ->unique('id');
    }

    /**
     * Get permissions grouped by module
     */
    public function getPermissionsByModule(): array
    {
        return $this->getAllPermissions()
                    ->groupBy('module')
                    ->map(function ($permissions) {
                        return $permissions->keyBy('action');
                    })
                    ->toArray();
    }

    /**
     * Assign a role to the user
     */
    public function assignRole($role, $assignedBy = null, $effectiveFrom = null, $effectiveUntil = null): void
    {
        if (is_string($role)) {
            $roleModel = Role::where('name', $role)->first();
            if (!$roleModel) {
                throw new \Exception("Role '{$role}' not found");
            }
            $roleId = $roleModel->id;
        } else {
            $roleId = $role->id;
        }

        $this->roles()->attach($roleId, [
            'assigned_by' => $assignedBy ?? auth()->id() ?? $this->id,
            'assigned_at' => now(),
            'effective_from' => $effectiveFrom,
            'effective_until' => $effectiveUntil,
            'is_active' => true
        ]);
    }

    /**
     * Remove a role from the user
     */
    public function removeRole($role): void
    {
        $roleId = is_string($role) ? Role::where('name', $role)->first()->id : $role->id;
        $this->roles()->detach($roleId);
    }

    /**
     * Sync roles for the user
     */
    public function syncRoles(array $roles): void
    {
        $this->roles()->detach();

        foreach ($roles as $role) {
            $this->assignRole($role);
        }
    }

    /**
     * Check if user can access resource based on roles and permissions
     */
    public function canAccess($module, $action, $scope = 'global'): bool
    {
        $permissionName = "{$module}.{$action}";

        if ($scope !== 'global') {
            $permissionName .= ".{$scope}";
        }

        return $this->hasPermission($permissionName);
    }

    /**
     * Get active team view session
     */
    public function getActiveTeamViewSession()
    {
        return $this->teamViewSessions()
                    ->active()
                    ->first();
    }

    /**
     * Check if user is admin or super admin
     */
    public function isAdmin(): bool
    {
        return $this->hasAnyRole(['admin', 'super_admin']);
    }

    /**
     * Check if user is super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    /**
     * Check if user is manager
     */
    public function isManager(): bool
    {
        return $this->hasRole('manager');
    }

    /**
     * Get user's role names as array
     */
    public function getRoleNames(): array
    {
        return $this->roles()->pluck('name')->toArray();
    }
}
