<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'action_type',
        'field_changed',
        'old_value',
        'new_value',
        'comment',
        'metadata',
        'changed_by'
    ];

    protected $casts = [
        'metadata' => 'array'
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
