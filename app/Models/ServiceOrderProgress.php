<?php

namespace App\Models;

use App\Services\ExaminationQueueNumber;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ServiceOrderProgress
{
    /** The caller holds the visit and item locks in the same transaction. */
    public function completeAdminItem(Request $request, ServiceOrderItem $item, CarbonInterface $completedAt): void
    {
        $current = ServiceStatus::whereKey($item->service_status_id)->lockForUpdate()->firstOrFail();
        if ($current->name !== 'PROCESSING' || ! $current->allows_transition) {
            throw ValidationException::withMessages(['service_status_id' => 'Trạng thái hiện tại không cho phép hoàn tất dịch vụ.']);
        }
        if ($item->started_at && $completedAt->lt($item->started_at)) {
            throw ValidationException::withMessages(['service_status_id' => 'Thời điểm hoàn tất dịch vụ không được trước thời điểm bắt đầu.']);
        }

        $completed = ServiceStatus::where('name', 'COMPLETED')->where('is_active', true)->first();
        if (! $completed) {
            throw ValidationException::withMessages(['service_status_id' => 'Chưa có trạng thái “Đã hoàn thành” đang được sử dụng. Vui lòng kiểm tra mục Trạng thái dịch vụ.']);
        }

        $before = [
            'service_status_id' => $current->id,
            'service_status' => $current->name,
            'started_at' => $item->started_at?->toDateTimeString(),
            'completed_at' => $item->completed_at?->toDateTimeString(),
        ];
        $item->update(['service_status_id' => $completed->id, 'completed_at' => $completedAt]);
        $item->setRelation('serviceStatus', $completed);
        $this->auditItemTransition($request, $item, $before, [
            'service_status_id' => $completed->id,
            'service_status' => $completed->name,
            'started_at' => $before['started_at'],
            'completed_at' => $completedAt->format('Y-m-d H:i:s'),
        ], 'Hoàn tất dịch vụ');

        try {
            $this->synchronize($item->service_order_id);
        } catch (\UnexpectedValueException $exception) {
            throw ValidationException::withMessages(['service_status_id' => $exception->getMessage()]);
        }
    }

    public function auditItemTransition(Request $request, ServiceOrderItem $item, array $before, array $after, string $action): void
    {
        $serviceCode = $item->medicalService?->service_code;
        $orderCode = $item->serviceOrder?->service_order_code;
        AuditLog::create([
            'user_id' => $request->user()?->id,
            'role_name_snapshot' => $request->user()?->role?->name,
            'action' => 'STATUS_CHANGE',
            'entity_type' => 'service_order_items',
            'entity_id' => $item->id,
            'old_values' => $before,
            'new_values' => $after,
            'description' => $action.($serviceCode ? ' '.$serviceCode : '').($orderCode ? ' của phiếu '.$orderCode : '').'.',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'performed_at' => now(),
        ]);
    }

    public function returnQueueMessage(int $medicalVisitId): string
    {
        $remaining = ServiceOrderItem::whereHas('serviceOrder', fn (Builder $query): Builder => $query->where('medical_visit_id', $medicalVisitId))
            ->unresolved()->count();
        if ($remaining > 0) {
            return 'Lượt khám còn '.$remaining.' dịch vụ cần hoàn tất. Bệnh nhân chưa được xếp hàng quay lại bác sĩ.';
        }
        $visit = MedicalVisit::with('status')->findOrFail($medicalVisitId);
        if ($visit->status?->name === 'RETURNING') {
            return 'Bệnh nhân đang khám lại với bác sĩ.';
        }
        if ($visit->queues()->where('queue_type', 'RETURN_AFTER_SERVICE')
            ->whereHas('status', fn (Builder $query): Builder => $query->whereIn('name', ['WAITING', 'CALLED', 'IN_SERVICE', 'SKIPPED']))->exists()) {
            return 'Bệnh nhân đã được xếp vào hàng đợi quay lại bác sĩ.';
        }

        return 'Không còn dịch vụ cần chờ. Vui lòng xem trạng thái hiện tại của lượt khám.';
    }

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

            // A serving queue belongs to the previous consultation, not a new return.
            // This also handles old records created before queue handoff was automatic.
            $visit->completeOpenQueues(now(), ['IN_SERVICE']);
            $alreadyQueued = $visit->queues()->where('queue_type', 'RETURN_AFTER_SERVICE')
                ->whereHas('status', fn (Builder $query): Builder => $query->whereIn('name', ['WAITING', 'CALLED', 'IN_SERVICE', 'SKIPPED']))->exists();
            if ($alreadyQueued) {
                return;
            }

            $waitingStatus = QueueStatus::where('name', 'WAITING')->where('is_active', true)->first();
            if (! $waitingStatus) {
                throw new \UnexpectedValueException('Chưa có trạng thái “Đang chờ” đang được sử dụng. Vui lòng kiểm tra mục Trạng thái hàng đợi.');
            }
            $queue = $visit->queues()->oldest('id')->lockForUpdate()->first();
            $number = app(ExaminationQueueNumber::class)->reserve($visit);
            if ($visit->reception_queue_number === null) {
                $visit->update(['reception_queue_number' => $number]);
            }
            $attributes = [
                'queue_status_id' => $waitingStatus->id, 'queue_number' => $number, 'queue_type' => 'RETURN_AFTER_SERVICE',
                'queued_at' => now(), 'called_at' => null, 'service_started_at' => null,
                'completed_at' => null, 'call_count' => 0,
                'notes' => 'Quay lại bác sĩ khi các dịch vụ còn lại đã hoàn tất hoặc toàn bộ dịch vụ đã hủy.',
            ];
            if ($queue) {
                $queue->update($attributes);
            } else {
                $visit->queues()->create($attributes);
            }
        }, 3);
    }
}
