<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CallStatusHistory extends Model
{
    use HasFactory;

    public $timestamps = true;
    const UPDATED_AT = null;

    protected $table = 'call_status_history';

    protected $fillable = [
        'call_id',
        'old_status',
        'new_status',
        'comment',
        'changed_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function call()
    {
        return $this->belongsTo(Call::class);
    }

    public function changer()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}