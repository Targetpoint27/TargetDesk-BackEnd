<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CallMotif extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'label',
        'category',
        'parent_id',
        'department_id',
        'sla_hours',
        'suggested_script',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sla_hours' => 'integer',
        'display_order' => 'integer',
    ];

    // Relationship: Parent motif
    public function parent()
    {
        return $this->belongsTo(CallMotif::class, 'parent_id');
    }

    // Relationship: Child motifs
    public function children()
    {
        return $this->hasMany(CallMotif::class, 'parent_id');
    }

    // Relationship: Department
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    // Scope: Active motifs only
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope: Root level motifs only (no parent)
    public function scopeRootOnly($query)
    {
        return $query->whereNull('parent_id');
    }

    // Scope: Ordered by display_order then label
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('label');
    }
}