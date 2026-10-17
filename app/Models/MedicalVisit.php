<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MedicalVisit extends Model
{
    protected $table = 'medical_visits';

    protected $fillable = [
        'visit_code',
        'appointment_id',
        'patient_id',
        'doctor_id',
        'specialty_id',
        'examination_room_id',
        'visit_status_id',
        'checked_in_at',
        'examination_started_at',
        'examination_ended_at',
        'reception_source',
        'reception_notes',
        'examination_slip_pdf_path',
    ];

    protected function casts(): array
    {
        return [
            'checked_in_at' => 'datetime',
            'examination_started_at' => 'datetime',
            'examination_ended_at' => 'datetime',
        ];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(
            Appointment::class,
            'appointment_id'
        );
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(
            Patient::class,
            'patient_id'
        );
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(
            Doctor::class,
            'doctor_id'
        );
    }

    public function specialty(): BelongsTo
    {
        return $this->belongsTo(
            Specialty::class,
            'specialty_id'
        );
    }

    public function examinationRoom(): BelongsTo
    {
        return $this->belongsTo(
            ExaminationRoom::class,
            'examination_room_id'
        );
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(
            VisitStatus::class,
            'visit_status_id'
        );
    }

    public function queues(): HasMany
    {
        return $this->hasMany(
            ExaminationRoomQueue::class,
            'medical_visit_id'
        );
    }

    public function clinicalNote(): HasOne
    {
        return $this->hasOne(
            VisitClinicalNote::class,
            'medical_visit_id'
        );
    }

    public function diagnoses(): HasMany
    {
        return $this->hasMany(
            VisitDiagnosis::class,
            'medical_visit_id'
        );
    }

    public function conclusion(): HasOne
    {
        return $this->hasOne(
            VisitConclusion::class,
            'medical_visit_id'
        );
    }

    /**
     * Các phiếu chỉ định dịch vụ của lượt khám.
     */
    public function serviceOrders(): HasMany
    {
        return $this->hasMany(
            ServiceOrder::class,
            'medical_visit_id'
        );
    }
}