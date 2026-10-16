<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function patient()
    {
        return $this->belongsTo(
            Patient::class,
            'patient_id'
        );
    }

    public function doctor()
    {
        return $this->belongsTo(
            Doctor::class,
            'doctor_id'
        );
    }

    public function specialty()
    {
        return $this->belongsTo(
            Specialty::class,
            'specialty_id'
        );
    }

    public function examinationRoom()
    {
        return $this->belongsTo(
            ExaminationRoom::class,
            'examination_room_id'
        );
    }

    public function appointmentTimeSlot()
    {
        return $this->belongsTo(
            AppointmentTimeSlot::class,
            'appointment_time_slot_id'
        );
    }

    public function appointmentStatus()
    {
        return $this->belongsTo(
            AppointmentStatus::class,
            'appointment_status_id'
        );
    }

    public function medicalVisit(): HasOne
    {
        return $this->hasOne(
            MedicalVisit::class,
            'appointment_id'
        );
    }
}
