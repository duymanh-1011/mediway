<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitDiagnosis extends Model
{
    protected $table = 'visit_diagnoses';

    protected $fillable = [
        'medical_visit_id',
        'disease_id',
        'diagnosis_type',
        'severity',
        'diagnosis_description',
        'diagnosed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'diagnosed_at' => 'datetime',
        ];
    }

    public function medicalVisit(): BelongsTo
    {
        return $this->belongsTo(
            MedicalVisit::class,
            'medical_visit_id'
        );
    }

    public function disease(): BelongsTo
    {
        return $this->belongsTo(
            Disease::class,
            'disease_id'
        );
    }
}
