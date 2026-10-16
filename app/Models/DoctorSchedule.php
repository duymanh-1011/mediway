<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorSchedule extends Model
{
    protected $fillable = [
        'doctor_id',
        'specialty_id',
        'examination_room_id',
        'work_date',
        'start_time',
        'end_time',
        'status',
        'notes',
    ];

    protected $casts = [
        'work_date' => 'date',
    ];

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

    public function appointmentTimeSlots()
    {
        return $this->hasMany(
            AppointmentTimeSlot::class,
            'doctor_schedule_id'
        );
    }
}