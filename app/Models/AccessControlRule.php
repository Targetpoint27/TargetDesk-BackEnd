<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessControlRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'rule_type',
        'conditions',
        'target_users',
        'target_roles',
        'scope',
        'priority',
        'is_active',
        'created_by'
    ];

    protected $casts = [
        'conditions' => 'array',
        'target_users' => 'array',
        'target_roles' => 'array',
        'is_active' => 'boolean'
    ];

    /**
     * Get the user who created this rule
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope for active rules
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for rules by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('rule_type', $type);
    }

    /**
     * Scope for rules by scope
     */
    public function scopeByScope($query, $scope)
    {
        return $query->where('scope', $scope);
    }

    /**
     * Get rules ordered by priority
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    /**
     * Check if rule applies to a user
     */
    public function appliesTo($userId, $userRoles = []): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // Check if user is specifically targeted
        if ($this->target_users && in_array($userId, $this->target_users)) {
            return true;
        }

        // Check if user's roles are targeted
        if ($this->target_roles && !empty(array_intersect($userRoles, $this->target_roles))) {
            return true;
        }

        return false;
    }

    /**
     * Evaluate rule conditions
     */
    public function evaluateConditions($context = []): bool
    {
        if (empty($this->conditions)) {
            return true;
        }

        // Simple condition evaluation logic
        foreach ($this->conditions as $condition) {
            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? 'equals';
            $value = $condition['value'] ?? null;
            $contextValue = $context[$field] ?? null;

            switch ($operator) {
                case 'equals':
                    if ($contextValue !== $value) return false;
                    break;
                case 'not_equals':
                    if ($contextValue === $value) return false;
                    break;
                case 'in':
                    if (!in_array($contextValue, (array)$value)) return false;
                    break;
                case 'not_in':
                    if (in_array($contextValue, (array)$value)) return false;
                    break;
                default:
                    return false;
            }
        }

        return true;
    }
}
