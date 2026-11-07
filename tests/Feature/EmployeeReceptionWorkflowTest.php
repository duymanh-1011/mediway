<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentStatus;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Employee;
use App\Models\ExaminationRoom;
use App\Models\Patient;
use App\Models\PaymentStatus;
use App\Models\Specialty;
use App\Models\User;
use App\Services\AppointmentReceptionService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

class EmployeeReceptionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 23)->setTime(7, 0));
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
    }

    private function booked(): Appointment
    {
        $schedule = DoctorSchedule::create([
            'doctor_id' => Doctor::where('doctor_code', 'BS000001')->value('id'),
            'specialty_id' => Specialty::where('specialty_code', 'CKTMH')->value('id'),
            'examination_room_id' => ExaminationRoom::where('room_code', 'PKTMH01')->value('id'),
            'work_date' => today()->toDateString(), 'start_time' => '09:00', 'end_time' => '11:00', 'status' => 'ACTIVE',
        ]);
        $slot = $schedule->appointmentTimeSlots()->create(['start_time' => '09:00', 'end_time' => '09:30', 'max_patients' => 10, 'status' => 'ACTIVE']);
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail())->post(route('admin.appointments.store'), [
            'patient_id' => Patient::where('patient_code', 'BN000005')->value('id'),
            'appointment_time_slot_id' => $slot->id, 'reason_for_visit' => 'Khám theo lịch',
        ])->assertSessionHasNoErrors();
        $this->flushSession();
        $this->travelTo(today()->setTime(9, 0));
        return Appointment::where('appointment_time_slot_id', $slot->id)->firstOrFail();
    }

    public function test_reception_creates_one_visit_and_unpaid_fee_then_payment_creates_queue(): void
    {
        $appointment = $this->booked();
        $employee = Employee::where('employee_code', 'NV000001')->firstOrFail();
        $this->actingAs($employee->user)->get(route('employee.appointments.show', $appointment))->assertOk()->assertSee('Xác nhận tiếp nhận');
        $this->post(route('employee.appointments.check-in', $appointment), [])->assertSessionHasErrors('confirm_reception');
        $this->post(route('employee.appointments.check-in', $appointment), ['confirm_reception' => 1])->assertSessionHasNoErrors();
        $visit = $appointment->fresh()->medicalVisit;
        $this->assertNotNull($visit);
        $this->assertSame('CHECKED_IN', $appointment->fresh()->appointmentStatus->name);
        $this->assertSame('WAITING', $visit->status->name);
        $this->assertSame(0, $visit->queues()->count());
        $fee = $visit->payables()->where('payment_type', 'EXAMINATION_FEE')->sole();
        $this->assertSame('UNPAID', $fee->paymentStatus->name);
        $this->assertDatabaseHas('audit_logs', ['user_id' => $employee->user_id, 'role_name_snapshot' => 'Employee', 'action' => 'CHECK_IN', 'entity_id' => $appointment->id]);
        $originalDate = $appointment->fresh()->appointment_date->toDateString();
        $originalSlot = $appointment->fresh()->appointment_time_slot_id;
        $this->post(route('employee.appointments.check-in', $appointment), [
            'confirm_reception' => 1, 'appointment_date' => '2030-01-01', 'appointment_time_slot_id' => 999999, 'id' => 1,
        ])->assertSessionHasErrors('appointment');
        $this->assertSame($originalDate, $appointment->fresh()->appointment_date->toDateString());
        $this->assertSame($originalSlot, $appointment->fresh()->appointment_time_slot_id);
        $this->assertSame(1, $appointment->medicalVisit()->count());
        $this->assertSame(1, $visit->payables()->where('payment_type', 'EXAMINATION_FEE')->count());
        $this->flushSession();
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail())->post(route('admin.payables.payment-transactions.store', $fee), [
            'payment_method' => 'CASH', 'confirm_payment' => 1, 'collected_by_employee_id' => Employee::where('employee_code', 'NV000004')->value('id'),
        ])->assertSessionHasNoErrors();
        $this->assertSame(1, $visit->queues()->count());
        $queue = $visit->queues()->sole();
        $this->assertSame('WAITING', $queue->status->name);
        $this->post(route('admin.queues.call', $queue))->assertSessionHasNoErrors()->assertSessionMissing('error');
        $this->assertSame('CALLED', $queue->fresh()->status->name);
        $this->flushSession();
        $this->actingAs($employee->user)->get(route('employee.appointments.show', $appointment))->assertOk()
            ->assertSee($visit->visit_code)->assertDontSee('Gọi bệnh nhân')->assertDontSee('/nhan-vien/hang-doi', false);
        $this->flushSession();
        $this->actingAs($appointment->patient->user)->get(route('patient.appointments.show', $appointment))->assertOk()->assertSee('Đã tiếp nhận');
    }

    public function test_cancelled_appointment_and_changed_employee_type_cannot_be_received(): void
    {
        $appointment = $this->booked();
        $employee = Employee::where('employee_code', 'NV000001')->firstOrFail();
        $appointment->update(['appointment_status_id' => AppointmentStatus::where('name', 'CANCELLED')->value('id')]);
        $this->actingAs($employee->user)->post(route('employee.appointments.check-in', $appointment), ['confirm_reception' => 1])->assertSessionHasErrors('appointment');
        $this->assertFalse($appointment->medicalVisit()->exists());
        $appointment->update(['appointment_status_id' => AppointmentStatus::where('name', 'BOOKED')->value('id')]);
        $employee->detail->update(['employee_type' => 'CASHIER']);
        $this->post(route('employee.appointments.check-in', $appointment), ['confirm_reception' => 1])->assertForbidden();
        $this->assertFalse($appointment->medicalVisit()->exists());
    }

    public function test_failure_creating_fee_rolls_back_visit_and_appointment(): void
    {
        $appointment = $this->booked();
        PaymentStatus::where('name', 'UNPAID')->update(['is_active' => false]);
        $this->actingAs(Employee::where('employee_code', 'NV000001')->firstOrFail()->user)
            ->post(route('employee.appointments.check-in', $appointment), ['confirm_reception' => 1])->assertSessionHasErrors('appointment');
        $this->assertFalse($appointment->medicalVisit()->exists());
        $this->assertSame('BOOKED', $appointment->fresh()->appointmentStatus->name);
    }

    public function test_service_rechecks_employee_type_after_it_was_changed(): void
    {
        $appointment = $this->booked();
        $employee = Employee::where('employee_code', 'NV000001')->firstOrFail();
        $staleUser = $employee->user;
        $employee->detail->update(['employee_type' => 'CASHIER']);
        try {
            app(AppointmentReceptionService::class)->checkIn($appointment, $staleUser, true);
            $this->fail('Tiếp nhận phải bị từ chối khi nhân viên đã đổi loại.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
        $this->assertFalse($appointment->medicalVisit()->exists());
    }
}
