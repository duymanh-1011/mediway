<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitConclusion extends Model
{
    protected $table = 'visit_conclusions';

    protected $fillable = [
        'medical_visit_id',
        'doctor_id',
        'professional_conclusion',
        'disposition',
        'treatment_plan',
        'instructions',
        'has_follow_up',
        'follow_up_date',
        'follow_up_reason',
        'notes',
        'conclusion_pdf_path',
        'concluded_at',
    ];

    protected function casts(): array
    {
        return [
            'has_follow_up' => 'boolean',
            'follow_up_date' => 'date',
            'concluded_at' => 'datetime',
        ];
    }

    public function medicalVisit(): BelongsTo
    {
        return $this->belongsTo(
            MedicalVisit::class,
            'medical_visit_id'
        );
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(
            Doctor::class,
            'doctor_id'
        );
    }
}
