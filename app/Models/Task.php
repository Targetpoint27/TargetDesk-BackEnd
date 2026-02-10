<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Carbon\Carbon;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'project_id',
        'parent_task_id',
        'title',
        'description',
        'status',
        'priority',
        'type',
        'estimated_hours',
        'actual_hours',
        'due_date',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'estimated_hours' => 'decimal:2',
        'actual_hours' => 'decimal:2'
    ];

    // Constants pour les statuts
    const STATUS_A_FAIRE = 'a_faire';
    const STATUS_EN_COURS = 'en_cours';
    const STATUS_BLOQUE = 'bloque';
    const STATUS_TEST = 'test';
    const STATUS_TERMINE = 'termine';

    // Constants pour les priorités
    const PRIORITY_BASSE = 'basse';
    const PRIORITY_NORMALE = 'normale';
    const PRIORITY_HAUTE = 'haute';
    const PRIORITY_CRITIQUE = 'critique';

    // Constants pour les types
    const TYPE_DEV = 'dev';
    const TYPE_DESIGN = 'design';
    const TYPE_TEST = 'test';
    const TYPE_ANALYSE = 'analyse';
    const TYPE_AUTRE = 'autre';

    /**
     * Génère un code unique pour la tâche
     */
    public static function generateUniqueCode(): string
    {
        $year = Carbon::now()->year;
        $lastTask = self::where('code', 'like', "TSK-{$year}-%")
            ->orderBy('code', 'desc')
            ->first();

        $nextNumber = 1;
        if ($lastTask) {
            $lastNumber = (int) substr($lastTask->code, -4);
            $nextNumber = $lastNumber + 1;
        }

        return sprintf('TSK-%d-%04d', $year, $nextNumber);
    }

    /**
     * Relations
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function parentTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_task_id');
    }

    public function subTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_task_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TaskAssignment::class);
    }

    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_assignments')
            ->withPivot(['assigned_at', 'assigned_by'])
            ->withTimestamps();
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(TaskTag::class, 'task_tag_relations', 'task_id', 'tag_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class)->orderBy('created_at', 'desc');
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TaskTimeEntry::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(TaskFile::class);
    }

    public function difficulties(): HasMany
    {
        return $this->hasMany(TaskDifficulty::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(TaskHistory::class)->orderBy('created_at', 'desc');
    }

    /**
     * Scopes
     */
    public function scopeAssignedTo($query, $userId)
    {
        return $query->whereHas('assignedUsers', function($q) use ($userId) {
            $q->where('users.id', $userId);
        });
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeByProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->whereNotIn('status', [self::STATUS_TERMINE]);
    }

    public function scopeUrgent($query)
    {
        return $query->where('priority', self::PRIORITY_CRITIQUE)
            ->orWhere(function($q) {
                $q->where('due_date', '<=', now()->addDays(3))
                  ->whereNotIn('status', [self::STATUS_TERMINE]);
            });
    }

    /**
     * Méthodes métier
     */
    public function isOverdue(): bool
    {
        return $this->due_date &&
               $this->due_date->isPast() &&
               $this->status !== self::STATUS_TERMINE;
    }

    public function isUrgent(): bool
    {
        return $this->priority === self::PRIORITY_CRITIQUE ||
               ($this->due_date && $this->due_date->isToday()) ||
               ($this->due_date && $this->due_date->isTomorrow());
    }

    public function canBeEditedBy(User $user): bool
    {
        // Chef de projet peut tout modifier
        if ($user->id === $this->project->project_manager_id) {
            return true;
        }

        // Membres assignés peuvent modifier certains champs
        return $this->assignedUsers->contains($user);
    }

    public function canChangeStatusTo(string $newStatus): array
    {
        $errors = [];

        if ($newStatus === self::STATUS_TERMINE) {
            // Vérifier que du temps a été saisi
            if ($this->timeEntries()->sum('hours') == 0) {
                $errors[] = 'Du temps doit être saisi avant de terminer la tâche';
            }
        }

        return $errors;
    }

    public function updateActualHours(): void
    {
        $totalHours = $this->timeEntries()->sum('hours');
        $this->update(['actual_hours' => $totalHours]);
    }

    public function getProgressPercentage(): float
    {
        if (!$this->estimated_hours || $this->estimated_hours == 0) {
            return 0;
        }

        return min(100, ($this->actual_hours / $this->estimated_hours) * 100);
    }

    public function getProgressColor(): string
    {
        $percentage = $this->getProgressPercentage();

        if ($percentage <= 100) return 'green';
        if ($percentage <= 120) return 'orange';
        return 'red';
    }

    public function getPriorityColor(): string
    {
        return match($this->priority) {
            self::PRIORITY_CRITIQUE => 'red',
            self::PRIORITY_HAUTE => 'orange',
            self::PRIORITY_NORMALE => 'blue',
            self::PRIORITY_BASSE => 'gray',
            default => 'gray'
        };
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            self::STATUS_A_FAIRE => 'gray',
            self::STATUS_EN_COURS => 'blue',
            self::STATUS_BLOQUE => 'red',
            self::STATUS_TEST => 'orange',
            self::STATUS_TERMINE => 'green',
            default => 'gray'
        };
    }

    /**
     * Accesseurs
     */
    public function getCommentsCountAttribute(): int
    {
        return $this->comments()->count();
    }

    public function getFilesCountAttribute(): int
    {
        return $this->files()->count();
    }

    public function getHasActiveDifficultiesAttribute(): bool
    {
        return $this->difficulties()
            ->whereIn('status', ['open', 'in_progress'])
            ->exists();
    }
}
