<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

final class WorkflowIntegrityRepair
{
    /** @return array{missing_payables: array<int, array<string, mixed>>, open_queues: array<int, array<string, mixed>>} */
    public function inspect(): array
    {
        $items = ServiceOrderItem::with(['serviceStatus', 'medicalService', 'serviceOrder.medicalVisit'])
            ->doesntHave('payable')->orderBy('id')->get();
        $queues = ExaminationRoomQueue::with(['status', 'medicalVisit'])
            ->whereHas('medicalVisit.status', fn ($query) => $query->where('name', 'COMPLETED'))
            ->whereHas('status', fn ($query) => $query->whereIn('name', ['WAITING', 'CALLED', 'IN_SERVICE', 'SKIPPED']))
            ->orderBy('id')->get();

        return [
            'missing_payables' => $items->map(fn (ServiceOrderItem $item): array => [
                'id' => $item->id, 'medical_visit_id' => $item->serviceOrder->medical_visit_id,
                'service_order_code' => $item->serviceOrder->service_order_code,
                'service_code' => $item->medicalService->service_code, 'price_at_order' => $item->price_at_order,
                'service_status' => $item->serviceStatus->name, 'before' => $item->getAttributes(),
            ])->all(),
            'open_queues' => $queues->map(fn (ExaminationRoomQueue $queue): array => [
                'id' => $queue->id, 'medical_visit_id' => $queue->medical_visit_id,
                'visit_code' => $queue->medicalVisit->visit_code, 'queue_status' => $queue->status->name,
                'before' => $queue->getAttributes(),
            ])->all(),
        ];
    }

    /** @return array{payables_created: int, queues_completed: int, backup_path: ?string} */
    public function apply(): array
    {
        return DB::transaction(function (): array {
            $initial = $this->inspect();
            $visitIds = collect([...$initial['missing_payables'], ...$initial['open_queues']])->pluck('medical_visit_id')->unique()->sort()->values();
            MedicalVisit::whereIn('id', $visitIds)->orderBy('id')->lockForUpdate()->get();
            $plan = $this->inspect();
            if (! $plan['missing_payables'] && ! $plan['open_queues']) {
                return ['payables_created' => 0, 'queues_completed' => 0, 'backup_path' => null];
            }

            $items = ServiceOrderItem::with(['serviceStatus', 'serviceOrder', 'medicalService'])
                ->whereIn('id', array_column($plan['missing_payables'], 'id'))->orderBy('id')->lockForUpdate()->get();
            $queues = ExaminationRoomQueue::with('medicalVisit')->whereIn('id', array_column($plan['open_queues'], 'id'))->orderBy('id')->lockForUpdate()->get();
            foreach ($items as $item) {
                $ambiguous = Payable::where('medical_visit_id', $item->serviceOrder->medical_visit_id)
                    ->where('payment_type', 'SERVICE_FEE')->whereNull('service_order_item_id')->exists();
                if ($item->serviceStatus->name !== 'PENDING' || $item->started_at || $item->result
                    || (float) $item->price_at_order < 0 || $ambiguous) {
                    throw new RuntimeException('Dừng khôi phục: dòng dịch vụ '.$item->id.' cần đối chiếu lịch sử thanh toán thủ công.');
                }
            }
            foreach ($queues as $queue) {
                $endedAt = $queue->medicalVisit->examination_ended_at;
                if (! $endedAt || $endedAt->lt($queue->queued_at) || ($queue->service_started_at && $endedAt->lt($queue->service_started_at))) {
                    throw new RuntimeException('Dừng khôi phục: thời điểm hoàn tất lượt khám của hàng đợi '.$queue->id.' chưa đủ rõ ràng.');
                }
            }
            $unpaid = $items->isNotEmpty() ? PaymentStatus::where('name', 'UNPAID')->where('is_active', true)->first() : null;
            $completed = $queues->isNotEmpty() ? QueueStatus::where('name', 'COMPLETED')->where('is_active', true)->first() : null;
            if (($items->isNotEmpty() && ! $unpaid) || ($queues->isNotEmpty() && ! $completed)) {
                throw new RuntimeException('Thiếu cấu hình trạng thái chưa thanh toán hoặc hoàn tất hàng đợi đang hoạt động.');
            }

            $backupPath = 'workflow-integrity-backups/'.now()->format('Ymd-His').'-'.Str::uuid().'.json';
            if (! Storage::disk('local')->put($backupPath, json_encode([
                'database' => DB::getDatabaseName(), 'captured_at' => now()->toIso8601String(), 'before' => $plan,
            ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                throw new RuntimeException('Không ghi được bản sao lưu; không thay đổi dữ liệu.');
            }

            $lastNumber = 0;
            if ($items->isNotEmpty()) {
                foreach (Payable::orderBy('id')->lockForUpdate()->pluck('payable_code') as $code) {
                    if (preg_match('/^PT([0-9]+)$/', $code, $matches)) {
                        $lastNumber = max($lastNumber, (int) $matches[1]);
                    }
                }
            }
            foreach ($items as $item) {
                $payable = Payable::create([
                    'payable_code' => 'PT'.str_pad((string) ++$lastNumber, 6, '0', STR_PAD_LEFT),
                    'medical_visit_id' => $item->serviceOrder->medical_visit_id, 'service_order_item_id' => $item->id,
                    'payment_type' => 'SERVICE_FEE',
                    'description' => Str::limit('Phí dịch vụ '.$item->medicalService->name.' - '.$item->medicalService->service_code, 255, ''),
                    'amount' => $item->price_at_order, 'payment_status_id' => $unpaid->id,
                    'generated_at' => now(), 'paid_at' => null,
                    'notes' => 'Khôi phục khoản thu thiếu từ giá tại thời điểm chỉ định; chưa ghi nhận thanh toán.',
                ]);
                $this->audit('CREATE', 'payables', $payable->id, $payable->payable_code, null, $payable->getAttributes());
            }
            foreach ($queues as $queue) {
                $before = $queue->getAttributes();
                $queue->update(['queue_status_id' => $completed->id, 'completed_at' => $queue->completed_at ?? $queue->medicalVisit->examination_ended_at]);
                $this->audit('STATUS_CHANGE', 'examination_room_queues', $queue->id, null, $before, $queue->getAttributes());
            }

            return ['payables_created' => $items->count(), 'queues_completed' => $queues->count(), 'backup_path' => $backupPath];
        }, 3);
    }

    /** @param array<string, mixed>|null $before
     * @param  array<string, mixed>  $after
     */
    private function audit(string $action, string $entity, int $id, ?string $code, ?array $before, array $after): void
    {
        AuditLog::create([
            'user_id' => null, 'role_name_snapshot' => null, 'action' => $action, 'entity_type' => $entity,
            'entity_id' => $id, 'entity_business_code' => $code, 'old_values' => $before, 'new_values' => $after,
            'description' => 'Khôi phục tính nhất quán luồng khám bằng lệnh bảo trì; giữ nguyên dữ liệu lịch sử.',
            'performed_at' => now(),
        ]);
    }
}
