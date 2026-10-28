<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\ExaminationRoomQueue;
use App\Models\MedicalService;
use App\Models\MedicalVisit;
use App\Models\Payable;
use App\Models\PaymentStatus;
use App\Models\PaymentTransaction;
use App\Models\QueueStatus;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderItem;
use App\Models\ServiceStatus;
use App\Models\WorkflowIntegrityRepair;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class WorkflowIntegrityRepairTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 19)->setTime(9, 0));
        $this->seed(DatabaseSeeder::class);
        Storage::fake('local');
    }

    private function missingItem(): ServiceOrderItem
    {
        $source = ServiceOrder::where('service_order_code', 'CD000004')->firstOrFail();
        $order = ServiceOrder::create([
            'service_order_code' => 'CDREPAIR', 'medical_visit_id' => $source->medical_visit_id,
            'ordered_by_doctor_id' => $source->ordered_by_doctor_id, 'ordered_at' => now()->subDay(), 'status' => 'ACTIVE',
        ]);

        return $order->items()->create([
            'medical_service_id' => MedicalService::where('service_code', 'DVXN003')->value('id'),
            'service_status_id' => ServiceStatus::where('name', 'PENDING')->value('id'), 'price_at_order' => 1000,
        ]);
    }

    private function openQueue(): ExaminationRoomQueue
    {
        $visit = MedicalVisit::where('visit_code', 'LK000003')->firstOrFail();
        $visit->update(['examination_started_at' => now()->subHour(), 'examination_ended_at' => now()->subMinutes(10)]);

        return $visit->queues()->create([
            'queue_status_id' => QueueStatus::where('name', 'IN_SERVICE')->value('id'),
            'queue_number' => 101, 'queue_type' => 'INITIAL_EXAM', 'queued_at' => now()->subMinutes(40),
            'service_started_at' => now()->subMinutes(30), 'call_count' => 2,
        ]);
    }

    public function test_repair_uses_historical_price_closes_queue_backs_up_and_is_idempotent(): void
    {
        $item = $this->missingItem();
        $queue = $this->openQueue();
        $item->medicalService->update(['price' => 9000]);
        $beforeQueue = $queue->getAttributes();
        $transactionCount = PaymentTransaction::count();
        $result = app(WorkflowIntegrityRepair::class)->apply();
        $this->assertSame(1, $result['payables_created']);
        $this->assertSame(1, $result['queues_completed']);
        Storage::disk('local')->assertExists($result['backup_path']);
        $backup = json_decode(Storage::disk('local')->get($result['backup_path']), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($beforeQueue['queue_status_id'], $backup['before']['open_queues'][0]['before']['queue_status_id']);
        $payable = $item->fresh()->payable;
        $this->assertSame('1000.00', $payable->amount);
        $this->assertSame('UNPAID', $payable->paymentStatus->name);
        $this->assertNull($payable->paid_at);
        $this->assertSame($transactionCount, PaymentTransaction::count());
        $this->assertSame('COMPLETED', $queue->fresh()->status->name);
        $this->assertTrue($queue->fresh()->completed_at->equalTo($queue->medicalVisit->examination_ended_at));
        $this->assertSame(2, $queue->fresh()->call_count);
        $logs = AuditLog::count();
        $this->assertSame(['payables_created' => 0, 'queues_completed' => 0, 'backup_path' => null], app(WorkflowIntegrityRepair::class)->apply());
        $this->assertSame($logs, AuditLog::count());
    }

    public function test_command_is_readonly_unless_apply_is_explicit(): void
    {
        $item = $this->missingItem();
        $count = Payable::count();
        $this->artisan('mediway:repair-workflow-integrity')->assertExitCode(0);
        $this->assertSame($count, Payable::count());
        $this->assertNull($item->fresh()->payable);
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->artisan('mediway:repair-workflow-integrity', ['--apply' => true])->assertExitCode(0);
        $this->assertNotNull($item->fresh()->payable);
    }

    public function test_ambiguous_billing_aborts_the_entire_repair_without_guessing(): void
    {
        $item = $this->missingItem();
        $queue = $this->openQueue();
        Payable::create([
            'payable_code' => 'PTAMB001', 'medical_visit_id' => $item->serviceOrder->medical_visit_id,
            'payment_type' => 'SERVICE_FEE', 'description' => 'Khoản thu cũ chưa xác định dịch vụ', 'amount' => 1000,
            'payment_status_id' => PaymentStatus::where('name', 'UNPAID')->value('id'), 'generated_at' => now(),
        ]);
        $count = Payable::count();
        try {
            app(WorkflowIntegrityRepair::class)->apply();
            $this->fail('Phải dừng khi có khoản thu dịch vụ mất liên kết chưa được đối chiếu.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('đối chiếu lịch sử', $exception->getMessage());
        }
        $this->assertSame($count, Payable::count());
        $this->assertNull($item->fresh()->payable);
        $this->assertSame('IN_SERVICE', $queue->fresh()->status->name);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_repair_does_not_invent_a_missing_visit_completion_time(): void
    {
        $queue = $this->openQueue();
        $queue->medicalVisit->update(['examination_ended_at' => null]);
        $this->expectException(RuntimeException::class);
        app(WorkflowIntegrityRepair::class)->apply();
    }
}
