<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitClinicalNote extends Model
{
    protected $table = 'visit_clinical_notes';

    protected $fillable = [
        'medical_visit_id',
        'reason_for_visit',
        'current_symptoms',
        'present_illness_history',
        'relevant_medical_history',
        'physical_examination',
        'initial_assessment',
        'notes',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
        ];
    }

    public function medicalVisit(): BelongsTo
    {
        return $this->belongsTo(
            MedicalVisit::class,
            'medical_visit_id'
        );
    }
}
