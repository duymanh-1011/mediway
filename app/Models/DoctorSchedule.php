<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Carbon\CarbonImmutable;

class DoctorSchedule extends Model
{
    protected $fillable = [
        'doctor_id',
        'specialty_id',
        'examination_room_id',
        'work_date',
        'start_time',
        'end_time',
        'shift_code',
        'status',
        'notes',
    ];

    protected $casts = [
        'work_date' => 'date',
    ];

    public function isStandardShift(): bool
    {
        return $this->shift_code !== null;
    }

    public function scopeOverlappingOn(Builder $query, string $date, string $start, string $end): Builder
    {
        return $query->whereDate('work_date', $date)->whereTime('start_time', '<', $end)->whereTime('end_time', '>', $start);
    }

    public function hasEnded(): bool
    {
        return $this->work_date && $this->end_time
            && now()->greaterThanOrEqualTo(CarbonImmutable::parse($this->work_date->format('Y-m-d').' '.$this->end_time, config('app.timezone')));
    }

    public function effectiveStatus(): string
    {
        return $this->status === 'ACTIVE' && ! $this->hasEnded() ? 'ACTIVE' : 'INACTIVE';
    }

    public function scopeEffectiveActive(Builder $query): Builder
    {
        return $query->where('status', 'ACTIVE')->where(function (Builder $date) {
            $date->whereDate('work_date', '>', today()->toDateString())
                ->orWhere(fn (Builder $today) => $today->whereDate('work_date', today()->toDateString())
                    ->where('end_time', '>', now()->format('H:i:s')));
        });
    }

    public function scopeEffectiveInactive(Builder $query): Builder
    {
        return $query->where(function (Builder $inactive) {
            $inactive->where('status', 'INACTIVE')
                ->orWhereDate('work_date', '<', today()->toDateString())
                ->orWhere(fn (Builder $today) => $today->whereDate('work_date', today()->toDateString())
                    ->where('end_time', '<=', now()->format('H:i:s')));
        });
    }

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
