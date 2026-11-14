<?php

namespace Tests\Feature\Portal;

use App\Models\Payable;
use App\Models\PaymentStatus;
use App\Models\Prescription;
use App\Models\User;
use App\Models\VisitStatus;
use Tests\Fixtures\Seeders\WorkflowFixtureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientPrescriptionPurchaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(WorkflowFixtureSeeder::class);
        $this->withoutVite();
    }

    private function prescription(): Prescription
    {
        $prescription = Prescription::where('prescription_code', 'DT000001')->firstOrFail();
        $prescription->medicalVisit->update(['appointment_id' => null, 'visit_status_id' => VisitStatus::where('name', 'COMPLETED')->value('id')]);
        foreach ($prescription->items as $item) $item->update(['unit_price' => 1000, 'line_total' => 2000, 'quantity' => 2]);
        return $prescription->fresh(['medicalVisit.patient.user', 'items']);
    }

    public function test_patient_confirms_once_cashier_collects_and_receipt_is_private(): void
    {
        $prescription = $this->prescription();
        $patient = $prescription->medicalVisit->patient->user;
        $other = User::where('business_code', 'BN000002')->firstOrFail();
        $show = route('patient.prescriptions.purchase.show', $prescription);
        $store = route('patient.prescriptions.purchase.store', $prescription);
        $this->actingAs($patient)->get($show)->assertOk()->assertSee('Bạn có muốn mua thuốc theo đơn tại bệnh viện không?')
            ->assertSee('Không mua tại bệnh viện')->assertSee($prescription->prescription_code);
        $this->get(route('patient.prescriptions.show', $prescription))->assertSee('Lựa chọn mua thuốc');
        $this->assertSame(0, Payable::where('prescription_id', $prescription->id)->count());
        $this->post($store, [])->assertSessionHasErrors('confirm_purchase');
        $this->post($store, ['confirm_purchase' => 1, 'amount' => 1])->assertSessionHasNoErrors()->assertRedirect($show);
        $payable = Payable::where('prescription_id', $prescription->id)->sole();
        $this->assertSame($prescription->pricedTotal(), $payable->amount);
        $this->assertSame('UNPAID', $payable->paymentStatus->name);
        $this->post($store, ['confirm_purchase' => 1])->assertSessionHasNoErrors();
        $this->assertSame(1, Payable::where('prescription_id', $prescription->id)->count());
        $this->get($show)->assertSee($payable->payable_code)->assertDontSee('Xác nhận mua thuốc');
        $this->flushSession()->actingAs(User::where('business_code', 'QT000001')->firstOrFail())
            ->post(route('admin.prescriptions.payable.store', $prescription), ['confirm_purchase' => 1])->assertSessionHasErrors('confirm_purchase');
        $this->assertSame(1, Payable::where('prescription_id', $prescription->id)->count());
        $this->flushSession()->actingAs($other)->get($show)->assertNotFound();
        $this->post($store, ['confirm_purchase' => 1])->assertNotFound();

        $this->flushSession()->actingAsEmployeeWorkspace(User::where('business_code', 'NV000004')->firstOrFail(), 'cashier');
        $this->get(route('employee.payments.show', $payable))->assertOk()->assertSee($payable->payable_code);
        $this->post(route('employee.payments.collect.store', $payable), ['payment_method' => 'CASH', 'confirm_payment' => 1])->assertSessionHasNoErrors();
        $transaction = $payable->transactions()->where('transaction_result', 'SUCCESS')->firstOrFail();
        $this->get(route('employee.payments.receipt', $transaction))->assertOk();
        $this->flushSession()->actingAs($patient)->get($show)->assertSee('Xem phiếu thu');
        $receipt = route('patient.results.receipt', [$prescription->medical_visit_id, $transaction->id]);
        $this->get($receipt)->assertOk();
        $this->flushSession()->actingAs($other)->get($receipt)->assertNotFound();
    }

    public function test_status_price_and_admin_existing_charge_are_enforced(): void
    {
        $prescription = $this->prescription();
        $patient = $prescription->medicalVisit->patient->user;
        $visit = $prescription->medicalVisit;
        $store = route('patient.prescriptions.purchase.store', $prescription);
        $this->actingAs($patient);
        $visit->update(['visit_status_id' => VisitStatus::where('name', 'IN_EXAMINATION')->value('id')]);
        $this->get(route('patient.notifications', ['danh-muc' => 'ket-qua']))->assertDontSee('Lựa chọn mua thuốc tại bệnh viện');
        $this->get(route('patient.prescriptions.show', $prescription))->assertDontSee('Lựa chọn mua thuốc');
        $this->post($store, ['confirm_purchase' => 1])->assertSessionHasErrors('confirm_purchase');
        $visit->update(['visit_status_id' => VisitStatus::where('name', 'COMPLETED')->value('id')]);
        $prescription->update(['status' => 'CANCELLED']);
        $this->post($store, ['confirm_purchase' => 1])->assertSessionHasErrors('confirm_purchase');
        $prescription->update(['status' => 'ACTIVE']);
        $prescription->items()->firstOrFail()->update(['unit_price' => null, 'line_total' => null]);
        $this->post($store, ['confirm_purchase' => 1])->assertSessionHasErrors('confirm_purchase');
        $this->assertSame(0, Payable::where('prescription_id', $prescription->id)->count());
        $prescription->items()->firstOrFail()->update(['unit_price' => 1000, 'line_total' => 2000]);
        $this->flushSession()->actingAs(User::where('business_code', 'QT000001')->firstOrFail())
            ->post(route('admin.prescriptions.payable.store', $prescription), ['confirm_purchase' => 1])->assertSessionHasNoErrors();
        $this->flushSession()->actingAs($patient)->post($store, ['confirm_purchase' => 1])->assertSessionHasNoErrors();
        $this->assertSame(1, Payable::where('prescription_id', $prescription->id)->count());
        $charge = Payable::where('prescription_id', $prescription->id)->sole();
        $pending = PaymentStatus::where('name', 'PENDING')->first();
        if ($pending) {
            $charge->update(['payment_status_id' => $pending->id]);
            $this->get(route('patient.prescriptions.purchase.show', $prescription))->assertSee($pending->description)->assertDontSee('Xác nhận mua thuốc');
        }
        $charge->update(['payment_status_id' => PaymentStatus::where('name', 'CANCELLED')->value('id')]);
        $this->post($store, ['confirm_purchase' => 1])->assertSessionHasNoErrors();
        $this->assertSame(2, Payable::where('prescription_id', $prescription->id)->count());
    }

    public function test_purchase_reminder_is_for_completed_visit_and_is_not_duplicated_after_read(): void
    {
        $prescription = $this->prescription();
        $patient = $prescription->medicalVisit->patient->user;
        $this->actingAs($patient)->get(route('patient.notifications', ['danh-muc' => 'ket-qua']))->assertOk()->assertSee('Lựa chọn mua thuốc tại bệnh viện');
        $alert = collect($this->getJson(route('patient.notifications'))->json('unread'))->firstWhere('title', 'Lựa chọn mua thuốc tại bệnh viện');
        $this->assertNotNull($alert);
        $this->post(route('patient.notifications.open'), ['notification_key' => $alert['key']])->assertRedirect(route('patient.prescriptions.purchase.show', $prescription));
        $this->getJson(route('patient.notifications'))->assertJsonMissing(['key' => $alert['key']]);
    }
}
