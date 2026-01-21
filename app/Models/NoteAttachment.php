<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NoteAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'note_id', 'filename', 'original_filename', 'path', 'size', 'mime_type'
    ];

    public function note(): BelongsTo { return $this->belongsTo(ClientNote::class, 'note_id'); }
}
