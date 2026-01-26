<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id', 'user_id', 'auditable_type', 'auditable_id', 'action',
        'field_name', 'old_value', 'new_value', 'changes', 'ip_address', 'user_agent'
    ];

    protected $casts = [
        'changes' => 'array'
    ];

    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function interaction(): MorphOne { return $this->morphOne(ClientInteraction::class, 'reference'); }

    protected static function boot()
    {
        parent::boot();
        static::created(function ($audit) { $audit->createInteraction(); });
    }

    public function createInteraction()
    {
        // Only create timeline entries for significant changes
        if ($this->client_id && in_array($this->action, ['created', 'deleted'])) {
            $this->interaction()->create([
                'client_id' => $this->client_id,
                'user_id' => $this->user_id,
                'type' => 'modification',
                'title' => $this->getActionTitle(),
                'summary' => $this->getActionSummary(),
                'importance_level' => 'low',
                'privacy_level' => 'public',
                'occurred_at' => $this->created_at,
                'metadata' => [
                    'action' => $this->action,
                    'auditable_type' => $this->auditable_type,
                    'auditable_id' => $this->auditable_id,
                    'field_name' => $this->field_name
                ]
            ]);
        }
    }

    private function getActionTitle(): string
    {
        $entityName = class_basename($this->auditable_type);
        return match($this->action) {
            'created' => "Création d'un(e) {$entityName}",
            'deleted' => "Suppression d'un(e) {$entityName}",
            'updated' => "Modification d'un(e) {$entityName}",
            default => "Action sur {$entityName}"
        };
    }

    private function getActionSummary(): string
    {
        $entityName = class_basename($this->auditable_type);
        $summary = "L'utilisateur a {$this->action} {$entityName} #{$this->auditable_id}";

        if ($this->field_name) {
            $summary .= " (champ: {$this->field_name})";
        }

        return $summary;
    }
}
