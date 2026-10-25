<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Patient extends Model
{
    protected $table = 'patients';

    protected $fillable = [
        'user_id',
        'patient_code',
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

    public function detail(): HasOne
    {
        return $this->hasOne(
            PatientDetail::class
        );
    }

    /**
     * Một bệnh nhân có nhiều lần ghi nhận chỉ số sức khỏe.
     */
    public function healthRecords(): HasMany
    {
        return $this->hasMany(
            PersonalHealthRecord::class
        );
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(
            Appointment::class,
            'patient_id'
        );
    }

    public function medicalVisits(): HasMany
    {
        return $this->hasMany(MedicalVisit::class);
    }
}
