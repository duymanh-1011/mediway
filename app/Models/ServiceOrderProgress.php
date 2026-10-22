<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class ServiceOrderProgress
{
    /**
     * Call after completion or cancellation; lock the visit before all child rows.
     */
    public function synchronize(int $serviceOrderId): void
    {
        $visitId = ServiceOrder::findOrFail($serviceOrderId)->medical_visit_id;

        DB::transaction(function () use ($visitId, $serviceOrderId): void {
            $visit = MedicalVisit::with('status')->whereKey($visitId)->lockForUpdate()->firstOrFail();
            $order = ServiceOrder::whereKey($serviceOrderId)->lockForUpdate()->firstOrFail();
            if ($order->items()->exists() && ! $order->items()->unresolved()->exists() && $order->status !== 'CANCELLED') {
                $order->update(['status' => 'COMPLETED']);
            }

            $items = ServiceOrderItem::whereHas('serviceOrder', fn (Builder $query): Builder => $query->where('medical_visit_id', $visitId));
            if ($visit->status?->name !== 'WAITING_FOR_SERVICE' || ! $items->exists() || (clone $items)->unresolved()->exists()) {
                return;
            }

            $alreadyQueued = $visit->queues()->where('queue_type', 'RETURN_AFTER_SERVICE')
                ->whereHas('status', fn (Builder $query): Builder => $query->whereIn('name', ['WAITING', 'CALLED', 'IN_SERVICE', 'SKIPPED']))->exists();
            if ($alreadyQueued) {
                return;
            }

            $waitingStatus = QueueStatus::where('name', 'WAITING')->where('is_active', true)->first();
            if (! $waitingStatus) {
                throw new \RuntimeException('Không tìm thấy trạng thái hàng đợi đang chờ đang hoạt động.');
            }
            ExaminationRoom::whereKey($visit->examination_room_id)->lockForUpdate()->firstOrFail();
            $lastNumber = ExaminationRoomQueue::whereDate('queued_at', now()->toDateString())
                ->whereHas('medicalVisit', fn (Builder $query): Builder => $query->where('examination_room_id', $visit->examination_room_id))
                ->orderByDesc('queue_number')->lockForUpdate()->value('queue_number');

            ExaminationRoomQueue::create([
                'medical_visit_id' => $visit->id, 'queue_status_id' => $waitingStatus->id,
                'queue_number' => ((int) $lastNumber) + 1, 'queue_type' => 'RETURN_AFTER_SERVICE',
                'queued_at' => now(), 'called_at' => null, 'service_started_at' => null,
                'completed_at' => null, 'call_count' => 0,
                'notes' => 'Quay lại bác sĩ khi các dịch vụ còn lại đã hoàn tất hoặc toàn bộ dịch vụ đã hủy.',
            ]);
        }, 3);
    }
}
