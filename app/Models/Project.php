<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'objectives',
        'estimated_budget',
        'actual_budget',
        'start_date',
        'planned_end_date',
        'actual_end_date',
        'status',
        'progress_percentage',
        'profitability_indicator',
        'risk_indicator',
        'client_type',
        'client_id',
        'external_client_info',
        'project_manager_id',
        'department',
        'created_by',
        'updated_by'
    ];

    protected $hidden = [
        '_oldValues'
    ];

    protected $casts = [
        'start_date' => 'date',
        'planned_end_date' => 'date',
        'actual_end_date' => 'date',
        'estimated_budget' => 'decimal:2',
        'actual_budget' => 'decimal:2',
        'progress_percentage' => 'integer',
        'external_client_info' => 'array'
    ];

    const STATUS_EN_COURS = 'en_cours';
    const STATUS_EN_ATTENTE = 'en_attente';
    const STATUS_EN_DANGER = 'en_danger';
    const STATUS_TERMINE = 'termine';
    const STATUS_ANNULE = 'annule';

    const PROFITABILITY_GREEN = 'green';
    const PROFITABILITY_ORANGE = 'orange';
    const PROFITABILITY_RED = 'red';

    const RISK_LOW = 'low';
    const RISK_MEDIUM = 'medium';
    const RISK_HIGH = 'high';

    /**
     * Génère un code unique pour le projet
     */
    public static function generateUniqueCode(): string
    {
        $year = Carbon::now()->year;
        $lastProject = self::where('code', 'like', "PRJ-{$year}-%")
            ->orderBy('code', 'desc')
            ->first();

        $nextNumber = 1;
        if ($lastProject) {
            $lastNumber = (int) substr($lastProject->code, -4);
            $nextNumber = $lastNumber + 1;
        }

        return sprintf('PRJ-%d-%04d', $year, $nextNumber);
    }

    /**
     * Relations
     */
    public function projectManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'project_manager_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function teamMembers(): HasMany
    {
        return $this->hasMany(ProjectTeam::class)->where('is_active', true);
    }

    public function allTeamMembers(): HasMany
    {
        return $this->hasMany(ProjectTeam::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ProjectHistory::class)->orderBy('created_at', 'desc');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Scopes
     */
    public function scopeMyProjects($query, $userId)
    {
        return $query->where(function($q) use ($userId) {
            $q->where('project_manager_id', $userId)
              ->orWhereHas('teamMembers', function($team) use ($userId) {
                  $team->where('user_id', $userId);
              });
        });
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            self::STATUS_EN_COURS,
            self::STATUS_EN_ATTENTE,
            self::STATUS_EN_DANGER
        ]);
    }

    /**
     * Calcule le pourcentage d'avancement basé sur les tâches
     * (Placeholder - sera connecté au système de tâches existant)
     */
    public function calculateProgress(): int
    {
        // TODO: Connecter au système de tâches existant
        // Pour l'instant, retourne la valeur stockée
        return $this->progress_percentage;
    }

    /**
     * Met à jour le pourcentage d'avancement
     */
    public function updateProgress(int $percentage): void
    {
        $this->update(['progress_percentage' => min(100, max(0, $percentage))]);
    }

    /**
     * Détermine la couleur de la jauge d'avancement
     */
    public function getProgressColorAttribute(): string
    {
        if ($this->progress_percentage > 75) return 'green';
        if ($this->progress_percentage >= 50) return 'orange';
        return 'red';
    }

    /**
     * Vérifie si le projet peut être terminé
     */
    public function canBeCompleted(): bool
    {
        // TODO: Vérifier que toutes les tâches sont terminées
        // Pour l'instant, toujours true
        return true;
    }

    /**
     * Vérifie si l'utilisateur peut modifier le projet
     */
    public function canBeEditedBy(User $user): bool
    {
        return $user->id === $this->project_manager_id
            || $user->hasRole(['super_admin', 'admin']);
    }

    /**
     * Retourne les indicateurs pour le dashboard
     */
    public function getDashboardIndicators(): array
    {
        return [
            'progress' => [
                'percentage' => $this->progress_percentage,
                'color' => $this->progress_color
            ],
            'profitability' => $this->profitability_indicator,
            'risk' => $this->risk_indicator,
            'status' => $this->status
        ];
    }

    /**
     * Retourne les informations du client (interne ou externe)
     */
    public function getClientInfoAttribute(): array
    {
        if ($this->client_type === 'interne' && $this->client_id) {
            return [
                'type' => 'interne',
                'id' => $this->client->id,
                'name' => $this->client->name,
                'email' => $this->client->email,
                'phone' => $this->client->phone,
                'sector' => $this->client->sector,
            ];
        }

        if ($this->client_type === 'externe' && $this->external_client_info) {
            return [
                'type' => 'externe',
                'name' => $this->external_client_info['name'] ?? null,
                'email' => $this->external_client_info['email'] ?? null,
                'phone' => $this->external_client_info['phone'] ?? null,
                'company' => $this->external_client_info['company'] ?? null,
                'address' => $this->external_client_info['address'] ?? null,
            ];
        }

        return ['type' => null];
    }

    /**
     * Définit un client interne pour le projet
     */
    public function setInternalClient(Client $client): void
    {
        $this->update([
            'client_type' => 'interne',
            'client_id' => $client->id,
            'external_client_info' => null
        ]);
    }

    /**
     * Définit un client externe pour le projet
     */
    public function setExternalClient(array $clientInfo): void
    {
        $this->update([
            'client_type' => 'externe',
            'client_id' => null,
            'external_client_info' => $clientInfo
        ]);
    }
}
