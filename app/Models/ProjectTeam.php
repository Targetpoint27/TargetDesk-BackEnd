<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectTeam extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'user_id',
        'role',
        'hourly_rate',
        'is_active',
        'added_by',
        'joined_at',
        'left_at'
    ];

    protected $casts = [
        'hourly_rate' => 'decimal:2',
        'is_active' => 'boolean',
        'joined_at' => 'datetime',
        'left_at' => 'datetime'
    ];

    const ROLE_DEVELOPPEUR = 'developeur';
    const ROLE_DESIGNER = 'designer';
    const ROLE_TESTEUR = 'testeur';
    const ROLE_ANALYSTE = 'analyste';
    const ROLE_AUTRE = 'autre';

    public static function getRoles(): array
    {
        return [
            self::ROLE_DEVELOPPEUR => 'Développeur',
            self::ROLE_DESIGNER => 'Designer',
            self::ROLE_TESTEUR => 'Testeur',
            self::ROLE_ANALYSTE => 'Analyste',
            self::ROLE_AUTRE => 'Autre'
        ];
    }

    /**
     * Relations
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Retire un membre de l'équipe
     */
    public function removeMember(): void
    {
        $this->update([
            'is_active' => false,
            'left_at' => now()
        ]);
    }

    /**
     * Réactive un membre dans l'équipe
     */
    public function reactivateMember(): void
    {
        $this->update([
            'is_active' => true,
            'left_at' => null,
            'joined_at' => now()
        ]);
    }
}
