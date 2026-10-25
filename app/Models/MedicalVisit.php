<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
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

    public function scopeForMedicalHistory(Builder $query, array $filters): Builder
    {
        $term = trim($filters['history_q'] ?? '');

        return $query
            ->when($term !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('visit_code', 'like', '%'.$term.'%')
                ->orWhereHas('patient', fn ($q) => $q->where('full_name', 'like', '%'.$term.'%')->orWhere('patient_code', 'like', '%'.$term.'%'))
                ->orWhereHas('doctor', fn ($q) => $q->where('full_name', 'like', '%'.$term.'%')->orWhere('doctor_code', 'like', '%'.$term.'%'))
                ->orWhereHas('specialty', fn ($q) => $q->where('name', 'like', '%'.$term.'%'))
                ->orWhereHas('examinationRoom', fn ($q) => $q->where('name', 'like', '%'.$term.'%')->orWhere('room_code', 'like', '%'.$term.'%'))
                ->orWhereHas('diagnoses.disease', fn ($q) => $q->where('name', 'like', '%'.$term.'%')->orWhere('disease_code', 'like', '%'.$term.'%'))
                ->orWhereHas('conclusion', fn ($q) => $q->where('professional_conclusion', 'like', '%'.$term.'%'))))
            ->when(! empty($filters['date_from']), fn ($q) => $q->where('checked_in_at', '>=', $filters['date_from'].' 00:00:00'))
            ->when(! empty($filters['date_to']), fn ($q) => $q->where('checked_in_at', '<', Carbon::parse($filters['date_to'])->addDay()->startOfDay()))
            ->when(! empty($filters['visit_status_id']), fn ($q) => $q->where('visit_status_id', $filters['visit_status_id']));
    }

    public function canRecordClinicalData(): bool
    {
        return in_array($this->status?->name, ['IN_EXAMINATION', 'RETURNING'], true)
            && ! $this->appointment?->isScheduledInFuture();
    }

    public function assertCanBeginExamination(): void
    {
        if ($this->appointment && ($this->appointment->isScheduledInFuture()
            || (! $this->examination_started_at && ! $this->appointment->isScheduledForToday()))) {
            throw ValidationException::withMessages(['visit' => 'Chỉ được bắt đầu khám vào đúng ngày hẹn của bệnh nhân.']);
        }
    }

    /** Acquire inside a transaction, before writing clinical child records. */
    public function lockForClinicalWrite(): self
    {
        $visit = self::with('status')->whereKey($this->id)->lockForUpdate()->firstOrFail();
        $visit->assertNotBeforeAppointmentDay();
        if (! $visit->canRecordClinicalData()) {
            throw ValidationException::withMessages(['visit' => 'Chưa thể ghi hồ sơ ở bước này. Chỉ được ghi khi đang khám hoặc bệnh nhân đã quay lại bác sĩ sau dịch vụ.']);
        }

        return $visit;
    }

    public function assertNotBeforeAppointmentDay(): void
    {
        if ($this->appointment?->isScheduledInFuture()) {
            throw ValidationException::withMessages(['visit' => 'Chưa đến ngày hẹn khám của bệnh nhân. Không thể thực hiện khám trước ngày hẹn.']);
        }
    }

    public function assertServiceDataWritable(): void
    {
        $this->assertNotBeforeAppointmentDay();
        if ($this->status?->name === 'COMPLETED') {
            throw ValidationException::withMessages(['visit' => 'Lượt khám đã hoàn tất, không thể thay đổi dịch vụ hoặc kết quả trực tiếp.']);
        }
    }

    /** Call while holding the visit lock, before changing its workflow state. */
    public function transitionStatus(string $name): VisitStatus
    {
        $statuses = VisitStatus::where(fn ($q) => $q->whereKey($this->visit_status_id)->orWhere('name', $name))
            ->orderBy('id')->lockForUpdate()->get();
        if (! $statuses->firstWhere('id', $this->visit_status_id)?->allows_transition) {
            throw ValidationException::withMessages(['visit_status_id' => 'Trạng thái lượt khám đang bị khóa thay đổi. Vui lòng kiểm tra mục Trạng thái lượt khám.']);
        }
        $status = $statuses->first(fn ($status) => $status->name === $name && $status->is_active);
        if (! $status) {
            throw ValidationException::withMessages(['visit_status_id' => 'Chưa có trạng thái “'.AdminDisplay::label($name).'” đang được sử dụng. Vui lòng kiểm tra mục Trạng thái lượt khám.']);
        }

        return $status;
    }

    /** Missing configuration must roll back the entire clinical transition. */
    public function synchronizeAppointment(string $name): void
    {
        if (! $this->appointment_id) {
            return;
        }
        $appointment = Appointment::whereKey($this->appointment_id)->lockForUpdate()->firstOrFail();
        $statuses = AppointmentStatus::where(fn ($q) => $q->whereKey($appointment->appointment_status_id)->orWhere('name', $name))
            ->orderBy('id')->lockForUpdate()->get();
        $status = $statuses->first(fn ($status) => $status->name === $name && $status->is_active);
        if (! $status) {
            throw ValidationException::withMessages(['visit_status_id' => 'Chưa có trạng thái lịch hẹn “'.AdminDisplay::label($name).'” đang được sử dụng. Vui lòng kiểm tra mục Trạng thái lịch hẹn.']);
        }
        if ($appointment->appointment_status_id === $status->id) {
            return;
        }
        if (! $statuses->firstWhere('id', $appointment->appointment_status_id)?->allows_transition) {
            throw ValidationException::withMessages(['visit_status_id' => 'Trạng thái lịch hẹn đang bị khóa thay đổi. Vui lòng kiểm tra mục Trạng thái lịch hẹn.']);
        }
        $appointment->update(['appointment_status_id' => $status->id]);
    }

    /** Direct start and queue start must leave the same queue state. */
    public function startInitialQueues(): void
    {
        $queues = $this->queues()->with('status')->where('queue_type', 'INITIAL_EXAM')
            ->whereHas('status', fn ($q) => $q->whereIn('name', ['WAITING', 'CALLED', 'SKIPPED', 'IN_SERVICE']))
            ->lockForUpdate()->get();
        if ($queues->isEmpty()) {
            return;
        }
        $status = QueueStatus::where('name', 'IN_SERVICE')->where('is_active', true)->lockForUpdate()->first();
        if (! $status) {
            throw ValidationException::withMessages(['visit_status_id' => 'Chưa có trạng thái “Đang phục vụ” đang được sử dụng. Vui lòng kiểm tra mục Trạng thái hàng đợi.']);
        }
        foreach ($queues as $queue) {
            if ($queue->status?->name === 'IN_SERVICE') {
                continue;
            }
            if (! $queue->status?->allows_transition) {
                throw ValidationException::withMessages(['visit_status_id' => 'Hàng đợi đang bị khóa thay đổi trạng thái. Vui lòng kiểm tra mục Trạng thái hàng đợi.']);
            }
            $queue->update(['queue_status_id' => $status->id, 'service_started_at' => $queue->service_started_at ?? now()]);
        }
    }

    /** The caller must hold the visit lock before closing its queues. */
    public function completeOpenQueues(CarbonInterface $completedAt, array $states = ['WAITING', 'CALLED', 'IN_SERVICE', 'SKIPPED']): void
    {
        $queues = $this->queues()->with('status')->whereHas('status', fn ($query) => $query->whereIn('name', $states))
            ->lockForUpdate()->get();
        if ($queues->isEmpty()) {
            return;
        }
        $completedStatus = QueueStatus::where('name', 'COMPLETED')->where('is_active', true)->first();
        if (! $completedStatus) {
            throw ValidationException::withMessages(['visit_status_id' => 'Chưa có trạng thái “Đã hoàn thành” đang được sử dụng. Vui lòng kiểm tra mục Trạng thái hàng đợi.']);
        }
        foreach ($queues as $queue) {
            if (! $queue->status?->allows_transition) {
                throw ValidationException::withMessages(['visit_status_id' => 'Hàng đợi đang bị khóa thay đổi trạng thái. Vui lòng kiểm tra mục Trạng thái hàng đợi.']);
            }
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

    public function payables(): HasMany
    {
        return $this->hasMany(Payable::class);
    }
}
