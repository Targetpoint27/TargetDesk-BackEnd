<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * @OA\Schema(
 *     schema="SupplierDocument",
 *     title="Document Fournisseur",
 *     description="Document attaché à un fournisseur",
 *     @OA\Property(property="id", type="integer", description="ID du document"),
 *     @OA\Property(property="supplier_id", type="integer", description="ID du fournisseur"),
 *     @OA\Property(property="title", type="string", description="Titre du document", maxLength=255),
 *     @OA\Property(property="description", type="string", description="Description du document"),
 *     @OA\Property(property="category", type="string", enum={"contrat", "devis", "facture", "autre"}, description="Catégorie du document"),
 *     @OA\Property(property="original_name", type="string", description="Nom original du fichier"),
 *     @OA\Property(property="mime_type", type="string", description="Type MIME du fichier"),
 *     @OA\Property(property="file_size", type="integer", description="Taille du fichier en bytes"),
 *     @OA\Property(property="file_extension", type="string", description="Extension du fichier"),
 *     @OA\Property(property="version", type="integer", description="Version du document"),
 *     @OA\Property(property="document_key", type="string", description="Clé de regroupement des versions"),
 *     @OA\Property(property="is_active", type="boolean", description="Document actif"),
 *     @OA\Property(property="uploaded_by", type="integer", description="ID de l'utilisateur qui a uploadé"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Date de création"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Date de modification"),
 *     @OA\Property(property="last_accessed_at", type="string", format="date-time", description="Dernier accès"),
 * )
 */
class SupplierDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'title',
        'description',
        'category',
        'original_name',
        'file_path',
        'mime_type',
        'file_size',
        'file_extension',
        'version',
        'document_key',
        'metadata',
        'is_active',
        'uploaded_by',
        'last_accessed_at'
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
        'file_size' => 'integer',
        'version' => 'integer',
        'last_accessed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $appends = [
        'formatted_size',
        'download_url',
        'preview_url',
        'can_preview'
    ];

    // Relations
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // Accessors
    public function getFormattedSizeAttribute(): string
    {
        return $this->formatBytes($this->file_size);
    }

    public function getDownloadUrlAttribute(): string
    {
        return route('suppliers.documents.download', [
            'supplier' => $this->supplier_id,
            'document' => $this->id
        ]);
    }

    public function getPreviewUrlAttribute(): ?string
    {
        if ($this->can_preview) {
            return route('suppliers.documents.preview', [
                'supplier' => $this->supplier_id,
                'document' => $this->id
            ]);
        }
        return null;
    }

    public function getCanPreviewAttribute(): bool
    {
        $previewableMimes = [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp'
        ];

        return in_array($this->mime_type, $previewableMimes);
    }

    // Méthodes
    public function markAsAccessed(): void
    {
        $this->update(['last_accessed_at' => now()]);
    }

    public function getFileUrl(): string
    {
        return Storage::url($this->file_path);
    }

    public function exists(): bool
    {
        return Storage::exists($this->file_path);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeLatestVersions($query)
    {
        return $query->where('is_active', true)
                    ->whereIn('id', function($subQuery) {
                        $subQuery->selectRaw('MAX(id)')
                                ->from('supplier_documents')
                                ->where('is_active', true)
                                ->groupBy('document_key');
                    });
    }

    public function scopeVersionsOf($query, string $documentKey)
    {
        return $query->where('document_key', $documentKey)
                    ->orderBy('version', 'desc');
    }

    // Méthodes statiques
    public static function getAllowedMimeTypes(): array
    {
        return [
            // PDF
            'application/pdf',
            // Documents Word
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            // Excel
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            // PowerPoint
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            // Images
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            // Text
            'text/plain',
            'text/csv'
        ];
    }

    public static function getAllowedExtensions(): array
    {
        return ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'txt', 'csv'];
    }

    public static function getMaxFileSize(): int
    {
        return config('filesystems.max_file_size', 10485760); // 10MB par défaut
    }

    // Méthodes utilitaires
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}