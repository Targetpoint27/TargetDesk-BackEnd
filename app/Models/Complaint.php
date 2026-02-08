<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Complaint extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'complaint_id', 'call_id', 'client_id', 'category', 'severity',
        'status', 'sla_deadline', 'description', 'actions_taken',
        'root_cause', 'proposed_solution', 'compensation_details',
        'prevention_measures', 'resolution_summary', 'client_satisfaction',
        'resolved_at', 'resolved_by', 'closing_comment', 'closed_at',
        'closed_by', 'created_by', 'assigned_to'
    ];

    protected $casts = [
        'sla_deadline' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    // ✅ Helper to generate unique REC-YYYY-XXXX ID
    public static function generateComplaintId()
    {
        $year = date('Y');
        $lastComplaint = self::where('complaint_id', 'like', "REC-{$year}-%")
            ->orderBy('id', 'desc')
            ->first();

        if (!$lastComplaint) {
            $number = 1;
        } else {
            // Extract the number part and increment
            $parts = explode('-', $lastComplaint->complaint_id);
            $number = intval(end($parts)) + 1;
        }

        return 'REC-' . $year . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    // Relationships
    public function call() { return $this->belongsTo(Call::class); }
    public function client() { return $this->belongsTo(Client::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function assignedAgent() { return $this->belongsTo(User::class, 'assigned_to'); }
    public function resolver() { return $this->belongsTo(User::class, 'resolved_by'); }
    public function closer() { return $this->belongsTo(User::class, 'closed_by'); }
}