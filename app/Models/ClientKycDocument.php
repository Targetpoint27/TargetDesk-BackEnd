<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ClientKycDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'document_type',
        'original_name',
        'file_path',
        'mime_type',
        'file_size',
        'can_preview',
        'uploaded_at',
        'uploaded_by'
    ];

    protected $casts = [
        'can_preview' => 'boolean',
        'uploaded_at' => 'datetime',
        'file_size' => 'integer'
    ];

    /**
     * Document types disponibles
     */
    const DOCUMENT_TYPES = [
        'kbis' => 'KBIS',
        'dlabe' => 'DLABE',
        'legal_representative_id_recto_verso' => 'Copie document représentant légal recto/verso',
        'accommodation_certificate' => 'Attestation d\'hébergement',
        'beneficial_owner_id_recto_verso' => 'Copie document bénéficiaire effectif recto/verso',
        'address_proof' => 'Justificatif de domicile',
        'beneficial_accommodation_certificate' => 'Attestation d\'hébergement bénéficiaire',
        'bank_identity_statement' => 'Relevé d\'identité bancaire'
    ];

    /**
     * Get the client that owns this document
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the user who uploaded this document
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Check if the document can be previewed
     */
    public function canPreview(): bool
    {
        $previewableMimes = [
            'application/pdf',
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp'
        ];

        return in_array($this->mime_type, $previewableMimes);
    }

    /**
     * Get the full file path
     */
    public function getFullPath(): string
    {
        return Storage::path($this->file_path);
    }

    /**
     * Get human-readable file size
     */
    public function getHumanFileSizeAttribute(): string
    {
        $size = $this->file_size;
        if ($size < 1024) return $size . ' B';
        if ($size < 1048576) return round($size / 1024, 2) . ' KB';
        if ($size < 1073741824) return round($size / 1048576, 2) . ' MB';
        return round($size / 1073741824, 2) . ' GB';
    }

    /**
     * Get document type label
     */
    public function getDocumentTypeLabelAttribute(): string
    {
        return self::DOCUMENT_TYPES[$this->document_type] ?? $this->document_type;
    }
}
