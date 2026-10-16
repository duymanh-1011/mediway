<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonalHealthRecord extends Model
{
    protected $table = 'personal_health_records';

    protected $fillable = [
        'patient_id',
        'patient_code',
        'height_cm',
        'weight_kg',
        'bmi',
        'systolic_blood_pressure',
        'diastolic_blood_pressure',
        'heart_rate',
        'body_temperature',
        'spo2',
        'respiratory_rate',
        'blood_glucose',
        'recorded_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'height_cm' => 'decimal:2',
            'weight_kg' => 'decimal:2',
            'bmi' => 'decimal:2',
            'body_temperature' => 'decimal:1',
            'spo2' => 'decimal:2',
            'blood_glucose' => 'decimal:2',
            'recorded_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}