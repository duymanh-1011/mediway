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
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientTeamIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_portal_booking_connects_reception_queue_and_cashier_to_patient_views(): void
    {
        $this->travelTo(Carbon::parse('2026-10-27 08:00:00'));
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $admin = User::where('business_code', 'QT000001')->firstOrFail();
        $patient = User::where('business_code', 'BN000001')->firstOrFail();
        $other = User::where('business_code', 'BN000002')->firstOrFail();
        $patient->patient->detail->update(['current_address' => '12 Pho Bach Mai, Ha Noi']);
        $doctor = Doctor::where('doctor_code', 'BS000001')->firstOrFail();
        $room = ExaminationRoom::where('room_code', 'PKTMH01')->firstOrFail();
        $date = today()->addDay()->toDateString();

        $this->actingAs($admin)->post(route('admin.doctor-schedules.store'), [
            'doctor_id' => $doctor->id, 'specialty_id' => $doctor->specialty_id,
            'examination_room_id' => $room->id, 'work_date' => $date,
            'start_time' => '09:00', 'end_time' => '11:00', 'status' => 'ACTIVE',
        ])->assertSessionHasNoErrors();
        $schedule = DoctorSchedule::where('doctor_id', $doctor->id)->whereDate('work_date', $date)->firstOrFail();
        $this->post(route('admin.doctor-schedules.time-slots.store', $schedule), [
            'start_time' => '09:00', 'end_time' => '09:30', 'max_patients' => 2, 'status' => 'ACTIVE',
        ])->assertSessionHasNoErrors();
        $slot = $schedule->appointmentTimeSlots()->sole();

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
        $queue = $visit->queues()->sole();
        $this->actingAs($patient)->get(route('patient.queues.index'))->assertOk()
            ->assertSee($appointment->appointment_code)->assertSee($visit->visit_code)->assertSeeText($queue->status->description);
        $this->actingAs($other)->get(route('patient.queues.index'))->assertOk()->assertDontSee($visit->visit_code);

        $this->actingAs($admin)->post(route('admin.queues.call', $queue))->assertSessionHasNoErrors();
        $this->post(route('admin.queues.start-service', $queue))->assertSessionHasNoErrors();
        $this->actingAs($patient)->get(route('patient.queues.index'))->assertOk()
            ->assertSeeText($queue->fresh()->status->description);

        $service = MedicalService::where('service_code', 'DVXN001')->firstOrFail();
        $this->actingAs($admin)->post(route('admin.visits.service-orders.store', $visit), ['medical_service_ids' => [$service->id]])->assertSessionHasNoErrors();
        $payable = ServiceOrder::where('medical_visit_id', $visit->id)->sole()->items()->firstOrFail()->payable;
        $this->actingAs($patient)->get(route('patient.payments.index'))->assertOk()->assertSee($payable->payable_code)->assertSeeText('Chưa thanh toán');
        $this->actingAs($other)->get(route('patient.payments.index'))->assertOk()->assertDontSee($payable->payable_code);
        $this->actingAs($admin)->post(route('admin.payables.payment-transactions.store', $payable), ['payment_method' => 'CASH', 'confirm_payment' => 1])->assertSessionHasNoErrors();
        $transaction = $payable->fresh()->transactions()->where('transaction_result', 'SUCCESS')->sole();
        $this->actingAs($patient)->get(route('patient.payments.index'))->assertOk()
            ->assertSee($payable->payable_code)->assertSee($transaction->transaction_code)->assertSeeText('Đã thanh toán');
    }
}
