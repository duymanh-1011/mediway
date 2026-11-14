<?php

namespace Tests\Feature\Portal;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\ExaminationRoom;
use App\Models\MedicalService;
use App\Models\ServiceOrder;
use App\Models\User;
use Carbon\Carbon;
use Tests\Fixtures\Seeders\WorkflowFixtureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientTeamIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_portal_booking_connects_reception_queue_and_cashier_to_patient_views(): void
    {
        $this->travelTo(Carbon::parse('2026-10-27 08:00:00'));
        $this->seed(WorkflowFixtureSeeder::class);
        $this->withoutVite();
        $admin = User::where('business_code', 'QT000001')->firstOrFail();
        $patient = User::where('business_code', 'BN000001')->firstOrFail();
        $other = User::where('business_code', 'BN000002')->firstOrFail();
        $patient->patient->detail->update(['current_address' => '12 Pho Bach Mai, Ha Noi']);
        $doctor = Doctor::where('doctor_code', 'BS000001')->firstOrFail();
        $room = ExaminationRoom::where('room_code', 'PKTMH01')->firstOrFail();
        $date = today()->addDay()->toDateString();

        $this->actingAs($admin)->put(route('admin.doctor-schedules.month.update', $doctor), [
            'date' => $date, 'shift' => 'MORNING', 'state' => 'WORKING', 'specialty_id' => $doctor->specialty_id, 'room_id' => $room->id,
        ])->assertSessionHasNoErrors();
        $schedule = DoctorSchedule::where('doctor_id', $doctor->id)->whereDate('work_date', $date)->where('shift_code', 'MORNING')->sole();
        $slot = $schedule->appointmentTimeSlots()->where('start_time', '09:00:00')->sole();

        $this->actingAs($patient)->get(route('patient.doctors.index', ['date' => $date]))
            ->assertOk()->assertSee($doctor->full_name)->assertSee('data-book-url="'.route('patient.appointments.create', $slot).'"', false);
        $this->post(route('patient.appointments.store'), [
            'appointment_time_slot_id' => $slot->id, 'reason_for_visit' => 'Kiem tra suc khoe', 'confirmed_information' => '1',
        ])->assertSessionHasNoErrors();
        $appointment = Appointment::where('patient_id', $patient->patient->id)->where('appointment_time_slot_id', $slot->id)->sole();
        $this->actingAs($admin)->get(route('admin.appointments.show', $appointment))->assertOk()->assertSee($appointment->appointment_code);

        $this->travelTo(Carbon::parse($date.' 09:00:00'));
        $this->post(route('admin.appointments.check-in', $appointment))->assertSessionHasNoErrors();
        $visit = $appointment->fresh()->medicalVisit;
        $this->assertNotNull($visit);
        $fee = $visit->payables()->where('payment_type', 'EXAMINATION_FEE')->sole();
        $this->assertSame('300000.00', $fee->amount);
        $this->assertSame(0, $visit->queues()->count());
        $this->get(route('admin.visits.show', $visit))->assertOk()->assertSee('Chờ thanh toán phí khám');
        $this->patch(route('admin.visits.update-status', $visit), [
            'visit_status_id' => \App\Models\VisitStatus::where('name', 'IN_EXAMINATION')->value('id'),
        ])->assertSessionHasErrors('visit');
        $this->assertSame('WAITING', $visit->fresh()->status->name);
        $this->patch(route('admin.payables.cancel', $fee), ['confirm_cancel' => 1, 'cancel_reason' => 'Test huy phi'])
            ->assertSessionHasErrors('cancel_reason');
        $this->actingAs($patient)->get(route('patient.payments.index'))->assertOk()->assertSee($fee->payable_code)->assertSee('300.000');
        $this->get(route('patient.queues.index'))->assertOk()->assertSee('Vui lòng thanh toán phí khám tại quầy');
        $this->actingAs($admin);
        $queueStatus = \App\Models\QueueStatus::where('name', 'WAITING')->firstOrFail();
        $queueStatus->update(['is_active' => false]);
        $this->post(route('admin.payables.payment-transactions.store', $fee), ['payment_method' => 'CASH', 'confirm_payment' => 1, 'collected_by_employee_id' => \App\Models\Employee::where('employee_code', 'NV000004')->value('id')])->assertSessionHasErrors('payment_method');
        $this->assertSame('UNPAID', $fee->fresh()->paymentStatus->name);
        $this->assertSame(0, $fee->transactions()->count());
        $this->assertSame(0, $visit->queues()->count());
        $queueStatus->update(['is_active' => true]);
        $this->actingAs($admin)->post(route('admin.payables.payment-transactions.store', $fee), ['payment_method' => 'CASH', 'confirm_payment' => 1, 'collected_by_employee_id' => \App\Models\Employee::where('employee_code', 'NV000004')->value('id')])->assertSessionHasNoErrors();
        $queue = $visit->queues()->sole();
        $this->post(route('admin.payables.payment-transactions.store', $fee), ['payment_method' => 'CASH', 'confirm_payment' => 1, 'collected_by_employee_id' => \App\Models\Employee::where('employee_code', 'NV000004')->value('id')]);
        $this->assertSame(1, $visit->queues()->count());
        $this->actingAs($patient)->get(route('patient.queues.index'))->assertOk()
            ->assertSee($appointment->appointment_code)->assertSee($visit->visit_code)->assertSeeText($queue->status->description);
        $this->actingAs($other)->get(route('patient.queues.index'))->assertOk()->assertDontSee($visit->visit_code);

        $this->actingAs($admin)->post(route('admin.queues.call', $queue))->assertSessionHasNoErrors();
        $this->actingAs($patient)->get(route('patient.notifications', ['danh-muc' => 'kham-benh']))
            ->assertOk()->assertSee('Đã đến lượt khám')->assertSee($room->name);
        $this->getJson(route('patient.notifications'))->assertOk()
            ->assertJsonPath('active_call.message', 'Vui lòng đến '.$room->name.' theo hướng dẫn.')
            ->assertJsonPath('active_call.url', route('patient.queues.index'))
            ->assertJsonStructure(['count', 'active_call']);
        $this->actingAs($other)->get(route('patient.notifications', ['danh-muc' => 'kham-benh']))
            ->assertOk()->assertDontSee('Đã đến lượt khám');
        $this->getJson(route('patient.notifications'))->assertOk()->assertJsonPath('active_call', null);
        $snapshot = $this->actingAs($patient)->getJson(route('patient.notifications'))->assertOk()->json();
        $key = $snapshot['active_call']['key'];
        $this->actingAs($other)->post(route('patient.notifications.open'), ['notification_key' => $key])->assertNotFound();
        $this->actingAs($patient)->post(route('patient.notifications.open'), ['notification_key' => $key])->assertRedirect(route('patient.queues.index'));
        $this->getJson(route('patient.notifications'))->assertOk()->assertJsonPath('active_call', null)->assertJsonPath('count', $snapshot['count'] - 1);
        $this->get(route('patient.notifications', ['danh-muc' => 'kham-benh']))->assertOk()->assertDontSee('Đã đến lượt khám');
        $this->actingAs($admin)->post(route('admin.queues.call', $queue))->assertSessionHasNoErrors();
        $this->actingAs($patient)->getJson(route('patient.notifications'))->assertOk()->assertJsonPath('active_call.message', 'Vui lòng đến '.$room->name.' theo hướng dẫn.');
        $this->actingAs($admin);
        $this->post(route('admin.queues.start-service', $queue))->assertSessionHasNoErrors();
        $this->actingAs($patient)->get(route('patient.queues.index'))->assertOk()
            ->assertSeeText($queue->fresh()->status->description);
        $this->getJson(route('patient.notifications'))->assertOk()->assertJsonPath('active_call', null);

        $service = MedicalService::where('service_code', 'DVXN001')->firstOrFail();
        $this->actingAs($admin)->post(route('admin.visits.service-orders.store', $visit), ['medical_service_ids' => [$service->id]])->assertSessionHasNoErrors();
        $payable = ServiceOrder::where('medical_visit_id', $visit->id)->sole()->items()->firstOrFail()->payable;
        $this->actingAs($patient)->get(route('patient.payments.index'))->assertOk()->assertSee($payable->payable_code)->assertSeeText('Chưa thanh toán');
        $this->get(route('patient.notifications', ['danh-muc' => 'thanh-toan']))->assertOk()->assertSee($payable->payable_code);
        $this->actingAs($other)->get(route('patient.notifications', ['danh-muc' => 'thanh-toan']))->assertOk()->assertDontSee($payable->payable_code);
        $this->actingAs($other)->get(route('patient.payments.index'))->assertOk()->assertDontSee($payable->payable_code);
        $this->actingAs($admin)->post(route('admin.payables.payment-transactions.store', $payable), ['payment_method' => 'CASH', 'confirm_payment' => 1, 'collected_by_employee_id' => \App\Models\Employee::where('employee_code', 'NV000004')->value('id')])->assertSessionHasNoErrors();
        $transaction = $payable->fresh()->transactions()->where('transaction_result', 'SUCCESS')->sole();
        $this->actingAs($patient)->get(route('patient.payments.index'))->assertOk()
            ->assertDontSee($payable->payable_code)->assertDontSee($transaction->transaction_code);
        $this->get(route('patient.results.visit', ['visit' => $visit->id, 'tab' => 'vien-phi']))->assertOk()
            ->assertSee($transaction->transaction_code);
        $this->get(route('patient.results.receipt', [$visit->id, $transaction->id]))->assertOk()
            ->assertViewIs('employee.payments.receipt')->assertSee($transaction->transaction_code);
        $this->get(route('patient.notifications', ['danh-muc' => 'thanh-toan']))->assertOk()->assertDontSee($payable->payable_code);
    }
}
