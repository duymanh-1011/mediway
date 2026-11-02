<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\CarbonImmutable;

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

    public function hasEnded(): bool
    {
        $date = $this->doctorSchedule?->work_date;
        return $date && $this->end_time
            && now()->greaterThanOrEqualTo(CarbonImmutable::parse($date->format('Y-m-d').' '.$this->end_time, config('app.timezone')));
    }

    public function effectiveStatus(): string
    {
        return $this->status === 'ACTIVE' && $this->doctorSchedule?->effectiveStatus() === 'ACTIVE' && ! $this->hasEnded()
            ? 'ACTIVE' : 'INACTIVE';
    }

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
