<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentTimeSlot extends Model
{
    protected $fillable = [
        'doctor_schedule_id',
        'start_time',
        'end_time',
        'max_patients',
        'status',
        'notes',
    ];

    protected $casts = [
        'max_patients' => 'integer',
    ];

    public function doctorSchedule()
    {
        return $this->belongsTo(
            DoctorSchedule::class,
            'doctor_schedule_id'
        );
    }

    public function appointments()
    {
        return $this->hasMany(
            Appointment::class,
            'appointment_time_slot_id'
        );
    }
}
