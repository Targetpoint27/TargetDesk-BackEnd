<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskDifficulty extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'user_id',
        'type',
        'title',
        'description',
        'severity',
        'status',
        'resolved_at',
        'resolved_by',
        'resolution_notes'
    ];

    protected $casts = [
        'resolved_at' => 'datetime'
    ];

    public function task(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resolver(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
