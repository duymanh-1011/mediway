<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    public function scopeWithQualifiedDoctor(Builder $query): Builder
    {
        return $query->whereHas('doctor', fn (Builder $doctor) => $doctor
            ->whereHas('specialty', fn (Builder $specialty) => $specialty->where('status', 'ACTIVE'))
            ->where(fn (Builder $eligible) => $eligible->whereColumn('doctors.specialty_id', 'doctor_schedules.specialty_id')
                ->orWhereHas('specialty', fn (Builder $specialty) => $specialty->where('is_general', true))));
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

    public function appointmentTimeSlots()
    {
        return $this->hasMany(
            AppointmentTimeSlot::class,
            'doctor_schedule_id'
        );
    }
}
