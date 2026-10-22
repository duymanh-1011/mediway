<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prescription extends Model
{
    protected $table = 'prescriptions';

    protected $fillable = [
        'prescription_code',
        'medical_visit_id',
        'doctor_id',
        'prescribed_at',
        'general_instructions',
        'doctor_notes',
        'status',
        'prescription_pdf_path',
    ];

    protected $casts = [
        'prescribed_at' => 'datetime',
    ];

    public function hasPdf(): bool
    {
        return app(ClinicalDocument::class)->exists($this->prescription_pdf_path, 'prescriptions/'.$this->id, 'pdf');
    }

    /**
     * Lượt khám của đơn thuốc.
     *
     * medical_visit_id -> medical_visits.id
     */
    public function medicalVisit(): BelongsTo
    {
        return $this->belongsTo(
            MedicalVisit::class,
            'medical_visit_id'
        );
    }

    /**
     * Bác sĩ kê đơn.
     *
     * doctor_id -> doctors.id
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(
            Doctor::class,
            'doctor_id'
        );
    }

    /**
     * Các thuốc trong đơn.
     */
    public function items(): HasMany
    {
        return $this->hasMany(
            PrescriptionItem::class,
            'prescription_id'
        );
    }
}
