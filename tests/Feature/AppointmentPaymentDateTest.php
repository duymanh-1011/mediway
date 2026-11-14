<?php

namespace Tests\Feature;

use App\Models\Payable;
use App\Models\User;
use App\Services\AppointmentPaymentDateGuard;
use Tests\Fixtures\Seeders\WorkflowFixtureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AppointmentPaymentDateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(WorkflowFixtureSeeder::class);
        $this->withoutVite();
        $this->withSession(['employee_workspace_mode' => 'cashier']);
    }

    private function fee(): Payable
    {
        $fee = Payable::where('payable_code', 'PT000012')->firstOrFail();
        $this->assertNotNull($fee->medicalVisit->appointment_id);
        DB::table('appointments')->where('id', $fee->medicalVisit->appointment_id)->update(['appointment_date' => '2026-11-05']);
        return $fee->fresh();
    }

    public function test_guard_checks_vietnamese_calendar_day_and_rescheduled_date(): void
    {
        $fee = $this->fee();
        $guard = app(AppointmentPaymentDateGuard::class);
        foreach (['2026-11-05 00:00:00', '2026-11-05 23:59:59'] as $time) {
            $this->travelTo(\Carbon\Carbon::parse($time, 'Asia/Ho_Chi_Minh'));
            $this->assertNull($guard->unavailableReason($fee->fresh()));
        }
        $this->travelTo(\Carbon\Carbon::parse('2026-11-04 23:59:59', 'Asia/Ho_Chi_Minh'));
        $this->assertStringContainsString('Chưa đến ngày', $guard->unavailableReason($fee->fresh()));
        $this->travelTo(\Carbon\Carbon::parse('2026-11-06 00:00:00', 'Asia/Ho_Chi_Minh'));
        $this->assertStringContainsString('Đã quá ngày', $guard->unavailableReason($fee->fresh()));
        DB::table('appointments')->where('id', $fee->medicalVisit->appointment_id)->update(['appointment_date' => '2026-11-06']);
        $this->assertNull($guard->unavailableReason($fee->fresh()));
        $this->travelTo(\Carbon\Carbon::parse('2026-11-04 17:00:00', 'UTC'));
        $this->assertStringContainsString('Chưa đến ngày', $guard->unavailableReason($fee->fresh()));
    }

    public function test_cashier_is_blocked_on_wrong_date_without_financial_side_effects(): void
    {
        $fee = $this->fee();
        $this->travelTo(\Carbon\Carbon::parse('2026-11-04 12:00:00', 'Asia/Ho_Chi_Minh'));
        $before = $fee->transactions()->count();
        $queueBefore = $fee->medicalVisit->queues()->count();
        $auditBefore = DB::table('audit_logs')->where('entity_type', 'payment_transactions')->count();
        $this->actingAs(User::where('business_code', 'NV000004')->firstOrFail());
        $filter = $this->post(route('employee.payments.filter'), ['q' => $fee->payable_code])->assertRedirect();
        $this->get($filter->headers->get('Location'))->assertOk()->assertSeeText('Chưa đến ngày hẹn khám');
        $this->get(route('employee.payments.show', $fee))->assertOk()->assertSeeText('Chưa đến ngày hẹn khám')
            ->assertDontSee(route('employee.payments.collect.create', $fee));
        $this->get(route('employee.payments.collect.create', $fee))
            ->assertRedirect(route('employee.payments.show', $fee))
            ->assertSessionHasErrors('payment_date');
        $details = $this->get(route('employee.payments.show', $fee));
        $details->assertOk()->assertSeeText('Chưa đến ngày hẹn khám');
        $this->assertSame(1, substr_count($details->getContent(), 'Chưa đến ngày hẹn khám'));
        $this->post(route('employee.payments.collect.store', $fee), ['payment_method' => 'CASH', 'confirm_payment' => 1, 'appointment_date' => '2026-11-04'])->assertSessionHasErrors('payment_date');
        $this->assertSame($before, $fee->transactions()->count());
        $this->assertSame($queueBefore, $fee->medicalVisit->queues()->count());
        $this->assertSame($auditBefore, DB::table('audit_logs')->where('entity_type', 'payment_transactions')->count());
        $this->assertNotSame('PAID', $fee->fresh()->paymentStatus->name);
    }

    public function test_cashier_can_pay_on_the_appointment_day(): void
    {
        $fee = $this->fee();
        $this->travelTo(\Carbon\Carbon::parse('2026-11-05 16:30:00', 'Asia/Ho_Chi_Minh'));
        $this->actingAs(User::where('business_code', 'NV000004')->firstOrFail())
            ->post(route('employee.payments.collect.store', $fee), ['payment_method' => 'CASH', 'confirm_payment' => 1])
            ->assertSessionHasNoErrors();
        $this->assertSame('PAID', $fee->fresh()->paymentStatus->name);
        $this->assertSame(1, $fee->transactions()->where('transaction_result', 'SUCCESS')->count());
    }

    public function test_cashier_can_collect_after_the_slot_ends_on_the_same_day(): void
    {
        $fee = $this->fee();
        $slot = $fee->medicalVisit->appointment->appointmentTimeSlot;
        DB::table('appointment_time_slots')->where('id', $slot->id)->update(['start_time' => '07:00:00', 'end_time' => '08:00:00']);
        DB::table('doctor_schedules')->where('id', $slot->doctor_schedule_id)->update(['work_date' => '2026-11-05']);
        $this->travelTo(\Carbon\Carbon::parse('2026-11-05 09:00:00', 'Asia/Ho_Chi_Minh'));
        $this->actingAs(User::where('business_code', 'NV000004')->firstOrFail())
            ->post(route('employee.payments.collect.store', $fee), ['payment_method' => 'CASH', 'confirm_payment' => 1])
            ->assertSessionHasNoErrors();
        $this->assertSame('PAID', $fee->fresh()->paymentStatus->name);
        $this->assertSame(1, $fee->transactions()->where('transaction_result', 'SUCCESS')->count());
    }

    public function test_admin_cannot_bypass_date_for_service_and_medicine_fees(): void
    {
        $fee = $this->fee();
        $this->travelTo(\Carbon\Carbon::parse('2026-11-06 12:00:00', 'Asia/Ho_Chi_Minh'));
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail());
        foreach (['EXAMINATION_FEE', 'SERVICE_FEE', 'MEDICINE_FEE'] as $type) {
            DB::table('payables')->where('id', $fee->id)->update(['payment_type' => $type]);
            $this->post(route('admin.payables.payment-transactions.store', $fee), [
                'payment_method' => 'CASH', 'confirm_payment' => 1,
                'collected_by_employee_id' => \App\Models\Employee::where('employee_code', 'NV000004')->value('id'),
            ])->assertSessionHasErrors('payment_date');
        }
        $this->assertFalse($fee->transactions()->where('transaction_result', 'SUCCESS')->exists());
    }

    public function test_admin_and_cashier_cannot_open_or_submit_payment_outside_appointment_day(): void
    {
        $fee = $this->fee();
        $before = DB::table('payables')->where('id', $fee->id)->first();
        $transactionCount = $fee->transactions()->count();
        $auditCount = DB::table('audit_logs')->where('entity_type', 'payment_transactions')->count();
        $queueCount = $fee->medicalVisit->queues()->count();
        foreach (['2026-11-04 12:00:00', '2026-11-06 12:00:00'] as $date) {
            $this->travelTo(\Carbon\Carbon::parse($date, 'Asia/Ho_Chi_Minh'));
            $this->flushSession();
            $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail());
            $this->get(route('admin.payables.payment-transactions.create', $fee))
                ->assertRedirect(route('admin.payables.show', $fee))->assertSessionHasErrors('payment_date')->assertSessionHas('error');
            $this->get(route('admin.payables.show', $fee))->assertOk()
                ->assertSeeText(str_starts_with($date, '2026-11-04') ? 'Chưa đến ngày hẹn khám' : 'Đã quá ngày hẹn khám');
            $this->post(route('admin.payables.payment-transactions.store', $fee), [
                'payment_method' => 'CASH', 'confirm_payment' => 1,
                'collected_by_employee_id' => \App\Models\Employee::where('employee_code', 'NV000004')->value('id'),
            ])->assertSessionHasErrors('payment_date');
            $this->flushSession();
            $this->actingAsEmployeeWorkspace(User::where('business_code', 'NV000004')->firstOrFail(), 'cashier');
            $this->get(route('employee.payments.collect.create', $fee))
                ->assertRedirect(route('employee.payments.show', $fee))->assertSessionHasErrors('payment_date');
            $this->post(route('employee.payments.collect.store', $fee), ['payment_method' => 'CASH', 'confirm_payment' => 1])
                ->assertSessionHasErrors('payment_date');
        }
        $this->assertEquals($before, DB::table('payables')->where('id', $fee->id)->first());
        $this->assertSame($transactionCount, $fee->transactions()->count());
        $this->assertSame($auditCount, DB::table('audit_logs')->where('entity_type', 'payment_transactions')->count());
        $this->assertSame($queueCount, $fee->medicalVisit->queues()->count());
    }
}
