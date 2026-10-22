<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\ExaminationRoomQueue;
use App\Models\MedicalService;
use App\Models\Payable;
use App\Models\PaymentStatus;
use App\Models\QueueStatus;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderItem;
use App\Models\ServiceResult;
use App\Models\ServiceStatus;
use App\Models\User;
use App\Models\VisitStatus;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class CancelledServiceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 19)->setTime(9, 0));
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail());
        $this->order()->medicalVisit->update([
            'visit_status_id' => VisitStatus::where('name', 'WAITING_FOR_SERVICE')->value('id'),
            'examination_started_at' => now()->subHour(),
        ]);
    }

    private function order(): ServiceOrder
    {
        return ServiceOrder::where('service_order_code', 'CD000004')->firstOrFail();
    }

    private function cancel(Payable $payable): TestResponse
    {
        return $this->patch(route('admin.payables.cancel', $payable->id), ['confirm_cancel' => 1, 'cancel_reason' => 'Người bệnh không thực hiện dịch vụ này'])->assertRedirect();
    }

    private function finish(ServiceOrderItem $item): void
    {
        $this->post(route('admin.payables.payment-transactions.store', $item->payable), ['payment_method' => 'CASH', 'confirm_payment' => 1])->assertSessionHasNoErrors()->assertSessionMissing('error');
        $this->post(route('admin.service-order-items.start', $item))->assertSessionMissing('error');
        $this->post(route('admin.service-order-items.service-results.store', $item), ['result_summary' => 'Kết quả bình thường', 'resulted_at' => now()->format('Y-m-d H:i:s'), 'confirm_create' => 1])->assertSessionHasNoErrors()->assertSessionMissing('error');
        $this->post(route('admin.service-order-items.complete', $item))->assertSessionMissing('error');
        $this->assertSame('COMPLETED', $item->fresh()->serviceStatus->name);
    }

    private function returnQueue(): ExaminationRoomQueue
    {
        return $this->order()->medicalVisit->queues()->where('queue_type', 'RETURN_AFTER_SERVICE')->sole();
    }

    public function test_cancelled_item_is_ignored_when_remaining_service_completes(): void
    {
        [$cancelled, $remaining] = $this->order()->items()->orderBy('id')->get()->all();
        $this->cancel($cancelled->payable)->assertSessionHasNoErrors()->assertSessionMissing('error');
        $this->assertSame('CANCELLED', $cancelled->fresh()->serviceStatus->name);
        $this->assertSame('CANCELLED', $cancelled->payable->fresh()->paymentStatus->name);
        $this->assertNull($cancelled->fresh()->started_at);
        $this->assertNull($cancelled->fresh()->completed_at);
        $this->assertNull($cancelled->fresh()->result);
        $this->assertFalse($this->order()->medicalVisit->queues()->where('queue_type', 'RETURN_AFTER_SERVICE')->exists());
        $this->post(route('admin.service-order-items.start', $cancelled))->assertSessionHas('error');
        $this->finish($remaining);
        $queue = $this->returnQueue();
        $this->assertSame('COMPLETED', $this->order()->status);
        $this->assertSame('WAITING', $queue->status->name);
        $this->assertSame('WAITING_FOR_SERVICE', $queue->medicalVisit->status->name);
        $this->post(route('admin.queues.call', $queue))->assertSessionMissing('error');
        $this->post(route('admin.queues.start-service', $queue))->assertSessionHasNoErrors();
        $this->assertSame('RETURNING', $queue->medicalVisit->fresh()->status->name);
        $this->post(route('admin.visits.conclusion.store', $queue->medicalVisit), [
            'professional_conclusion' => 'Đã đánh giá các dịch vụ còn lại', 'has_follow_up' => 0,
        ])->assertSessionHasNoErrors()->assertSessionMissing('error');
        $this->post(route('admin.queues.complete', $queue))->assertSessionMissing('error');
        $this->patch(route('admin.visits.update-status', $queue->medicalVisit), ['visit_status_id' => VisitStatus::where('name', 'COMPLETED')->value('id')])->assertRedirect()->assertSessionHasNoErrors()->assertSessionMissing('error');
        $this->assertSame('COMPLETED', $queue->medicalVisit->fresh()->status->name);
    }

    public function test_cancelling_last_item_enqueues_once_and_keeps_history(): void
    {
        [$completed, $last] = $this->order()->items()->orderBy('id')->get()->all();
        $this->finish($completed);
        $this->assertFalse($this->order()->medicalVisit->queues()->where('queue_type', 'RETURN_AFTER_SERVICE')->exists());
        $amount = $last->payable->amount;
        $this->cancel($last->payable)->assertSessionHasNoErrors()->assertSessionMissing('error');
        $queueId = $this->returnQueue()->id;
        $notes = $last->payable->fresh()->notes;
        $logs = AuditLog::count();
        $this->cancel($last->payable)->assertSessionHasNoErrors()->assertSessionMissing('error');
        $this->assertSame($queueId, $this->returnQueue()->id);
        $this->assertSame($notes, $last->payable->fresh()->notes);
        $this->assertSame($logs, AuditLog::count());
        $this->assertSame($amount, $last->payable->fresh()->amount);
        $this->assertNotNull($completed->fresh()->result);
        $this->assertDatabaseHas('service_order_items', ['id' => $last->id]);
        $this->assertSame('COMPLETED', $this->order()->status);
    }

    public function test_cancelling_all_services_still_returns_patient_to_doctor(): void
    {
        foreach ($this->order()->items as $item) {
            $this->cancel($item->payable)->assertSessionHasNoErrors()->assertSessionMissing('error');
        }
        $queue = $this->returnQueue();
        $this->assertSame('WAITING', $queue->status->name);
        $this->assertSame('WAITING_FOR_SERVICE', $queue->medicalVisit->status->name);
        $this->assertSame('COMPLETED', $this->order()->status);
        $this->post(route('admin.queues.call', $queue))->assertSessionMissing('error');
        $this->post(route('admin.queues.start-service', $queue))->assertSessionHasNoErrors();
        $this->assertSame('RETURNING', $queue->medicalVisit->fresh()->status->name);
    }

    public function test_other_orders_with_pending_services_still_block_return(): void
    {
        $order = $this->order();
        $other = ServiceOrder::create(['service_order_code' => 'CDTEST100', 'medical_visit_id' => $order->medical_visit_id, 'ordered_by_doctor_id' => $order->ordered_by_doctor_id, 'ordered_at' => now(), 'status' => 'PENDING']);
        $item = $other->items()->create(['medical_service_id' => MedicalService::where('service_code', 'DVXN003')->value('id'), 'service_status_id' => ServiceStatus::where('name', 'PENDING')->value('id'), 'price_at_order' => 1000]);
        $payable = Payable::create(['payable_code' => 'PTTEST100', 'medical_visit_id' => $order->medical_visit_id, 'service_order_item_id' => $item->id, 'payment_type' => 'SERVICE_FEE', 'description' => 'Dịch vụ của phiếu thứ hai', 'amount' => 1000, 'payment_status_id' => PaymentStatus::where('name', 'UNPAID')->value('id'), 'generated_at' => now()]);
        foreach ($order->items as $oldItem) {
            $this->cancel($oldItem->payable)->assertSessionHasNoErrors();
        }
        $this->assertFalse($order->medicalVisit->queues()->where('queue_type', 'RETURN_AFTER_SERVICE')->exists());
        $this->cancel($payable)->assertSessionHasNoErrors()->assertSessionMissing('error');
        $this->assertSame('WAITING', $this->returnQueue()->status->name);
    }

    public function test_started_completed_or_result_bearing_services_cannot_be_cancelled(): void
    {
        $item = $this->order()->items()->firstOrFail();
        foreach (['PROCESSING', 'COMPLETED'] as $name) {
            $item->update(['service_status_id' => ServiceStatus::where('name', $name)->value('id'), 'started_at' => now(), 'completed_at' => $name === 'COMPLETED' ? now() : null]);
            $this->cancel($item->payable)->assertSessionHasErrors('cancel_reason');
            $this->assertSame($name, $item->fresh()->serviceStatus->name);
            $this->assertSame('UNPAID', $item->payable->fresh()->paymentStatus->name);
        }
        $item->update(['service_status_id' => ServiceStatus::where('name', 'PENDING')->value('id'), 'started_at' => null, 'completed_at' => null]);
        ServiceResult::create(['service_order_item_id' => $item->id, 'result_summary' => 'Giữ kết quả lịch sử']);
        $this->cancel($item->payable)->assertSessionHasErrors('cancel_reason');
        $this->assertSame('PENDING', $item->fresh()->serviceStatus->name);
        $this->assertNotNull($item->fresh()->result);
    }

    public function test_paid_payable_cannot_cancel_service_or_refund_transaction(): void
    {
        $item = $this->order()->items()->firstOrFail();
        $this->post(route('admin.payables.payment-transactions.store', $item->payable), ['payment_method' => 'CASH', 'confirm_payment' => 1])->assertSessionHasNoErrors();
        $count = $item->payable->transactions()->count();
        $this->cancel($item->payable)->assertSessionHasErrors('cancel_reason');
        $this->assertSame('PENDING', $item->fresh()->serviceStatus->name);
        $this->assertSame('PAID', $item->payable->fresh()->paymentStatus->name);
        $this->assertSame($count, $item->payable->transactions()->count());
        $item->payable->update(['payment_status_id' => PaymentStatus::where('name', 'UNPAID')->value('id'), 'paid_at' => null]);
        $this->cancel($item->payable)->assertSessionHasErrors('cancel_reason');
        $this->assertSame('PENDING', $item->fresh()->serviceStatus->name);
    }

    public function test_examination_fee_cancellation_does_not_cancel_service_items(): void
    {
        $order = $this->order();
        $fee = Payable::where('medical_visit_id', $order->medical_visit_id)->whereNull('service_order_item_id')->firstOrFail();
        $this->cancel($fee)->assertSessionHasNoErrors()->assertSessionMissing('error');
        $this->assertSame('CANCELLED', $fee->fresh()->paymentStatus->name);
        $this->assertSame(2, $order->items()->unresolved()->count());
        $this->assertFalse($order->medicalVisit->queues()->where('queue_type', 'RETURN_AFTER_SERVICE')->exists());
    }

    public function test_enqueue_failure_rolls_back_service_payable_and_audit_changes(): void
    {
        [$first, $last] = $this->order()->items()->orderBy('id')->get()->all();
        $this->cancel($first->payable)->assertSessionHasNoErrors();
        $count = AuditLog::count();
        $orderStatus = $this->order()->status;
        QueueStatus::where('name', 'WAITING')->update(['is_active' => false]);
        $this->cancel($last->payable)->assertSessionHas('error');
        $this->assertSame('PENDING', $last->fresh()->serviceStatus->name);
        $this->assertSame('UNPAID', $last->payable->fresh()->paymentStatus->name);
        $this->assertSame($orderStatus, $this->order()->status);
        $this->assertSame($count, AuditLog::count());
        $this->assertFalse($this->order()->medicalVisit->queues()->where('queue_type', 'RETURN_AFTER_SERVICE')->exists());
    }
}
