<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactPhone extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_id',
        'phone',
        'type',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    // Constants for phone types
    public const TYPE_OFFICE = 'bureau';
    public const TYPE_MOBILE = 'mobile';
    public const TYPE_FAX = 'fax';
    public const TYPE_OTHER = 'autre';

    public static function getTypes(): array
    {
        return [
            self::TYPE_OFFICE,
            self::TYPE_MOBILE,
            self::TYPE_FAX,
            self::TYPE_OTHER,
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

        // Remove primary status from other phones of the same contact
        static::where('contact_id', $this->contact_id)
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);

        // Set this phone as primary
        $this->update(['is_primary' => true]);

        return true;
    }
}