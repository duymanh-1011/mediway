<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Doctor extends Model
{
    protected $table = 'doctors';

    protected $fillable = [
        'user_id',
        'specialty_id',
        'doctor_code',
        'full_name',
        'date_of_birth',
        'gender',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class
        );
    }

    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }

    public function scopeForSpecialty(Builder $query, int $specialtyId): Builder
    {
        return $query->where(fn (Builder $doctors) => $doctors
            ->where('doctors.specialty_id', $specialtyId)
            ->orWhereHas('specialty', fn (Builder $specialty) => $specialty->where('is_general', true)));
    }

    public function canPracticeSpecialty(int $specialtyId): bool
    {
        $this->loadMissing('specialty');

        return $this->specialty?->status === 'ACTIVE'
            && ($this->specialty->is_general || (int) $this->specialty_id === $specialtyId);
    }

    public function detail(): HasOne
    {
        return $this->hasOne(
            DoctorDetail::class
        );
    }

    public function schedules()
    {
        return $this->hasMany(
            DoctorSchedule::class,
            'doctor_id'
        );
    }
}
