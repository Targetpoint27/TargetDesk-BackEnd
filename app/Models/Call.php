<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Call extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'call_id',
        'type',
        'phone_number',
        'caller_name',
        'department_id',
        'object',
        'summary',
        'urgency',
        'status',
        'outbound_reason',
        'call_result',
        'call_duration_seconds',
        'scheduled_callback_date',
        'scheduled_callback_time',
        'callback_reason',
        'callback_notes',
        'callback_attempts',
        'last_callback_at',
        'client_id',
        'contact_id',
        'related_to_type',
        'related_to_id',
        'assigned_to',
        'created_by',
        'resolution_summary',
        'final_result',
        'closed_at',
        'closed_by',
        'treatment_time_seconds',
        'reopened_at',
        'reopen_reason',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'scheduled_callback_date' => 'date',
        'last_callback_at' => 'datetime',
        'closed_at' => 'datetime',
        'reopened_at' => 'datetime',
    ];

    protected $dates = [
        'last_callback_at',
        'closed_at',
        'reopened_at',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function assignedAgent()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function closer()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function notes()
    {
        return $this->hasMany(CallNote::class);
    }

    public static function generateCallId()
    {
        $year = date('Y');
        $lastCall = self::where('call_id', 'LIKE', "CALL-{$year}-%")
                        ->orderBy('call_id', 'desc')
                        ->first();

        if ($lastCall) {
            $lastNumber = (int) substr($lastCall->call_id, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "CALL-{$year}-{$newNumber}";
    }
}