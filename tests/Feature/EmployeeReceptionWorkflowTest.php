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
use Tests\Fixtures\Seeders\WorkflowFixtureSeeder;
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
        $this->seed(WorkflowFixtureSeeder::class);
        $this->withoutVite();
        $this->withSession(['employee_workspace_mode' => 'reception']);
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
        $this->actingAsEmployeeWorkspace($employee->user, 'reception')->get(route('employee.appointments.show', $appointment))->assertOk()->assertSee('Xác nhận tiếp nhận');
        $this->post(route('employee.appointments.check-in', $appointment), [])->assertSessionHasErrors('confirm_reception');
        $this->post(route('employee.appointments.check-in', $appointment), ['confirm_reception' => 1])->assertSessionHasNoErrors();
        $visit = $appointment->fresh()->medicalVisit;
        $this->assertNotNull($visit);
        $this->assertSame('CHECKED_IN', $appointment->fresh()->appointmentStatus->name);
        $this->assertSame('WAITING', $visit->status->name);
        $this->assertSame(0, $visit->queues()->count());
        $fee = $visit->payables()->where('payment_type', 'EXAMINATION_FEE')->sole();
        $this->assertSame('UNPAID', $fee->paymentStatus->name);
        $this->assertGreaterThan(0, $visit->reception_queue_number);
        $slip = route('employee.reception.slip', $visit);
        $this->get(route('employee.appointments.show', $appointment))->assertOk()->assertSee($slip);
        $this->get($slip)->assertOk()->assertSee('PHIẾU KHÁM BỆNH')->assertSee('In phiếu khám')
            ->assertSee('Khám theo lịch')->assertSee($appointment->examinationRoom->name)
            ->assertSee(number_format((float) $fee->amount, 0, ',', '.').' đ')
            ->assertSee('class="screen-wrap print-document-wrap reception-slip"', false)
            ->assertSee('class="reception-slip-number__value">'.$visit->reception_queue_number.'<', false)
            ->assertDontSee('Tiếp nhận lúc')->assertDontSee('Thông tin bệnh nhân')->assertDontSee('Thông tin khám')
            ->assertSeeInOrder(['Số thứ tự khám', 'Hà Nội, ngày '.$visit->checked_in_at->format('d').' tháng '.$visit->checked_in_at->format('m').' năm '.$visit->checked_in_at->format('Y').' lúc 09:00', 'Người tiếp đón', $employee->full_name]);
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
        $this->get(route('admin.visits.reception-slip', $visit))->assertOk()->assertSee($employee->full_name);
        $this->assertSame(1, $visit->queues()->count());
        $queue = $visit->queues()->sole();
        $this->assertSame($visit->reception_queue_number, (int) $queue->queue_number);
        $this->assertSame('WAITING', $queue->status->name);
        $this->post(route('admin.queues.call', $queue))->assertSessionHasNoErrors()->assertSessionMissing('error');
        $this->assertSame('CALLED', $queue->fresh()->status->name);
        $this->flushSession();
        $this->actingAsEmployeeWorkspace($employee->user, 'reception')->get(route('employee.appointments.show', $appointment))->assertOk()
            ->assertSee($visit->visit_code)->assertDontSee('Gọi bệnh nhân')->assertDontSee('/nhan-vien/hang-doi', false);
        $this->flushSession();
        $this->actingAs($appointment->patient->user)->get(route('patient.appointments.show', $appointment))->assertOk()->assertSee('Đã tiếp nhận');
        $this->get($slip)->assertForbidden();
    }

    public function test_cancelled_appointment_is_rejected_but_employee_classification_does_not_revoke_workspace(): void
    {
        $appointment = $this->booked();
        $employee = Employee::where('employee_code', 'NV000001')->firstOrFail();
        $appointment->update(['appointment_status_id' => AppointmentStatus::where('name', 'CANCELLED')->value('id')]);
        $this->actingAsEmployeeWorkspace($employee->user, 'reception')->post(route('employee.appointments.check-in', $appointment), ['confirm_reception' => 1])->assertSessionHasErrors('appointment');
        $this->assertFalse($appointment->medicalVisit()->exists());
        $appointment->update(['appointment_status_id' => AppointmentStatus::where('name', 'BOOKED')->value('id')]);
        $employee->detail->update(['employee_type' => 'CASHIER']);
        $this->post(route('employee.appointments.check-in', $appointment), ['confirm_reception' => 1])->assertSessionHasNoErrors();
        $this->assertTrue($appointment->medicalVisit()->exists());
    }

    public function test_failure_creating_fee_rolls_back_visit_and_appointment(): void
    {
        $appointment = $this->booked();
        PaymentStatus::where('name', 'UNPAID')->update(['is_active' => false]);
        $this->actingAsEmployeeWorkspace(Employee::where('employee_code', 'NV000001')->firstOrFail()->user, 'reception')
            ->post(route('employee.appointments.check-in', $appointment), ['confirm_reception' => 1])->assertSessionHasErrors('appointment');
        $this->assertFalse($appointment->medicalVisit()->exists());
        $this->assertSame('BOOKED', $appointment->fresh()->appointmentStatus->name);
    }

    public function test_service_rechecks_employee_account_after_classification_changes(): void
    {
        $appointment = $this->booked();
        $employee = Employee::where('employee_code', 'NV000001')->firstOrFail();
        $staleUser = $employee->user;
        $employee->detail->update(['employee_type' => 'CASHIER']);
        try {
            $visit = app(AppointmentReceptionService::class)->checkIn($appointment, $staleUser, true);
            $this->assertNotNull($visit->id);
        } catch (HttpException $exception) {
            $this->fail('Đổi phân loại nhân viên không được thu hồi workspace đã chọn: '.$exception->getMessage());
        }
        $this->assertTrue($appointment->medicalVisit()->exists());
    }
}
