<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentTimeSlot;
use App\Models\Disease;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\ExaminationRoom;
use App\Models\ExaminationRoomQueue;
use App\Models\MedicalService;
use App\Models\MedicalVisit;
use App\Models\Patient;
use App\Models\PaymentStatus;
use App\Models\QueueStatus;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderItem;
use App\Models\Specialty;
use App\Models\User;
use App\Models\VisitStatus;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MediwayWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-19 07:00:00'));
        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail());
    }

    private function slot(): AppointmentTimeSlot
    {
        $schedule = DoctorSchedule::create([
            'doctor_id' => Doctor::where('doctor_code', 'BS000001')->value('id'),
            'specialty_id' => Specialty::where('specialty_code', 'CKTMH')->value('id'),
            'examination_room_id' => ExaminationRoom::where('room_code', 'PKTMH01')->value('id'),
            'work_date' => now()->toDateString(), 'start_time' => '09:00:00', 'end_time' => '11:00:00', 'status' => 'ACTIVE',
        ]);

        return AppointmentTimeSlot::create(['doctor_schedule_id' => $schedule->id, 'start_time' => '09:00:00', 'end_time' => '09:30:00', 'max_patients' => 1, 'status' => 'ACTIVE']);
    }

    private function book(AppointmentTimeSlot $slot): Appointment
    {
        $this->post(route('admin.appointments.store'), [
            'patient_id' => Patient::where('patient_code', 'BN000005')->value('id'),
            'appointment_time_slot_id' => $slot->id, 'reason_for_visit' => 'Khám kiểm thử',
            'doctor_id' => Doctor::where('doctor_code', 'BS000002')->value('id'),
        ])->assertSessionHasNoErrors()->assertRedirect();

        return Appointment::where('appointment_time_slot_id', $slot->id)->sole();
    }

    public function test_complete_patient_journey_from_booking_through_return_to_doctor(): void
    {
        $slot = $this->slot();
        $appointment = $this->book($slot);
        $this->assertEquals($slot->doctorSchedule->doctor_id, $appointment->doctor_id);
        $this->travelTo(today()->setTime(9, 0));
        $this->post(route('admin.appointments.check-in', $appointment))->assertSessionHasNoErrors();
        $visit = $appointment->fresh()->medicalVisit;
        $this->assertSame('WAITING', $visit->status->name);
        $queue = $visit->queues()->sole();
        $this->assertSame('INITIAL_EXAM', $queue->queue_type);
        $this->post(route('admin.queues.start-service', $queue))->assertSessionHas('error');
        $this->post(route('admin.queues.call', $queue))->assertSessionHasNoErrors();
        $this->assertSame('CALLED', $queue->fresh()->status->name);
        $this->post(route('admin.queues.start-service', $queue))->assertSessionHasNoErrors();
        $this->assertSame('IN_SERVICE', $queue->fresh()->status->name);
        $this->assertSame('IN_EXAMINATION', $visit->fresh()->status->name);
        $this->post(route('admin.visits.clinical-note.store', $visit), ['reason_for_visit' => 'Đau họng'])->assertSessionHasNoErrors();
        $this->post(route('admin.visits.diagnoses.store', $visit), ['disease_id' => Disease::where('disease_code', 'J01.9')->value('id'), 'diagnosis_type' => 'PRIMARY'])->assertSessionHasNoErrors();
        $this->post(route('admin.visits.conclusion.store', $visit), ['professional_conclusion' => 'Cần kiểm tra dịch vụ', 'has_follow_up' => 0])->assertSessionHasNoErrors();
        $serviceIds = MedicalService::whereIn('service_code', ['DVXN001', 'DVXN002'])->pluck('id')->all();
        $this->get(route('admin.visits.service-orders.create', $visit))->assertOk();
        $this->post(route('admin.visits.service-orders.store', $visit), ['medical_service_ids' => $serviceIds])->assertSessionHasNoErrors();
        $order = ServiceOrder::where('medical_visit_id', $visit->id)->sole();
        $this->assertSame('WAITING_FOR_SERVICE', $visit->fresh()->status->name);
        $this->post(route('admin.queues.complete', $queue))->assertSessionHasNoErrors();
        foreach ($order->items as $index => $item) {
            $this->post(route('admin.service-order-items.start', $item))->assertSessionHas('error');
            $this->assertSame('PENDING', $item->fresh()->serviceStatus->name);
            $this->post(route('admin.payables.payment-transactions.store', $item->payable), ['payment_method' => 'CASH', 'confirm_payment' => 1])->assertSessionHasNoErrors();
            $this->assertTrue($item->payable->fresh()->paymentStatus->allows_service_execution);
            $this->post(route('admin.service-order-items.start', $item))->assertSessionHasNoErrors();
            $this->assertSame('PROCESSING', $item->fresh()->serviceStatus->name);
            $this->post(route('admin.service-order-items.complete', $item))->assertSessionHas('error');
            $this->post(route('admin.service-order-items.service-results.store', $item), ['result_summary' => 'Kết quả kiểm thử', 'resulted_at' => now()->format('Y-m-d H:i:s'), 'confirm_create' => 1])->assertSessionHasNoErrors();
            $this->post(route('admin.service-order-items.complete', $item))->assertSessionHasNoErrors();
            $this->assertSame('COMPLETED', $item->fresh()->serviceStatus->name);
            if ($index === 0) {
                $this->assertFalse($visit->queues()->where('queue_type', 'RETURN_AFTER_SERVICE')->exists());
            }
        }
        $this->assertSame('COMPLETED', $order->fresh()->status);
        $returnQueue = $visit->queues()->where('queue_type', 'RETURN_AFTER_SERVICE')->sole();
        $this->assertSame('WAITING', $returnQueue->status->name);
        $this->assertSame('WAITING_FOR_SERVICE', $visit->fresh()->status->name);
        $this->post(route('admin.service-order-items.complete', $order->items->last()))->assertSessionHasNoErrors();
        $this->assertSame(1, $visit->queues()->where('queue_type', 'RETURN_AFTER_SERVICE')->count());
        $this->post(route('admin.queues.call', $returnQueue))->assertSessionHasNoErrors();
        $this->assertSame('WAITING_FOR_SERVICE', $visit->fresh()->status->name);
        $this->post(route('admin.queues.start-service', $returnQueue))->assertSessionHasNoErrors();
        $this->assertSame('RETURNING', $visit->fresh()->status->name);
        $this->post(route('admin.queues.complete', $returnQueue))->assertSessionHasNoErrors();
        $this->patch(route('admin.visits.update-status', $visit), ['visit_status_id' => VisitStatus::where('name', 'COMPLETED')->value('id')])->assertSessionHasNoErrors();
        $this->assertSame('COMPLETED', $visit->fresh()->status->name);
        $this->assertNotNull($visit->fresh()->examination_ended_at);
    }

    public function test_queue_can_start_after_the_visit_was_started_from_visit_details(): void
    {
        $appointment = $this->book($this->slot());
        $this->travelTo(today()->setTime(9, 0));
        $this->post(route('admin.appointments.check-in', $appointment))->assertSessionHasNoErrors();
        $visit = $appointment->fresh()->medicalVisit;
        $this->patch(route('admin.visits.update-status', $visit), ['visit_status_id' => VisitStatus::where('name', 'IN_EXAMINATION')->value('id')])->assertSessionHasNoErrors();
        $queue = $visit->queues()->sole();
        $this->post(route('admin.queues.call', $queue))->assertSessionHasNoErrors();
        $this->post(route('admin.queues.start-service', $queue))->assertSessionHasNoErrors();
        $this->assertSame('IN_SERVICE', $queue->fresh()->status->name);
        $this->assertSame('IN_EXAMINATION', $visit->fresh()->status->name);
    }

    public function test_schedule_cannot_be_shortened_to_exclude_existing_unbooked_slots(): void
    {
        $schedule = $this->slot()->doctorSchedule;
        $this->put(route('admin.doctor-schedules.update', $schedule), [
            'doctor_id' => $schedule->doctor_id, 'specialty_id' => $schedule->specialty_id,
            'examination_room_id' => $schedule->examination_room_id, 'work_date' => $schedule->work_date->format('Y-m-d'),
            'start_time' => '09:00', 'end_time' => '09:15', 'status' => 'ACTIVE',
        ])->assertSessionHasErrors('end_time');
        $this->assertSame('11:00:00', $schedule->fresh()->end_time);
    }

    public function test_service_orders_require_an_active_examination(): void
    {
        $visit = MedicalVisit::where('visit_code', 'LK000004')->firstOrFail();
        $visit->update(['visit_status_id' => VisitStatus::where('name', 'WAITING')->value('id')]);
        $before = ServiceOrder::where('medical_visit_id', $visit->id)->count();
        $this->post(route('admin.visits.service-orders.store', $visit), ['medical_service_ids' => [MedicalService::where('service_code', 'DVXN003')->value('id')]]);
        $this->assertSame($before, ServiceOrder::where('medical_visit_id', $visit->id)->count());
        $this->assertSame('WAITING', $visit->fresh()->status->name);
    }

    public function test_returning_cannot_be_set_manually_before_return_queue_service(): void
    {
        $visit = MedicalVisit::where('visit_code', 'LK000004')->firstOrFail();
        $visit->update(['visit_status_id' => VisitStatus::where('name', 'WAITING_FOR_SERVICE')->value('id')]);
        $this->patch(route('admin.visits.update-status', $visit), ['visit_status_id' => VisitStatus::where('name', 'RETURNING')->value('id')]);
        $this->assertSame('WAITING_FOR_SERVICE', $visit->fresh()->status->name);
    }

    public function test_return_queue_cannot_start_while_any_service_is_incomplete(): void
    {
        $visit = MedicalVisit::where('visit_code', 'LK000004')->firstOrFail();
        $visit->update(['visit_status_id' => VisitStatus::where('name', 'WAITING_FOR_SERVICE')->value('id')]);
        $queue = ExaminationRoomQueue::create([
            'medical_visit_id' => $visit->id, 'queue_status_id' => QueueStatus::where('name', 'CALLED')->value('id'),
            'queue_number' => 99, 'queue_type' => 'RETURN_AFTER_SERVICE', 'queued_at' => now(), 'call_count' => 1,
        ]);
        $this->post(route('admin.queues.start-service', $queue));
        $this->assertSame('CALLED', $queue->fresh()->status->name);
        $this->assertSame('WAITING_FOR_SERVICE', $visit->fresh()->status->name);
    }

    public function test_service_execution_uses_permission_flag_instead_of_paid_code(): void
    {
        $item = ServiceOrderItem::whereHas('serviceOrder', fn ($q) => $q->where('service_order_code', 'CD000004'))->firstOrFail();
        $status = PaymentStatus::forceCreate(['name' => 'APPROVED_CREDIT', 'allows_service_execution' => true, 'process_order' => 10, 'is_active' => true]);
        $item->payable->update(['payment_status_id' => $status->id]);
        $this->post(route('admin.service-order-items.start', $item))->assertSessionHasNoErrors();
        $this->assertSame('PROCESSING', $item->fresh()->serviceStatus->name);
    }

    public function test_full_inactive_past_or_outside_schedule_slots_cannot_be_booked(): void
    {
        $slot = $this->slot();
        $this->book($slot);
        $data = ['patient_id' => Patient::where('patient_code', 'BN000001')->value('id'), 'appointment_time_slot_id' => $slot->id, 'reason_for_visit' => 'Kiểm thử'];
        $this->post(route('admin.appointments.store'), $data)->assertSessionHasErrors('appointment_time_slot_id');
        $slot->update(['max_patients' => 2, 'status' => 'INACTIVE']);
        $this->post(route('admin.appointments.store'), $data)->assertSessionHasErrors('appointment_time_slot_id');
        $slot->update(['status' => 'ACTIVE', 'start_time' => '06:00:00', 'end_time' => '06:30:00']);
        $this->post(route('admin.appointments.store'), $data)->assertSessionHasErrors('appointment_time_slot_id');
        $slot->update(['start_time' => '12:00:00', 'end_time' => '12:30:00']);
        $this->post(route('admin.appointments.store'), $data)->assertSessionHasErrors('appointment_time_slot_id');
    }
}
