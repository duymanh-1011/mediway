<?php

namespace App\Services\Doctor;

use App\Models\ExaminationRoomQueue;
use App\Models\MedicalVisit;
use App\Models\QueueStatus;
use App\Models\ServiceOrderItem;
use Illuminate\Validation\ValidationException;

final class ExaminationQueueWorkflow
{
    public function actionsForVisit(MedicalVisit $visit): array
    {
        if ($visit->examination_ended_at || ! $visit->status?->allows_transition
            || $visit->examinationRoom?->status !== 'ACTIVE') {
            return [];
        }

        $stage = $visit->displayStatus();
        $type = match ($stage) {
            'WAITING' => $visit->examination_started_at ? null : 'INITIAL_EXAM',
            'WAITING_FOR_RETURN' => 'RETURN_AFTER_SERVICE',
            default => null,
        };

        if (! $type || $visit->appointment?->isScheduledInFuture()
            || ($type === 'INITIAL_EXAM' && $visit->appointment && ! $visit->appointment->isScheduledForToday())) {
            return [];
        }

        $queue = $visit->queues()
            ->with('status')
            ->where('queue_type', $type)
            ->whereHas('status', fn ($status) => $status->whereIn('name', ['WAITING', 'CALLED', 'SKIPPED']))
            ->latest('id')
            ->first();
        if (! $queue?->status?->is_active || ! $queue->status->allows_transition) {
            return [];
        }
        $queue->setRelation('medicalVisit', $visit);

        return [
            'queue' => $queue,
            'actions' => match ($queue->status->name) {
                'WAITING' => ['call'],
                'SKIPPED' => ['recall'],
                'CALLED' => ['recall', 'start'],
            },
            'expected' => [
                'expected_status' => $queue->status->name,
                'expected_calls' => $queue->call_count,
                'expected_phase' => $this->phaseToken($queue),
            ],
        ];
    }

    public function phaseToken(ExaminationRoomQueue $queue): string
    {
        $latestOrder = null;
        if ($queue->queue_type === 'RETURN_AFTER_SERVICE') {
            $visit = $queue->medicalVisit;
            // A new order starts another service cycle even if queued_at is in the same second.
            $latestOrder = array_key_exists('service_orders_max_id', $visit->getAttributes())
                ? $visit->service_orders_max_id : $visit->serviceOrders()->max('id');
        }

        return hash('sha256', json_encode([
            $queue->id, $queue->queue_type, $queue->queued_at?->format('Y-m-d H:i:s.u'), $latestOrder === null ? null : (string) $latestOrder,
        ], JSON_THROW_ON_ERROR));
    }

    /** Acquire in a transaction. All clinical writers lock the visit before its children. */
    public function lockQueue(ExaminationRoomQueue $queue, array $allowed, bool $requireUnchanged = false): ExaminationRoomQueue
    {
        $original = $queue;
        $visit = MedicalVisit::with('status')->whereKey($queue->medical_visit_id)->lockForUpdate()->firstOrFail();
        $queue = ExaminationRoomQueue::with('status')->whereKey($queue->id)
            ->where('medical_visit_id', $visit->id)->lockForUpdate()->firstOrFail();
        $queue->setRelation('medicalVisit', $visit);

        if ($visit->status?->name === 'COMPLETED') {
            throw ValidationException::withMessages(['queue' => 'Lượt khám đã hoàn tất, không thể tiếp tục thao tác hàng đợi.']);
        }
        if (! in_array($queue->status?->name, $allowed, true) || ! $queue->status?->allows_transition || ! $queue->status->is_active
            || ($requireUnchanged && ($queue->queue_status_id !== $original->queue_status_id || $queue->call_count !== $original->call_count))) {
            throw ValidationException::withMessages(['queue' => 'Hàng đợi đã đổi trạng thái. Vui lòng tải lại trang.']);
        }

        return $queue;
    }

    public function callLocked(ExaminationRoomQueue $queue, QueueStatus $calledStatus): void
    {
        $visit = $queue->medicalVisit;
        if ($queue->queue_type === 'INITIAL_EXAM' && $visit->status?->name === 'WAITING' && ! $visit->examination_started_at) {
            $visit->assertCanBeginExamination();
        } elseif ($queue->queue_type === 'RETURN_AFTER_SERVICE' && $visit->status?->name === 'WAITING_FOR_SERVICE') {
            $visit->assertNotBeforeAppointmentDay();
            $this->assertReturnReady($visit);
        } else {
            throw ValidationException::withMessages(['queue' => 'Lượt khám chưa đến bước cho phép gọi bệnh nhân.']);
        }

        $queue->update([
            'queue_status_id' => $calledStatus->id,
            'called_at' => now(),
            'call_count' => $queue->call_count + 1,
        ]);
    }

    public function startLocked(ExaminationRoomQueue $queue, QueueStatus $inServiceStatus): void
    {
        $visit = $queue->medicalVisit;
        $visit->assertCanBeginExamination();
        $returning = $queue->queue_type === 'RETURN_AFTER_SERVICE';
        $expected = $returning ? ['WAITING_FOR_SERVICE'] : ['WAITING', 'IN_EXAMINATION'];

        if (! in_array($queue->queue_type, ['INITIAL_EXAM', 'RETURN_AFTER_SERVICE'], true)
            || ! in_array($visit->status?->name, $expected, true)) {
            throw ValidationException::withMessages(['queue' => 'Chưa thể bắt đầu phục vụ ở bước hiện tại. Vui lòng kiểm tra trạng thái lượt khám.']);
        }

        if ($returning) {
            $this->assertReturnReady($visit);
        }

        $this->assertSingleOpenQueue($queue);

        $status = $visit->transitionStatus($returning ? 'RETURNING' : 'IN_EXAMINATION');
        $visit->synchronizeAppointment('IN_EXAMINATION');
        $now = now();
        $attributes = ['visit_status_id' => $status->id];
        if (! $returning && ! $visit->examination_started_at) {
            $attributes['examination_started_at'] = $now;
        }
        $visit->update($attributes);
        $queue->update([
            'queue_status_id' => $inServiceStatus->id,
            'service_started_at' => $queue->service_started_at ?? $now,
        ]);
    }

    /** Check while holding the visit lock, before operating on one of its open entries. */
    public function assertSingleOpenQueue(ExaminationRoomQueue $queue): void
    {
        if ($queue->medicalVisit->queues()->whereKeyNot($queue->id)
            ->whereHas('status', fn ($status) => $status->whereIn('name', ['WAITING', 'CALLED', 'IN_SERVICE', 'SKIPPED']))->exists()) {
            throw ValidationException::withMessages(['queue' => 'Lượt khám có nhiều hàng đợi đang mở. Vui lòng đối chiếu hàng đợi trước khi thao tác.']);
        }
    }

    private function assertReturnReady(MedicalVisit $visit): void
    {
        $items = ServiceOrderItem::whereHas('serviceOrder', fn ($query) => $query->where('medical_visit_id', $visit->id));
        if (! $items->exists() || (clone $items)->unresolved()->exists()) {
            throw ValidationException::withMessages(['queue' => 'Phải hoàn tất các dịch vụ chưa hủy trước khi quay lại bác sĩ.']);
        }
    }
}
