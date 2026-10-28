<?php

namespace Tests\Feature;

use App\Models\Disease;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\ExaminationRoom;
use App\Models\MedicalVisit;
use App\Models\Patient;
use App\Models\PaymentStatus;
use App\Models\QueueStatus;
use App\Models\ServiceOrder;
use App\Models\Specialty;
use App\Models\User;
use App\Models\VisitStatus;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 19)->setTime(9, 0));
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail());
    }

    private function visit(string $status = 'IN_EXAMINATION'): MedicalVisit
    {
        return MedicalVisit::create([
            'visit_code' => 'LKTEST'.MedicalVisit::count(),
            'patient_id' => Patient::where('patient_code', 'BN000005')->value('id'),
            'doctor_id' => Doctor::where('doctor_code', 'BS000001')->value('id'),
            'specialty_id' => Specialty::where('specialty_code', 'CKTMH')->value('id'),
            'examination_room_id' => ExaminationRoom::where('room_code', 'PKTMH01')->value('id'),
            'visit_status_id' => VisitStatus::where('name', $status)->value('id'),
            'checked_in_at' => now()->subHour(), 'examination_started_at' => $status === 'WAITING' ? null : now(),
            'reception_source' => 'COUNTER',
        ]);
    }

    public function test_cancelled_payable_cannot_be_reopened_or_charged(): void
    {
        $item = ServiceOrder::where('service_order_code', 'CD000004')->firstOrFail()->items()->firstOrFail();
        $transactionCount = $item->payable->transactions()->count();
        $this->patch(route('admin.payables.cancel', $item->payable), [
            'confirm_cancel' => 1, 'cancel_reason' => 'Không thực hiện dịch vụ',
        ])->assertSessionHasNoErrors()->assertSessionMissing('error');
        foreach (['UNPAID', 'PENDING', 'PAID'] as $status) {
            $this->patch(route('admin.payables.update-status', $item->payable), [
                'payment_status_id' => PaymentStatus::where('name', $status)->value('id'), 'confirm_update' => 1,
            ])->assertSessionHasErrors('payment_status_id');
            $this->assertSame('CANCELLED', $item->payable->fresh()->paymentStatus->name);
        }
        $item->payable->fresh()->update(['payment_status_id' => PaymentStatus::where('name', 'UNPAID')->value('id')]);
        $this->post(route('admin.payables.payment-transactions.store', $item->payable), [
            'payment_method' => 'CASH', 'confirm_payment' => 1,
        ])->assertSessionHasErrors('payment_method');
        $this->assertSame($transactionCount, $item->payable->transactions()->count());
        $this->assertSame('CANCELLED', $item->fresh()->serviceStatus->name);
    }

    public function test_paid_history_cannot_be_reset_by_manual_status_update(): void
    {
        $payable = ServiceOrder::where('service_order_code', 'CD000004')->firstOrFail()->items()->firstOrFail()->payable;
        $this->patch(route('admin.payables.update-status', $payable), [
            'payment_status_id' => PaymentStatus::where('name', 'PAID')->value('id'), 'confirm_update' => 1,
        ])->assertSessionHasErrors('payment_status_id');
        $this->post(route('admin.payables.payment-transactions.store', $payable), [
            'payment_method' => 'CASH', 'confirm_payment' => 1,
        ])->assertSessionHasNoErrors();
        $this->patch(route('admin.payables.update-status', $payable), [
            'payment_status_id' => PaymentStatus::where('name', 'UNPAID')->value('id'), 'confirm_update' => 1,
        ])->assertSessionHasErrors('payment_status_id');
        $this->assertSame('PAID', $payable->fresh()->paymentStatus->name);
        $this->assertSame(1, $payable->transactions()->where('transaction_result', 'SUCCESS')->count());
    }

    public function test_waiting_for_service_is_only_entered_by_creating_an_order(): void
    {
        $visit = $this->visit();
        $this->patch(route('admin.visits.update-status', $visit), [
            'visit_status_id' => VisitStatus::where('name', 'WAITING_FOR_SERVICE')->value('id'),
        ])->assertSessionHas('error');
        $this->assertSame('IN_EXAMINATION', $visit->fresh()->status->name);
        $this->assertSame(0, $visit->serviceOrders()->count());
        $this->get(route('admin.visits.show', $visit))->assertOk()->assertViewHas('allowedVisitStatuses',
            fn ($statuses): bool => ! $statuses->contains('name', 'WAITING_FOR_SERVICE'));
    }

    public function test_completing_a_visit_closes_all_open_queues_and_blocks_further_calls(): void
    {
        $visit = $this->visit();
        $this->post(route('admin.visits.diagnoses.store', $visit), [
            'disease_id' => Disease::where('disease_code', 'J01.9')->value('id'), 'diagnosis_type' => 'PRIMARY',
        ])->assertSessionHasNoErrors()->assertSessionMissing('error');
        $this->post(route('admin.visits.conclusion.store', $visit), [
            'professional_conclusion' => 'Đủ điều kiện kết thúc khám', 'has_follow_up' => 0,
        ])->assertSessionHasNoErrors()->assertSessionMissing('error');
        $queues = collect();
        foreach (['WAITING', 'CALLED', 'IN_SERVICE', 'SKIPPED'] as $number => $status) {
            $queues->push($visit->queues()->create([
                'queue_status_id' => QueueStatus::where('name', $status)->value('id'),
                'queue_number' => 101 + $number, 'queue_type' => 'INITIAL_EXAM', 'queued_at' => now(), 'call_count' => 0,
            ]));
        }
        $this->patch(route('admin.visits.update-status', $visit), [
            'visit_status_id' => VisitStatus::where('name', 'COMPLETED')->value('id'),
        ])->assertSessionHasNoErrors()->assertSessionMissing('error');
        foreach ($queues as $queue) {
            $this->assertSame('COMPLETED', $queue->fresh()->status->name);
            $this->assertTrue($queue->fresh()->completed_at->equalTo($visit->fresh()->examination_ended_at));
            $this->post(route('admin.queues.call', $queue))->assertSessionHas('error');
            $this->assertSame(0, $queue->fresh()->call_count);
        }
    }

    public function test_legacy_open_queue_of_a_completed_visit_cannot_be_called(): void
    {
        $visit = $this->visit('COMPLETED');
        $queue = $visit->queues()->create([
            'queue_status_id' => QueueStatus::where('name', 'WAITING')->value('id'),
            'queue_number' => 101, 'queue_type' => 'INITIAL_EXAM', 'queued_at' => now(), 'call_count' => 0,
        ]);
        $this->post(route('admin.queues.call', $queue))->assertSessionHasErrors('queue');
        $this->assertSame('WAITING', $queue->fresh()->status->name);
    }

    public function test_clinical_writes_are_blocked_before_examination_and_while_waiting_for_services(): void
    {
        foreach (['WAITING', 'WAITING_FOR_SERVICE'] as $status) {
            $visit = $this->visit($status);
            $this->post(route('admin.visits.clinical-note.store', $visit), ['reason_for_visit' => 'Kiểm thử'])->assertSessionHas('error');
            $this->post(route('admin.visits.diagnoses.store', $visit), [
                'disease_id' => Disease::where('disease_code', 'J01.9')->value('id'), 'diagnosis_type' => 'PRIMARY',
            ])->assertSessionHas('error');
            $this->post(route('admin.visits.conclusion.store', $visit), ['professional_conclusion' => 'Kiểm thử', 'has_follow_up' => 0])->assertSessionHas('error');
            $this->assertNull($visit->clinicalNote);
            $this->assertSame(0, $visit->diagnoses()->count());
            $this->assertNull($visit->conclusion);
        }
    }

    public function test_active_schedules_reject_inactive_parents_but_allow_inactive_history(): void
    {
        $doctor = Doctor::where('doctor_code', 'BS000001')->firstOrFail();
        $specialty = Specialty::where('specialty_code', 'CKTMH')->firstOrFail();
        $room = ExaminationRoom::where('room_code', 'PKTMH01')->firstOrFail();
        $data = ['doctor_id' => $doctor->id, 'specialty_id' => $specialty->id, 'examination_room_id' => $room->id,
            'work_date' => '2026-10-28', 'start_time' => '09:00', 'end_time' => '10:00', 'status' => 'ACTIVE'];
        foreach (['doctor_id' => $doctor, 'specialty_id' => $specialty, 'examination_room_id' => $room] as $field => $parent) {
            $parent->update(['status' => 'INACTIVE']);
            $this->post(route('admin.doctor-schedules.store'), $data)->assertSessionHasErrors($field);
            $parent->update(['status' => 'ACTIVE']);
        }
        $doctor->update(['status' => 'INACTIVE']);
        $data['status'] = 'INACTIVE';
        $this->post(route('admin.doctor-schedules.store'), $data)->assertSessionHasNoErrors();
        $schedule = DoctorSchedule::where('doctor_id', $doctor->id)->whereDate('work_date', '2026-10-28')->sole();
        $this->put(route('admin.doctor-schedules.update', $schedule), [...$data, 'status' => 'ACTIVE'])->assertSessionHasErrors('doctor_id');
        $this->assertSame('INACTIVE', $schedule->fresh()->status);
    }
}
