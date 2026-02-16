<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'name',
        'type',
        'email',
        'phone',
        'address',
        'siret',
        'sector',
        'website',
        'notes',
        'is_active',
        'created_by'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    /**
     * Generate unique client ID on creation
     */
    protected static function booted()
    {
        static::creating(function ($client) {
            if (empty($client->client_id)) {
                $client->client_id = 'CLI-' . strtoupper(Str::random(10));
            }
        });
    }

    /**
     * Get the user who created this client
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the contacts for this client
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class)->where('is_active', true);
    }

    /**
     * Get the categories assigned to this client
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'client_categories')
                    ->withPivot('assigned_by', 'assigned_at')
                    ->withTimestamps();
    }

    /**
     * Scope for active clients
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the primary contact for this client
     */
    public function primaryContact(): HasMany
    {
        return $this->contacts()->where('is_primary', true);
    }

    /**
     * Get categories by type for this client
     */
    public function categoriesByType($type): BelongsToMany
    {
        return $this->categories()->where('categories.type', $type);
    }

    /**
     * Check if client has a specific category
     */
    public function hasCategory($categoryId): bool
    {
        return $this->categories()->where('category_id', $categoryId)->exists();
    }

    /**
     * Get categories count for this client
     */
    public function getCategoriesCountAttribute(): int
    {
        return $this->categories()->count();
    }

    /**
     * Get contacts count for this client
     */
    public function getContactsCountAttribute(): int
    {
        return $this->contacts()->count();
    }

    /**
     * Get the notes for this client
     */
    public function notes(): HasMany
    {
        return $this->hasMany(ClientNote::class);
    }

    /**
     * Get the call logs for this client
     */
    public function callLogs(): HasMany
    {
        return $this->hasMany(CallLog::class);
    }

    /**
     * Get the appointments for this client
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Get the interactions for this client
     */
    public function interactions(): HasMany
    {
        return $this->hasMany(ClientInteraction::class);
    }

    /**
     * Get the emails for this client
     */
    public function emails(): HasMany
    {
        return $this->hasMany(ClientEmail::class);
    }

    /**
     * Get the opportunities for this client
     */
    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class);
    }

    /**
     * Get the audit logs for this client
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Get the documents for this client
     */
    public function documents(): HasMany
    {
        return $this->hasMany(ClientDocument::class);
    }

    /**
     * Get the custom fields for this client
     */
    public function customFields(): HasMany
    {
        return $this->hasMany(ClientCustomField::class);
    }

    /**
     * Get active custom fields for this client
     */
    public function activeCustomFields(): HasMany
    {
        return $this->customFields()->active()->ordered();
    }

    /**
     * Get custom field by key
     */
    public function getCustomField(string $key): ?ClientCustomField
    {
        return $this->customFields()->byKey($key)->active()->first();
    }

    /**
     * Get custom field value by key
     */
    public function getCustomFieldValue(string $key): mixed
    {
        $field = $this->getCustomField($key);
        return $field ? $field->getValue() : null;
    }

    /**
     * Set custom field value
     */
    public function setCustomField(string $key, $value, string $type = 'text', array $options = []): ClientCustomField
    {
        $field = $this->getCustomField($key);

        if ($field) {
            $field->setValue($value);
            $field->save();
            return $field;
        }

        return ClientCustomField::createField($this->id, $key, $value, $type, $options);
    }

    /**
     * Get documents organized by folders
     */
    public function getDocumentsByFolder(): array
    {
        // Exclure les fichiers placeholder
        $documents = $this->documents()->active()
                         ->where('title', '!=', '.folder_placeholder')
                         ->orderBy('folder_path')
                         ->orderBy('title')
                         ->get();

        $folders = [];
        foreach ($documents as $document) {
            $folderPath = $document->folder_path ?? 'Root';
            if (!isset($folders[$folderPath])) {
                $folders[$folderPath] = [
                    'path' => $folderPath,
                    'name' => $document->folder_name ?? 'Dossier racine',
                    'level' => $document->folder_level ?? 0,
                    'documents' => []
                ];
            }
            $folders[$folderPath]['documents'][] = $document;
        }

        return $folders;
    }

    /**
     * Get folder structure for this client
     */
    public function getFolderStructure(): array
    {
        // Récupérer tous les folder_path uniques des documents réels (pas placeholders)
        $documents = $this->documents()->active()
                         ->where('title', '!=', '.folder_placeholder')
                         ->whereNotNull('folder_path')
                         ->select('folder_path', 'folder_name', 'folder_level')
                         ->distinct()
                         ->orderBy('folder_path')
                         ->get();

        $folders = [];
        foreach ($documents as $document) {
            $path = $document->folder_path;
            $folders[$path] = [
                'path' => $path,
                'name' => $document->folder_name,
                'level' => $document->folder_level,
                'document_count' => $this->documents()->active()
                                        ->where('folder_path', $path)
                                        ->where('title', '!=', '.folder_placeholder')
                                        ->count()
            ];
        }

        return array_values($folders);
    }
}
