<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Employee;
use App\Models\ExaminationRoom;
use App\Models\MedicalVisit;
use App\Models\Patient;
use App\Models\PaymentStatus;
use App\Models\Specialty;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalkInReceptionTest extends TestCase
{
    use RefreshDatabase;

    private DoctorSchedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 23)->setTime(9, 0));
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $this->schedule = DoctorSchedule::create([
            'doctor_id' => Doctor::where('doctor_code', 'BS000001')->value('id'),
            'specialty_id' => Specialty::where('specialty_code', 'CKTMH')->value('id'),
            'examination_room_id' => ExaminationRoom::where('room_code', 'PKTMH01')->value('id'),
            'work_date' => today()->toDateString(), 'start_time' => '09:00', 'end_time' => '11:00', 'status' => 'ACTIVE',
        ]);
    }

    private function receptionist(): User
    {
        return Employee::where('employee_code', 'NV000001')->firstOrFail()->user;
    }

    public function test_reception_dashboard_and_appointment_list_have_distinct_purposes(): void
    {
        $this->actingAs($this->receptionist())->get(route('employee.reception.index'))->assertOk()
            ->assertSee('Tiếp đón bệnh nhân')->assertSee('Tiếp nhận khám trực tiếp');
        $this->get(route('employee.appointments.index'))->assertOk()->assertSee('Lịch hẹn tiếp đón')->assertDontSee('Khám trực tiếp tại quầy');
        $this->get(route('employee.walk-in.create'))->assertOk()->assertSee('Tìm hồ sơ bệnh nhân')->assertSee('Tạo hồ sơ bệnh nhân mới');
    }

    public function test_existing_patient_walk_in_requires_payment_then_receives_one_queue_number(): void
    {
        $patient = Patient::where('patient_code', 'BN000005')->firstOrFail();
        $identifier = strtolower($patient->patient_code);
        $this->actingAs($this->receptionist())->post(route('employee.walk-in.search'), ['identifier' => $identifier])->assertRedirect();
        $this->get(route('employee.walk-in.create'))->assertOk()
            ->assertSee('value="'.$identifier.'"', false)->assertSee('Tìm thấy 1 hồ sơ bệnh nhân')
            ->assertSee($patient->full_name)->assertSee('Chọn hồ sơ để tiếp nhận');
        $payload = ['patient_mode' => 'existing', 'patient_id' => $patient->id, 'schedule_id' => $this->schedule->id, 'confirm_reception' => 1, 'request_token' => (string) \Illuminate\Support\Str::uuid()];
        $this->post(route('employee.walk-in.store'), $payload)->assertSessionHasNoErrors();
        $visit = MedicalVisit::where('patient_id', $patient->id)->where('reception_source', 'WALK_IN')->sole();
        $this->assertNull($visit->appointment_id);
        $this->assertSame(0, $visit->queues()->count());
        $fee = $visit->payables()->where('payment_type', 'EXAMINATION_FEE')->sole();
        $this->assertSame('UNPAID', $fee->paymentStatus->name);
        $this->get(route('employee.walk-in.show', $visit))->assertOk()->assertSee('cần thanh toán phí khám');
        $this->post(route('employee.walk-in.search'), ['identifier' => $patient->patient_code]);
        $this->post(route('employee.walk-in.store'), $payload)->assertSessionHasNoErrors();
        $this->post(route('employee.walk-in.search'), ['identifier' => $patient->patient_code]);
        $this->post(route('employee.walk-in.store'), [...$payload, 'request_token' => (string) \Illuminate\Support\Str::uuid()])->assertSessionHasErrors('patient_id');
        $this->assertSame(1, MedicalVisit::where('patient_id', $patient->id)->where('reception_source', 'WALK_IN')->count());
        $this->flushSession();
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail())->post(route('admin.payables.payment-transactions.store', $fee), [
            'payment_method' => 'CASH', 'confirm_payment' => 1, 'collected_by_employee_id' => Employee::where('employee_code', 'NV000004')->value('id'),
        ])->assertSessionHasNoErrors()->assertSessionMissing('error');
        $this->assertSame('PAID', $fee->fresh()->paymentStatus->name);
        $this->assertSame(1, $visit->queues()->count());
        $this->assertSame('WAITING', $visit->queues()->sole()->status->name);
        $this->flushSession();
        $this->actingAs($this->receptionist())->get(route('employee.walk-in.show', $visit))->assertSee('Phí khám đã được thanh toán');
    }

    public function test_new_patient_record_is_created_without_creating_an_account(): void
    {
        $this->actingAs($this->receptionist())->post(route('employee.walk-in.store'), [
            'patient_mode' => 'new', 'full_name' => 'Người Mới Kiểm Thử', 'date_of_birth' => '2000-01-02', 'gender' => 'Nữ',
            'citizen_id_number' => '123456789012', 'confirm_search' => 1, 'schedule_id' => $this->schedule->id, 'confirm_reception' => 1, 'request_token' => (string) \Illuminate\Support\Str::uuid(),
        ])->assertSessionHasNoErrors();
        $patient = Patient::where('full_name', 'Người Mới Kiểm Thử')->sole();
        $this->assertNull($patient->user_id);
        $this->assertSame('123456789012', $patient->detail->citizen_id_number);
        $this->assertSame(1, $patient->medicalVisits()->where('reception_source', 'WALK_IN')->count());
    }

    public function test_repeated_new_patient_submission_does_not_duplicate_a_record(): void
    {
        $token = (string) \Illuminate\Support\Str::uuid();
        $payload = ['patient_mode' => 'new', 'full_name' => 'Bệnh Nhân Gửi Lại', 'date_of_birth' => '2000-01-02', 'gender' => 'Nam',
            'confirm_search' => 1, 'schedule_id' => $this->schedule->id, 'confirm_reception' => 1, 'request_token' => $token];
        $this->actingAs($this->receptionist())->post(route('employee.walk-in.store'), $payload)->assertSessionHasNoErrors();
        $this->post(route('employee.walk-in.store'), $payload)->assertSessionHasNoErrors();
        $this->assertSame(1, Patient::where('full_name', 'Bệnh Nhân Gửi Lại')->count());
        $patient = Patient::where('full_name', 'Bệnh Nhân Gửi Lại')->sole();
        $this->assertSame(1, $patient->medicalVisits()->count());
    }

    public function test_validation_and_invalid_schedule_do_not_create_partial_records(): void
    {
        $base = ['patient_mode' => 'new', 'full_name' => 'Kiểm Thử Không Lưu', 'date_of_birth' => '2000-01-02', 'gender' => 'Nam',
            'schedule_id' => $this->schedule->id, 'confirm_reception' => 1, 'request_token' => (string) \Illuminate\Support\Str::uuid()];
        $this->actingAs($this->receptionist())->post(route('employee.walk-in.store'), $base)->assertSessionHasErrors('confirm_search');
        $this->schedule->update(['status' => 'INACTIVE']);
        $this->post(route('employee.walk-in.store'), [...$base, 'confirm_search' => 1])->assertSessionHasErrors('schedule_id');
        $this->assertFalse(Patient::where('full_name', 'Kiểm Thử Không Lưu')->exists());
        $this->schedule->update(['status' => 'ACTIVE']);
        PaymentStatus::where('name', 'UNPAID')->update(['is_active' => false]);
        $this->post(route('employee.walk-in.store'), [...$base, 'confirm_search' => 1])->assertSessionHasErrors('appointment');
        $this->assertFalse(Patient::where('full_name', 'Kiểm Thử Không Lưu')->exists());
    }

    public function test_existing_identity_and_unsearched_patient_cannot_be_substituted(): void
    {
        $patient = Patient::where('patient_code', 'BN000005')->firstOrFail();
        $patient->detail()->update(['citizen_id_number' => '012345678901']);
        $this->actingAs($this->receptionist())->post(route('employee.walk-in.store'), [
            'patient_mode' => 'new', 'full_name' => 'Hồ Sơ Trùng', 'date_of_birth' => '2000-01-02', 'gender' => 'Nam',
            'citizen_id_number' => '012345678901', 'confirm_search' => 1, 'schedule_id' => $this->schedule->id, 'confirm_reception' => 1, 'request_token' => (string) \Illuminate\Support\Str::uuid(),
        ])->assertSessionHasErrors('citizen_id_number');
        $this->assertFalse(Patient::where('full_name', 'Hồ Sơ Trùng')->exists());
        $this->post(route('employee.walk-in.store'), [
            'patient_mode' => 'existing', 'patient_id' => $patient->id, 'schedule_id' => $this->schedule->id, 'confirm_reception' => 1, 'request_token' => (string) \Illuminate\Support\Str::uuid(),
        ])->assertSessionHasErrors('patient_id');
        $this->assertFalse(MedicalVisit::where('patient_id', $patient->id)->where('reception_source', 'WALK_IN')->exists());
    }

    public function test_search_accepts_login_phone_and_exact_identity_number(): void
    {
        $patient = Patient::where('patient_code', 'BN000005')->firstOrFail();
        $patient->detail()->update(['citizen_id_number' => '012345678901']);
        $this->actingAs($this->receptionist())->post(route('employee.walk-in.search'), ['identifier' => $patient->user->phone])->assertRedirect();
        $this->get(route('employee.walk-in.create'))->assertSee($patient->patient_code);
        $this->post(route('employee.walk-in.search'), ['identifier' => '012345678901'])->assertRedirect();
        $this->get(route('employee.walk-in.create'))->assertSee($patient->patient_code);
        $this->post(route('employee.walk-in.search'), ['identifier' => '01234567890'])->assertRedirect();
        $this->get(route('employee.walk-in.create'))->assertDontSee($patient->patient_code)->assertSee('Không tìm thấy hồ sơ');
        $this->get(route('employee.walk-in.create'))->assertSee('Không tìm thấy hồ sơ');
    }

    public function test_unfinished_walk_in_prevents_duplicate_appointment_reception_for_same_doctor(): void
    {
        $patient = Patient::where('patient_code', 'BN000005')->firstOrFail();
        $slot = $this->schedule->appointmentTimeSlots()->create(['start_time' => '09:00', 'end_time' => '09:30', 'max_patients' => 5, 'status' => 'ACTIVE']);
        $appointment = \App\Models\Appointment::create([
            'appointment_code' => 'LH-WALKIN-TEST', 'patient_id' => $patient->id, 'doctor_id' => $this->schedule->doctor_id,
            'specialty_id' => $this->schedule->specialty_id, 'examination_room_id' => $this->schedule->examination_room_id,
            'appointment_date' => today(), 'appointment_time_slot_id' => $slot->id,
            'appointment_status_id' => \App\Models\AppointmentStatus::where('name', 'BOOKED')->value('id'),
            'checkin_token' => (string) \Illuminate\Support\Str::uuid(), 'booked_at' => now(),
        ]);
        $this->actingAs($this->receptionist())->post(route('employee.walk-in.search'), ['identifier' => $patient->patient_code]);
        $this->post(route('employee.walk-in.store'), ['patient_mode' => 'existing', 'patient_id' => $patient->id,
            'schedule_id' => $this->schedule->id, 'confirm_reception' => 1, 'request_token' => (string) \Illuminate\Support\Str::uuid()])->assertSessionHasNoErrors();
        $this->post(route('employee.appointments.check-in', $appointment), ['confirm_reception' => 1, 'request_token' => (string) \Illuminate\Support\Str::uuid()])->assertSessionHasErrors('appointment');
        $this->assertFalse($appointment->medicalVisit()->exists());
    }

    public function test_wrong_roles_cannot_search_or_submit_walk_in(): void
    {
        foreach (['NV000002', 'NV000003', 'NV000004', 'BN000001', 'QT000001'] as $code) {
            $this->flushSession();
            $this->actingAs(User::where('business_code', $code)->firstOrFail())->get(route('employee.walk-in.create'))->assertForbidden();
            $this->post(route('employee.walk-in.search'), ['identifier' => 'BN000005'])->assertForbidden();
            $this->post(route('employee.walk-in.store'), [])->assertForbidden();
        }
    }
}
