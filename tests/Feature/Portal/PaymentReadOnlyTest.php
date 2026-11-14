<?php

namespace Tests\Feature\Portal;

use App\Models\Employee;
use App\Models\Payable;
use App\Models\PaymentStatus;
use App\Models\User;
use Tests\Fixtures\Seeders\WorkflowFixtureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PaymentReadOnlyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(WorkflowFixtureSeeder::class);
        $this->withoutVite();
    }

    private function patient(string $code = 'BN000001'): User
    {
        return User::where('business_code', $code)->firstOrFail();
    }

    private function payable(User $patient): Payable
    {
        return Payable::whereHas('medicalVisit', fn ($visit) => $visit->where('patient_id', $patient->patient->id))
            ->whereHas('transactions', fn ($transaction) => $transaction->where('transaction_result', 'SUCCESS'))->firstOrFail();
    }

    public function test_cashier_confirmation_removes_the_same_charge_and_notification_without_changing_its_price(): void
    {
        $payable = Payable::where('payment_type', 'EXAMINATION_FEE')
            ->whereHas('paymentStatus', fn ($status) => $status->where('name', 'UNPAID'))
            ->whereHas('medicalVisit.appointment')
            ->whereDoesntHave('transactions', fn ($transaction) => $transaction->where('transaction_result', 'SUCCESS'))
            ->firstOrFail();
        $patient = $payable->medicalVisit->patient->user;
        $this->travelTo($payable->medicalVisit->appointment->appointment_date->copy()->setTime(10, 0));
        $originalAmount = $payable->amount;
        $this->assertSame('UNPAID', $payable->paymentStatus->name);

        $this->actingAs($patient)->get(route('patient.payments.index'))->assertOk()
            ->assertSee($payable->payable_code)
            ->assertSee(number_format((float) $originalAmount, 0, ',', '.'));
        $before = $this->getJson(route('patient.payments.index'))->assertOk()->json('snapshot');
        $this->get(route('patient.notifications', ['danh-muc' => 'thanh-toan']))
            ->assertOk()->assertSee($payable->payable_code);

        $cashier = User::where('business_code', 'NV000004')->firstOrFail();
        $this->actingAsEmployeeWorkspace($cashier, 'cashier')->post(route('employee.payments.collect.store', $payable), [
            'payment_method' => 'CASH', 'confirm_payment' => '1',
        ])->assertRedirect(route('employee.payments.show', $payable))->assertSessionHasNoErrors();
        $this->assertSame('PAID', $payable->fresh()->paymentStatus->name);
        $this->assertSame($originalAmount, $payable->fresh()->amount);
        $transaction = $payable->transactions()->where('transaction_result', 'SUCCESS')->sole();
        $this->assertSame($originalAmount, $transaction->amount);

        $this->actingAs($patient)->get(route('patient.payments.index'))->assertOk()->assertDontSee($payable->payable_code);
        $after = $this->getJson(route('patient.payments.index'))->assertOk();
        $this->assertNotSame($before, $after->json('snapshot'));
        $this->assertStringContainsString('no-store', $after->headers->get('Cache-Control'));
        $this->get(route('patient.notifications', ['danh-muc' => 'thanh-toan']))
            ->assertOk()->assertDontSee($payable->payable_code);
    }

    public function test_payment_snapshot_is_scoped_to_the_patient_and_ignores_another_patients_charge(): void
    {
        $patient = $this->patient();
        $other = $this->payable($this->patient('BN000002'));
        $before = $this->actingAs($patient)->getJson(route('patient.payments.index'))->assertOk()->json('snapshot');
        $other->transactions()->delete();
        $other->update(['payment_status_id' => PaymentStatus::where('name', 'UNPAID')->value('id'), 'amount' => 456789]);
        $this->getJson(route('patient.payments.index'))->assertOk()->assertExactJson(['snapshot' => $before]);
    }

    public function test_payment_page_only_shows_own_outstanding_charges_and_hides_successful_payments(): void
    {
        $patient = $this->patient();
        $own = $this->payable($patient);
        $other = $this->payable($this->patient('BN000002'));
        $unpaid = PaymentStatus::where('name', 'UNPAID')->firstOrFail();
        $own->transactions()->delete();
        $other->transactions()->delete();
        $own->update(['payment_status_id' => $unpaid->id, 'amount' => 123456, 'payment_qr_data' => 'PRIVATE-QR-DATA']);
        $other->update(['payment_status_id' => $unpaid->id, 'amount' => 99999]);

        $this->actingAs($patient)->get(route('patient.payments.index'))->assertOk()
            ->assertSee($own->payable_code)->assertSee('123.456')->assertDontSee($other->payable_code)->assertDontSee('PRIVATE-QR-DATA');
        $this->assertSame('UNPAID', $own->fresh()->paymentStatus->name);
        $this->assertSame(0, $own->transactions()->count());

        $own->transactions()->create(['transaction_code' => 'GD-PORTAL-OUTSTANDING', 'amount' => $own->amount,
            'payment_method' => 'CASH', 'transaction_result' => 'SUCCESS', 'started_at' => now()]);
        $this->get(route('patient.payments.index'))->assertOk()->assertDontSee($own->payable_code);
    }

    public function test_unknown_or_cancelled_status_is_not_presented_as_an_outstanding_charge(): void
    {
        $patient = $this->patient();
        $own = $this->payable($patient);
        $own->transactions()->delete();
        $unknown = (new PaymentStatus)->forceFill(['name' => 'UNRECOGNIZED', 'description' => 'Chưa xác định', 'allows_service_execution' => false,
            'process_order' => 99, 'is_active' => true]);
        $unknown->save();
        foreach ([$unknown, PaymentStatus::where('name', 'CANCELLED')->firstOrFail()] as $status) {
            $own->update(['payment_status_id' => $status->id]);
            $this->actingAs($patient)->get(route('patient.payments.index'))->assertOk()->assertDontSee($own->payable_code);
        }
    }

    public function test_paid_transactions_and_receipts_are_scoped_to_the_visit_and_hide_internal_details(): void
    {
        $patient = $this->patient();
        $otherPatient = $this->patient('BN000002');
        $own = $this->payable($patient);
        $other = $this->payable($otherPatient);
        $success = $own->transactions()->where('transaction_result', 'SUCCESS')->firstOrFail();
        $otherSuccess = $other->transactions()->where('transaction_result', 'SUCCESS')->firstOrFail();
        $collector = Employee::where('employee_code', 'NV000004')->firstOrFail();
        $success->update(['payment_method' => 'CASH', 'collected_by_employee_id' => $collector->id, 'provider_transaction_code' => 'INTERNAL-PROVIDER', 'notes' => 'INTERNAL-NOTES']);
        $failed = $success->replicate();
        $failed->fill(['transaction_code' => 'GD-PORTAL-FAILED', 'transaction_result' => 'FAILED', 'provider_transaction_code' => null])->save();

        $this->actingAs($patient)->get(route('patient.payments.index'))->assertOk()
            ->assertDontSee($own->payable_code)->assertDontSee($success->transaction_code);
        $this->get(route('patient.results.visit', ['visit' => $own->medical_visit_id, 'tab' => 'vien-phi']))->assertOk()
            ->assertSee($success->transaction_code)->assertSee($collector->full_name)
            ->assertDontSee($otherSuccess->transaction_code)->assertDontSee('GD-PORTAL-FAILED')->assertDontSee('INTERNAL-PROVIDER');
        $receipt = route('patient.results.receipt', [$own->medical_visit_id, $success->id]);
        $this->get($receipt)->assertOk()->assertViewIs('employee.payments.receipt')
            ->assertSee($success->transaction_code)->assertSee($collector->full_name)
            ->assertSee('Quay lại giấy tờ')->assertSee('In phiếu thu A4')
            ->assertDontSee('INTERNAL-PROVIDER')->assertDontSee('INTERNAL-NOTES');
        $success->update(['payment_method' => 'QR']);
        $this->get($receipt)->assertNotFound();
        $success->update(['payment_method' => 'CASH']);
        $this->get(route('patient.results.receipt', [$own->medical_visit_id, $failed->id]))->assertNotFound();
        $this->get(route('patient.results.receipt', [$own->medical_visit_id, $otherSuccess->id]))->assertNotFound();
        $this->actingAs($otherPatient)->get($receipt)->assertNotFound();
    }

    public function test_payment_module_is_read_only_and_rejects_non_patient_accounts(): void
    {
        foreach (['store', 'update', 'cancel', 'update-status', 'transactions.store', 'checkout'] as $action) {
            $this->assertFalse(Route::has('patient.payments.'.$action));
        }
        $admin = User::where('business_code', 'QT000001')->firstOrFail();
        $this->actingAs($admin)->get(route('patient.payments.index'))->assertRedirect(route('patient.login'));
    }

    public function test_patient_service_receipt_uses_the_cashier_template_and_rejects_mismatched_amounts(): void
    {
        $transaction = \App\Models\PaymentTransaction::where('payment_method', 'CASH')
            ->where('transaction_result', 'SUCCESS')
            ->whereHas('payable', fn ($query) => $query->where('payment_type', 'SERVICE_FEE'))->firstOrFail();
        $payable = $transaction->payable;
        $url = route('patient.results.receipt', [$payable->medical_visit_id, $transaction->id]);
        $this->actingAs($payable->medicalVisit->patient->user)->get($url)->assertOk()
            ->assertViewIs('employee.payments.receipt')->assertViewHas('receiptType', 'service')
            ->assertSee($payable->serviceOrderItem->medicalService->name)
            ->assertSeeText('II. Chi phí khám, chữa bệnh')->assertDontSeeText('III. Thông tin giao dịch');
        $transaction->update(['amount' => (float) $transaction->amount + 1]);
        $this->get($url)->assertStatus(422);
    }

    public function test_patient_medicine_receipt_requires_completed_visit_and_uses_the_shared_template(): void
    {
        $prescription = \App\Models\Prescription::whereHas('items')->with('items')->firstOrFail();
        $payable = Payable::where('medical_visit_id', $prescription->medical_visit_id)->firstOrFail();
        $total = 0;
        foreach ($prescription->items as $item) {
            $line = (float) $item->quantity * 2000;
            $total += $line;
            $item->update(['unit_price' => 2000, 'line_total' => $line]);
        }
        $payable->update(['payment_type' => 'MEDICINE_FEE', 'prescription_id' => $prescription->id,
            'amount' => $total, 'payment_status_id' => PaymentStatus::where('name', 'PAID')->value('id')]);
        $transaction = $payable->transactions()->create(['transaction_code' => 'GD-PORTAL-MEDICINE-RECEIPT',
            'amount' => $total, 'payment_method' => 'CASH', 'transaction_result' => 'SUCCESS', 'started_at' => now(), 'paid_at' => now()]);
        $url = route('patient.results.receipt', [$payable->medical_visit_id, $transaction->id]);
        \Illuminate\Support\Facades\DB::table('prescriptions')->where('id', $prescription->id)->update(['patient_publication_status' => 'PRIVATE']);
        $visit = $payable->medicalVisit;
        $visit->update(['visit_status_id' => \App\Models\VisitStatus::where('name', 'IN_EXAMINATION')->value('id')]);
        $this->actingAs($payable->medicalVisit->patient->user)->get($url)->assertNotFound();
        $visit->update(['visit_status_id' => \App\Models\VisitStatus::where('name', 'COMPLETED')->value('id')]);
        $this->get($url)->assertOk()->assertViewIs('employee.payments.receipt')
            ->assertViewHas('receiptType', 'prescription')->assertSee($prescription->prescription_code)
            ->assertSeeText('II. Chi phí khám, chữa bệnh')->assertDontSeeText('III. Thông tin giao dịch')
            ->assertSeeText('XÁC NHẬN CỦA NGƯỜI BỆNH')->assertSeeText('NGƯỜI LẬP BẢNG KÊ / NGƯỜI THU TIỀN');
    }
}
