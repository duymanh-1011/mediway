<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $fillable = [
        'appointment_code',
        'patient_id',
        'doctor_id',
        'specialty_id',
        'examination_room_id',
        'appointment_date',
        'appointment_time_slot_id',
        'appointment_status_id',
        'checkin_token',
        'reason_for_visit',
        'patient_notes',
        'booked_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'booked_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];
}