<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_id', 'contact_id', 'email', 'name', 'status'
    ];

    public function appointment(): BelongsTo { return $this->belongsTo(Appointment::class); }
    public function contact(): BelongsTo { return $this->belongsTo(Contact::class); }
}
