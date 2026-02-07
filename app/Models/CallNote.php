<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CallNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'call_id',
        'note',
        'is_important',
        'created_by',
    ];

    protected $casts = [
        'is_important' => 'boolean',
    ];

    public function call()
    {
        return $this->belongsTo(Call::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}