<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\ExaminationRoom;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class F08TeamIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_order_service_then_cashier_collects_and_opens_matching_receipt(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $visit = \App\Models\MedicalVisit::whereDoesntHave('serviceOrders')->firstOrFail();
        $visit->update(['appointment_id' => null, 'visit_status_id' => \App\Models\VisitStatus::where('name', 'IN_EXAMINATION')->value('id')]);
        $service = \App\Models\MedicalService::where('service_code', 'DVXN001')->firstOrFail();
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail())
            ->post(route('admin.visits.service-orders.store', $visit), ['medical_service_ids' => [$service->id]])->assertSessionHasNoErrors();
        $item = $visit->serviceOrders()->sole()->items()->sole();
        $fee = $item->payable;
        $this->assertNotNull($fee);
        $this->assertSame($visit->id, $fee->medical_visit_id);
        $this->assertSame($item->id, $fee->service_order_item_id);
        $this->assertSame('UNPAID', $fee->paymentStatus->name);
        $cashier = User::where('business_code', 'NV000004')->firstOrFail();
        $originalPrice = $item->price_at_order;
        // MySQL's CHECK prevents persisting a negative price for this HTTP test.
        $invalidPrices = DB::getDriverName() === 'mysql' ? [0] : [0, -1];
        foreach ($invalidPrices as $invalidPrice) {
            $item->update(['price_at_order' => $invalidPrice]);
            $fee->update(['amount' => $invalidPrice]);
            $this->post(route('admin.payables.payment-transactions.store', $fee), [
                'payment_method' => 'CASH', 'confirm_payment' => 1, 'collected_by_employee_id' => $cashier->employee->id,
            ])->assertSessionHasErrors('payment_method');
            $this->assertSame(0, $fee->transactions()->count());
            $this->flushSession();
        }
        $item->update(['price_at_order' => $originalPrice]);
        $item->refresh();
        $fee->update(['amount' => (float) $item->price_at_order + 1]);
        $this->post(route('admin.payables.payment-transactions.store', $fee), [
            'payment_method' => 'CASH', 'confirm_payment' => 1, 'collected_by_employee_id' => $cashier->employee->id,
        ])->assertSessionHasErrors('payment_method');
        $this->flushSession();
        $this->actingAs($cashier)->post(route('employee.payments.collect.store', $fee), [
            'payment_method' => 'CASH', 'confirm_payment' => 1,
        ])->assertSessionHasErrors('payment_method');
        $this->assertSame('UNPAID', $fee->fresh()->paymentStatus->name);
        $this->assertSame(0, $fee->transactions()->count());
        $fee->update(['amount' => $item->price_at_order]);
        $this->flushSession();
        $this->actingAs($cashier)->post(route('employee.payments.collect.store', $fee), ['payment_method' => 'CASH', 'confirm_payment' => 1])
            ->assertRedirect(route('employee.payments.show', $fee))->assertSessionHasNoErrors();
        $transaction = $fee->fresh()->transactions()->where('transaction_result', 'SUCCESS')->sole();
        $this->assertSame('PAID', $fee->fresh()->paymentStatus->name);
        $this->assertSame($cashier->employee->id, $transaction->collected_by_employee_id);
        $this->assertSame((string) $fee->amount, (string) $transaction->amount);
        $this->assertDatabaseHas('audit_logs', ['entity_type' => 'payment_transactions', 'entity_id' => $transaction->id, 'user_id' => $cashier->id]);
        $this->get(route('employee.payments.receipt', $transaction))->assertOk()->assertSee($service->name)->assertSee($transaction->transaction_code);
    }

    public function test_confirmed_medicine_purchase_then_cashier_collects_and_opens_receipt(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $medicine = \App\Models\Medicine::where('status', 'ACTIVE')->where('unit', 'viên')->firstOrFail();
        $medicine->update(['price_per_unit' => 2000]);
        $visit = \App\Models\MedicalVisit::whereDoesntHave('prescription')->firstOrFail();
        $visit->update(['appointment_id' => null, 'visit_status_id' => \App\Models\VisitStatus::where('name', 'IN_EXAMINATION')->value('id')]);
        $admin = User::where('business_code', 'QT000001')->firstOrFail();
        $this->actingAs($admin)->post(route('admin.visits.prescription.store', $visit), [
            'items' => [['medicine_id' => $medicine->id, 'quantity' => '10', 'usage_unit' => 'viên', 'dose_per_use' => '1 viên', 'times_per_day' => '2', 'duration_days' => '5', 'usage_method' => 'Uống', 'administration_time' => 'Sau ăn', 'usage_instructions' => 'Theo chỉ dẫn']],
            'confirm_create' => 1,
        ])->assertSessionHasNoErrors();
        $prescription = $visit->prescription()->sole();
        $this->assertNull($prescription->payable);
        $this->post(route('admin.prescriptions.payable.store', $prescription), [])->assertSessionHasErrors('confirm_purchase');
        $this->assertNull($prescription->fresh()->payable);
        $this->post(route('admin.prescriptions.payable.store', $prescription), ['confirm_purchase' => 1])->assertSessionHasNoErrors();
        $fee = $prescription->fresh()->payable;
        $this->assertSame('UNPAID', $fee->paymentStatus->name);
        $cashier = User::where('business_code', 'NV000004')->firstOrFail();
        $this->flushSession();
        $this->actingAs($cashier)->post(route('employee.payments.collect.store', $fee), ['payment_method' => 'CASH', 'confirm_payment' => 1])
            ->assertRedirect(route('employee.payments.show', $fee))->assertSessionHasNoErrors();
        $transaction = $fee->fresh()->transactions()->where('transaction_result', 'SUCCESS')->sole();
        $this->assertSame('PAID', $fee->fresh()->paymentStatus->name);
        $this->assertSame($cashier->employee->id, $transaction->collected_by_employee_id);
        $this->assertSame((string) $fee->amount, (string) $transaction->amount);
        $this->assertDatabaseHas('audit_logs', ['entity_type' => 'payment_transactions', 'entity_id' => $transaction->id, 'user_id' => $cashier->id]);
        $this->get(route('employee.payments.receipt', $transaction))->assertOk()->assertSee($medicine->name)->assertSee($prescription->prescription_code);
    }

    public function test_patient_booking_reception_cashier_queue_audit_and_exam_receipt(): void
    {
        $this->travelTo(Carbon::parse('2026-10-27 08:00:00', 'Asia/Ho_Chi_Minh'));
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $admin = User::where('business_code', 'QT000001')->firstOrFail();
        $patient = User::where('business_code', 'BN000001')->firstOrFail();
        $patient->patient->detail->update(['current_address' => 'Địa chỉ kiểm thử']);
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
        $this->actingAs($patient)->post(route('patient.appointments.store'), [
            'appointment_time_slot_id' => $slot->id, 'reason_for_visit' => 'Khám thử nghiệm', 'confirmed_information' => '1',
        ])->assertSessionHasNoErrors();
        $appointment = Appointment::where('patient_id', $patient->patient->id)->where('appointment_time_slot_id', $slot->id)->sole();
        $this->travelTo(Carbon::parse($date.' 09:00:00', 'Asia/Ho_Chi_Minh'));
        $receptionist = User::where('business_code', 'NV000001')->firstOrFail();
        $this->flushSession();
        $this->actingAs($receptionist)->post(route('employee.appointments.check-in', $appointment), ['confirm_reception' => 1])
            ->assertRedirect(route('employee.appointments.show', $appointment))->assertSessionHasNoErrors();
        $visit = $appointment->fresh()->medicalVisit;
        $this->assertNotNull($visit);
        $this->assertSame($patient->patient->id, $visit->patient_id);
        $fee = $visit->payables()->where('payment_type', 'EXAMINATION_FEE')->sole();
        $this->assertSame('UNPAID', $fee->paymentStatus->name);
        $this->assertSame(0, $visit->queues()->count());
        $cashier = User::where('business_code', 'NV000004')->firstOrFail();
        $this->flushSession();
        $this->actingAs($cashier)->post(route('employee.payments.collect.store', $fee), ['payment_method' => 'CASH', 'confirm_payment' => 1])
            ->assertRedirect(route('employee.payments.show', $fee))->assertSessionHasNoErrors();
        $transaction = $fee->fresh()->transactions()->where('transaction_result', 'SUCCESS')->sole();
        $this->assertSame('PAID', $fee->fresh()->paymentStatus->name);
        $this->assertSame($cashier->employee->id, $transaction->collected_by_employee_id);
        $this->assertSame((string) $fee->amount, (string) $transaction->amount);
        $queue = $visit->queues()->where('queue_type', 'INITIAL_EXAM')->sole();
        $this->assertSame('WAITING', $queue->status->name);
        $this->assertDatabaseHas('audit_logs', ['entity_type' => 'payment_transactions', 'entity_id' => $transaction->id, 'user_id' => $cashier->id]);
        $this->get(route('employee.payments.receipt', $transaction))->assertOk()->assertSee($patient->patient->full_name)->assertSee($transaction->transaction_code);
        $this->post(route('employee.payments.collect.store', $fee), ['payment_method' => 'CASH', 'confirm_payment' => 1])->assertSessionHasErrors();
        $this->assertSame(1, $visit->queues()->where('queue_type', 'INITIAL_EXAM')->count());
        $this->assertSame(1, $fee->transactions()->where('transaction_result', 'SUCCESS')->count());
    }
}
