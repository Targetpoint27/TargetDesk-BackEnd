<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactEmail extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_id',
        'email',
        'type',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    // Constants for email types
    public const TYPE_PROFESSIONAL = 'professionnel';
    public const TYPE_PERSONAL = 'personnel';

    public static function getTypes(): array
    {
        return [
            self::TYPE_PROFESSIONAL,
            self::TYPE_PERSONAL,
        ];
    }

    // Relations
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    // Scopes
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // Business methods
    public function makePrimary(): bool
    {
        if ($this->is_primary) {
            return true; // Already primary
        }

        // Remove primary status from other emails of the same contact
        static::where('contact_id', $this->contact_id)
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);

        // Set this email as primary
        $this->update(['is_primary' => true]);

        return true;
    }
}