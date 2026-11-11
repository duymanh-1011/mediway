<?php

namespace Tests\Feature\Portal;

use App\Models\DoctorSchedule;
use App\Models\User;
use App\Models\Employee;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UnlimitedAppointmentBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_multiple_patients_can_book_same_future_unlimited_slot(): void
    {
        $this->seed(DatabaseSeeder::class);
        DB::table('patient_details')->update(['current_address' => 'Địa chỉ kiểm thử']);
        $existing = DoctorSchedule::firstOrFail();
        $schedule = DoctorSchedule::create([
            'doctor_id' => $existing->doctor_id, 'specialty_id' => $existing->specialty_id,
            'examination_room_id' => $existing->examination_room_id, 'work_date' => today()->addDays(20),
            'start_time' => '09:00', 'end_time' => '10:00', 'status' => 'ACTIVE',
        ]);
        $slot = $schedule->appointmentTimeSlots()->create(['start_time' => '09:00', 'end_time' => '09:30', 'max_patients' => null, 'status' => 'ACTIVE']);
        $data = ['appointment_time_slot_id' => $slot->id, 'reason_for_visit' => 'Khám kiểm tra', 'confirmed_information' => '1'];
        foreach (['BN000001', 'BN000002'] as $code) {
            $this->flushSession();
            $this->actingAs(User::where('business_code', $code)->firstOrFail())
                ->post(route('patient.appointments.store'), $data)->assertSessionHasNoErrors();
        }
        $this->assertSame(2, $slot->appointments()->count());
        $this->post(route('patient.appointments.store'), $data)->assertSessionHasErrors('appointment_time_slot_id');
        $this->assertSame(2, $slot->appointments()->count());
    }

    public function test_same_slot_receives_distinct_queue_numbers_in_payment_order(): void
    {
        $this->travelTo(today()->setTime(8, 0));
        $this->seed(DatabaseSeeder::class);
        DB::table('patient_details')->update(['current_address' => 'Địa chỉ kiểm thử']);
        $existing = DoctorSchedule::firstOrFail();
        $schedule = DoctorSchedule::create([
            'doctor_id' => $existing->doctor_id, 'specialty_id' => $existing->specialty_id,
            'examination_room_id' => $existing->examination_room_id, 'work_date' => today()->addDay(),
            'start_time' => '09:00', 'end_time' => '10:00', 'status' => 'ACTIVE',
        ]);
        $slot = $schedule->appointmentTimeSlots()->create(['start_time' => '09:00', 'end_time' => '09:30', 'max_patients' => null, 'status' => 'ACTIVE']);
        $data = ['appointment_time_slot_id' => $slot->id, 'reason_for_visit' => 'Khám kiểm tra', 'confirmed_information' => '1'];
        $appointments = [];
        foreach (['BN000001', 'BN000002'] as $code) {
            $this->flushSession();
            $patient = User::where('business_code', $code)->firstOrFail();
            $this->actingAs($patient)->post(route('patient.appointments.store'), $data)->assertSessionHasNoErrors();
            $appointments[$code] = $slot->appointments()->where('patient_id', $patient->patient->id)->sole();
        }

        $this->travelTo($schedule->work_date->copy()->setTime(9, 0));
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail());
        $numbers = [];
        // Người đặt sau đến và hoàn thành thanh toán trước sẽ nhận số trước.
        foreach (['BN000002', 'BN000001'] as $code) {
            $appointment = $appointments[$code];
            $this->post(route('admin.appointments.check-in', $appointment))->assertSessionHasNoErrors();
            $visit = $appointment->fresh()->medicalVisit;
            $fee = $visit->payables()->where('payment_type', 'EXAMINATION_FEE')->sole();
            $this->post(route('admin.payables.payment-transactions.store', $fee), [
                'payment_method' => 'CASH', 'confirm_payment' => 1,
                'collected_by_employee_id' => Employee::where('employee_code', 'NV000004')->value('id'),
            ])->assertSessionHasNoErrors();
            $numbers[] = $visit->queues()->sole()->queue_number;
        }
        $this->assertLessThan($numbers[1], $numbers[0]);
    }
}
