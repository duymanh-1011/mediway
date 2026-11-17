<?php

namespace App\Services;

use App\Models\ExaminationRoomQueue;
use App\Models\MedicalVisit;
use App\Models\Payable;
use App\Models\PaymentStatus;
use App\Models\QueueStatus;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ExaminationFeeService
{
    public function createForVisit(MedicalVisit $visit): Payable
    {
        $unpaid = PaymentStatus::where('name', 'UNPAID')->where('is_active', true)->first();
        if (! $unpaid) throw ValidationException::withMessages(['appointment' => 'Chưa cấu hình trạng thái chưa thanh toán.']);
        return Payable::create(['payable_code' => 'PT'.strtoupper(Str::random(20)), 'medical_visit_id' => $visit->id,
            'service_order_item_id' => null, 'payment_type' => 'EXAMINATION_FEE', 'description' => 'Phí khám bệnh',
            'amount' => config('patient_appointments.examination_fee'), 'payment_status_id' => $unpaid->id, 'generated_at' => now()]);
    }

    /** Call after marking the fee paid, while holding the visit lock. */
    public function enqueueAfterPayment(Payable $payable): void
    {
        if ($payable->payment_type !== 'EXAMINATION_FEE') {
            return;
        }
        $visit = $payable->medicalVisit;
        if ((! $visit->appointment_id && $visit->reception_source !== 'WALK_IN') || $visit->status?->name !== 'WAITING'
            || $visit->queues()->where('queue_type', 'INITIAL_EXAM')->exists()) {
            return;
        }

        $visit->assertExaminationFeePaid();
        if ($visit->appointment_id ? ! $visit->appointment?->isScheduledForToday() : ! $visit->checked_in_at?->isToday()) {
            throw ValidationException::withMessages(['payment_method' => 'Không thể xếp hàng cho lịch khám khác ngày. Vui lòng kiểm tra lại lịch hẹn trước khi thu phí.']);
        }
        $waiting = QueueStatus::where('name', 'WAITING')->where('is_active', true)->first();
        if (! $waiting) {
            throw ValidationException::withMessages(['payment_method' => 'Chưa cấu hình trạng thái hàng đợi đang chờ.']);
        }
        ExaminationRoomQueue::create([
            'medical_visit_id' => $visit->id, 'queue_status_id' => $waiting->id,
            'queue_number' => app(ExaminationQueueNumber::class)->reserve($visit), 'queue_type' => 'INITIAL_EXAM',
            'queued_at' => now(), 'call_count' => 0,
        ]);
    }
}
