<?php

namespace Tests\Feature\Portal;

use App\Models\Employee;
use App\Models\Payable;
use App\Models\PaymentStatus;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PaymentReadOnlyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
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
        $success->update(['collected_by_employee_id' => $collector->id, 'provider_transaction_code' => 'INTERNAL-PROVIDER', 'notes' => 'INTERNAL-NOTES']);
        $failed = $success->replicate();
        $failed->fill(['transaction_code' => 'GD-PORTAL-FAILED', 'transaction_result' => 'FAILED', 'provider_transaction_code' => null])->save();

        $this->actingAs($patient)->get(route('patient.payments.index'))->assertOk()
            ->assertDontSee($own->payable_code)->assertDontSee($success->transaction_code);
        $this->get(route('patient.results.visit', ['visit' => $own->medical_visit_id, 'tab' => 'vien-phi']))->assertOk()
            ->assertSee($success->transaction_code)->assertSee($collector->full_name)
            ->assertDontSee($otherSuccess->transaction_code)->assertDontSee('GD-PORTAL-FAILED')->assertDontSee('INTERNAL-PROVIDER');
        $receipt = route('patient.results.receipt', [$own->medical_visit_id, $success->id]);
        $this->get($receipt)->assertOk()->assertSee($own->payable_code)->assertSee($collector->full_name)
            ->assertDontSee('INTERNAL-PROVIDER')->assertDontSee('INTERNAL-NOTES');
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
}
