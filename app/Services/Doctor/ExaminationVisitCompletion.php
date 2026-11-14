<?php

namespace App\Services\Doctor;

use App\Contracts\DoctorVisitCompletionEligibility;
use App\Models\MedicalVisit;
use Illuminate\Validation\ValidationException;

final class ExaminationVisitCompletion
{
    public function hasClinicalEligibility(): bool
    {
        return interface_exists(DoctorVisitCompletionEligibility::class)
            && app()->bound(DoctorVisitCompletionEligibility::class);
    }

    /** Close the visit, its appointment and open queue entries in the caller's transaction. */
    public function completeLocked(MedicalVisit $visit): void
    {
        $visit->assertNotBeforeAppointmentDay();
        if (! in_array($visit->status?->name, ['IN_EXAMINATION', 'RETURNING'], true)) {
            throw ValidationException::withMessages(['visit_status_id' => 'Lượt khám chưa đến bước hoàn tất.']);
        }
        $completedAt = now();
        if (! $visit->examination_started_at || $visit->examination_started_at->gt($completedAt) || $visit->examination_ended_at) {
            throw ValidationException::withMessages(['visit_status_id' => 'Thời điểm bắt đầu hoặc kết thúc của lượt khám không hợp lệ.']);
        }
        abort_unless($this->hasClinicalEligibility(), 409, 'Chưa tích hợp kiểm tra hồ sơ chuyên môn.');
        $clinical = app(DoctorVisitCompletionEligibility::class);
        abort_unless($clinical instanceof DoctorVisitCompletionEligibility, 409, 'Chưa tích hợp kiểm tra hồ sơ chuyên môn.');

        if ($visit->serviceOrders()->whereHas('items', fn ($items) => $items->unresolved())->exists()) {
            throw ValidationException::withMessages(['visit_status_id' => 'Lượt khám còn dịch vụ chưa xử lý.']);
        }
        $clinical->assertComplete($visit);

        $completedStatus = $visit->transitionStatus('COMPLETED');
        $visit->update(['visit_status_id' => $completedStatus->id, 'examination_ended_at' => $completedAt]);
        $visit->completeOpenQueues($completedAt);
        $visit->synchronizeAppointment('COMPLETED');
    }
}
