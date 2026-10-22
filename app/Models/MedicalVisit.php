<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Validation\ValidationException;

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

    public function canRecordClinicalData(): bool
    {
        return in_array($this->status?->name, ['IN_EXAMINATION', 'RETURNING'], true);
    }

    /** Acquire inside a transaction, before writing clinical child records. */
    public function lockForClinicalWrite(): self
    {
        $visit = self::with('status')->whereKey($this->id)->lockForUpdate()->firstOrFail();
        if (! $visit->canRecordClinicalData()) {
            throw ValidationException::withMessages(['visit' => 'Chỉ được ghi hồ sơ khi đang khám hoặc đã quay lại bác sĩ.']);
        }

        return $visit;
    }

    /** The caller must hold the visit lock before closing its queues. */
    public function completeOpenQueues(CarbonInterface $completedAt): void
    {
        $queues = $this->queues()->whereHas('status', fn ($query) => $query->whereIn('name', ['WAITING', 'CALLED', 'IN_SERVICE', 'SKIPPED']))
            ->lockForUpdate()->get();
        if ($queues->isEmpty()) {
            return;
        }
        $completedStatus = QueueStatus::where('name', 'COMPLETED')->where('is_active', true)->first();
        if (! $completedStatus) {
            throw ValidationException::withMessages(['visit_status_id' => 'Thiếu trạng thái hoàn tất hàng đợi đang hoạt động.']);
        }
        foreach ($queues as $queue) {
            $queue->update(['queue_status_id' => $completedStatus->id, 'completed_at' => $queue->completed_at ?? $completedAt]);
        }
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
    public function prescription(): HasOne
    {
        return $this->hasOne(Prescription::class, 'medical_visit_id');
    }

    public function serviceOrders(): HasMany
    {
        return $this->hasMany(
            ServiceOrder::class,
            'medical_visit_id'
        );
    }
}
