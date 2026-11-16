<?php

namespace Tests\Feature\Portal;

use App\Models\Appointment;
use App\Models\AppointmentStatus;
use App\Models\AuditLog;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\ExaminationRoom;
use App\Models\Specialty;
use App\Models\User;
use App\Services\DoctorMonthlyScheduleService;
use Tests\Fixtures\Seeders\WorkflowFixtureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentChangeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(
            now()->setDate(2026, 10, 27)->setTime(8, 0)
        );

        $this->seed(WorkflowFixtureSeeder::class);        // M06_TEST_PROFILE_FIXTURE_20261030
        \Illuminate\Support\Facades\DB::table('patient_details')
            ->whereIn(
                'patient_id',
                \Illuminate\Support\Facades\DB::table('patients')
                    ->whereIn(
                        'patient_code',
                        ['BN000001', 'BN000002']
                    )
                    ->pluck('id')
            )
            ->update([
                'current_address' => '12 Pho Bach Mai, Ha Noi',
            ]);
        $this->withoutVite();
    }

    private function user(string $code = 'BN000001'): User
    {
        return User::where('business_code', $code)->firstOrFail();
    }

    private function slot(int $day = 1, int $limit = 1)
    {
        $doctor = Doctor::where(
            'doctor_code',
            'BS000001'
        )->firstOrFail();

        $specialty = Specialty::where(
            'specialty_code',
            'CKTMH'
        )->firstOrFail();

        $room = ExaminationRoom::where(
            'room_code',
            'PKTMH01'
        )->firstOrFail();

        $schedule = DoctorSchedule::create([
            'doctor_id' => $doctor->id,
            'specialty_id' => $specialty->id,
            'examination_room_id' => $room->id,
            'work_date' => today()->addDays($day)->format('Y-m-d'),
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'status' => 'ACTIVE',
        ]);

        return $schedule->appointmentTimeSlots()->create([
            'start_time' => '09:00:00',
            'end_time' => '09:30:00',
            'max_patients' => $limit,
            'status' => 'ACTIVE',
        ]);
    }

    private function booked(
        $slot,
        string $code = 'BN000001'
    ): Appointment {
        $user = $this->user($code);

        $this->actingAs($user)->post(
            route('patient.appointments.store'),
            [
                'appointment_time_slot_id' => $slot->id,
                'reason_for_visit' => 'Khám sức khỏe định kỳ.',
                'confirmed_information' => '1',
            ]
        )->assertSessionHasNoErrors();

        return Appointment::where(
            'patient_id',
            $user->patient->id
        )->where(
            'appointment_time_slot_id',
            $slot->id
        )->firstOrFail();
    }

    private function approve(): void
    {
        // Chỉ bật giả lập bên trong bài test.
        config()->set(
            'patient_appointments.self_service_approved',
            true
        );
    }

    public function test_preview_visible_but_actions_blocked_without_policy(): void
    {
        $appointment = $this->booked($this->slot());

        $this->get(
            route('patient.appointments.show', $appointment)
        )->assertOk()
            ->assertSee('Hủy lịch khám')
            ->assertDontSee('Đổi lịch khám');
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('patient.appointments.reschedule.form'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('patient.appointments.reschedule'));

        $this->get(
            route('patient.appointments.cancel.form', $appointment)
        )->assertOk()
            ->assertSee('Chưa thể xác nhận hủy lịch');

        $this->post(
            route('patient.appointments.cancel', $appointment),
            ['cancellation_reason' => 'Không đến khám được.']
        )->assertSessionHasErrors('appointment');

        $this->assertSame(
            'BOOKED',
            $appointment->fresh()->appointmentStatus->name
        );
    }

    public function test_approved_patient_can_cancel(): void
    {
        $appointment = $this->booked($this->slot());

        $this->approve();

        $this->post(
            route('patient.appointments.cancel', $appointment),
            ['cancellation_reason' => 'Bận công việc đột xuất.']
        )->assertRedirect(
            route('patient.appointments.show', $appointment)
        );

        $appointment->refresh();

        $this->assertSame(
            'CANCELLED',
            $appointment->appointmentStatus->name
        );

        $this->assertNotNull($appointment->cancelled_at);

        $this->assertTrue(
            AuditLog::where('entity_id', $appointment->id)
                ->where('action', 'CANCEL')
                ->exists()
        );
    }

    public function test_upcoming_booking_has_a_direct_cancel_action(): void
    {
        $appointment = $this->booked($this->slot());
        $this->approve();

        $this->get(route('patient.appointments'))
            ->assertOk()
            ->assertSee(route('patient.appointments.cancel.form', $appointment))
            ->assertSee('Hủy lịch khám')
            ->assertDontSee('Đổi lịch');
    }

    public function test_other_patient_cannot_cancel_appointment(): void
    {
        $appointment = $this->booked($this->slot());

        $this->approve();
        $this->actingAs($this->user('BN000002'));

        $this->get(
            route('patient.appointments.cancel.form', $appointment)
        )->assertNotFound();

        $this->post(
            route('patient.appointments.cancel', $appointment),
            ['cancellation_reason' => 'Muốn hủy lịch của người khác.']
        )->assertNotFound();
    }


    public function test_checked_in_appointment_cannot_cancel(): void
    {
        $appointment = $this->booked($this->slot());
        $originalDate = $appointment->appointment_date->format('Y-m-d');
        $originalSlot = $appointment->appointment_time_slot_id;

        $status = AppointmentStatus::where(
            'name',
            'CHECKED_IN'
        )->firstOrFail();

        $appointment->update([
            'appointment_status_id' => $status->id,
        ]);

        $this->approve();

        $this->post(
            route('patient.appointments.cancel', $appointment),
            ['cancellation_reason' => 'Muốn hủy sau tiếp nhận.']
        )->assertSessionHasErrors('appointment');

        $this->assertSame($originalDate, $appointment->fresh()->appointment_date->format('Y-m-d'));
        $this->assertSame($originalSlot, $appointment->fresh()->appointment_time_slot_id);

        $this->assertSame(
            'CHECKED_IN',
            $appointment->fresh()->appointmentStatus->name
        );
    }


    public function test_cutoff_blocks_late_cancellation(): void
    {
        $appointment = $this->booked($this->slot(1));

        $this->approve();
        $this->assertSame(120, (int) config('patient_appointments.cancel_cutoff_minutes'));

        $start = \Illuminate\Support\Carbon::parse(
            $appointment->appointment_date->format('Y-m-d')
            . ' ' . $appointment->appointmentTimeSlot->start_time,
            config('app.timezone')
        );
        $this->travelTo($start->copy()->subMinutes(119));

        $this->post(
            route('patient.appointments.cancel', $appointment),
            ['cancellation_reason' => 'Muốn hủy quá hạn.']
        )->assertSessionHasErrors('appointment');

        $this->assertSame(
            'BOOKED',
            $appointment->fresh()->appointmentStatus->name
        );
    }

    public function test_cancellation_is_allowed_just_over_two_hours_before_start(): void
    {
        $appointment = $this->booked($this->slot(1));
        $this->approve();

        $start = \Illuminate\Support\Carbon::parse(
            $appointment->appointment_date->format('Y-m-d')
            . ' ' . $appointment->appointmentTimeSlot->start_time,
            config('app.timezone')
        );
        $this->travelTo($start->copy()->subMinutes(121));

        $this->get(
            route('patient.appointments.cancel.form', $appointment)
        )->assertOk()
            ->assertSee('Xác nhận hủy lịch');
    }

    public function test_cancellation_is_allowed_at_the_two_hour_cutoff(): void
    {
        $appointment = $this->booked($this->slot(1));
        $this->approve();

        $start = \Illuminate\Support\Carbon::parse(
            $appointment->appointment_date->format('Y-m-d')
            . ' ' . $appointment->appointmentTimeSlot->start_time,
            config('app.timezone')
        );
        $this->travelTo($start->copy()->subMinutes(120));

        $this->get(
            route('patient.appointments.cancel.form', $appointment)
        )->assertOk()
            ->assertSee('Xác nhận hủy lịch');
    }

    public function test_generated_slot_flows_through_patient_booking_reception_cashier_and_queue(): void
    {
        $doctor = Doctor::where('doctor_code', 'BS000001')->firstOrFail();
        $schedule = app(DoctorMonthlyScheduleService::class)->updateShift(
            $this->user('QT000001'), $doctor->id, today()->addDay()->toDateString(), 'MORNING', 'WORKING', null,
            $doctor->specialty_id, ExaminationRoom::where('room_code', 'PKTMH01')->value('id'),
        );
        $slot = $schedule->appointmentTimeSlots()->where('start_time', '09:00:00')->sole();
        $this->assertNull($slot->max_patients);
        $appointment = $this->booked($slot);
        $patientId = $appointment->patient_id;
        $this->travelTo($slot->doctorSchedule->work_date->copy()->setTime(9, 0));
        $receptionist = User::where('business_code', 'NV000001')->firstOrFail();
        $this->flushSession()->actingAsEmployeeWorkspace($receptionist, 'reception')
            ->post(route('employee.appointments.check-in', $appointment), ['confirm_reception' => 1])
            ->assertSessionHasNoErrors();
        $visit = $appointment->fresh()->medicalVisit;
        $this->assertNotNull($visit);
        $this->assertSame($patientId, $visit->patient_id);
        $this->assertSame($appointment->id, $visit->appointment_id);
        $this->assertSame($appointment->doctor_id, $visit->doctor_id);
        $fee = $visit->payables()->where('payment_type', 'EXAMINATION_FEE')->sole();
        $this->assertSame('UNPAID', $fee->paymentStatus->name);
        $cashier = User::where('business_code', 'NV000004')->firstOrFail();
        $this->flushSession()->actingAsEmployeeWorkspace($cashier, 'cashier')
            ->post(route('employee.payments.collect.store', $fee), ['payment_method' => 'CASH', 'confirm_payment' => 1])
            ->assertSessionHasNoErrors();
        $this->assertSame('PAID', $fee->fresh()->paymentStatus->name);
        $transaction = $fee->transactions()->where('transaction_result', 'SUCCESS')->sole();
        $this->assertSame($cashier->employee->id, $transaction->collected_by_employee_id);
        $this->assertDatabaseHas('audit_logs', ['entity_type' => 'payment_transactions', 'entity_id' => $transaction->id, 'user_id' => $cashier->id]);
        $this->assertSame('WAITING', $visit->queues()->where('queue_type', 'INITIAL_EXAM')->sole()->status->name);
    }
}
