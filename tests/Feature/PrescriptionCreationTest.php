<?php

namespace Tests\Feature;

use App\Models\MedicalVisit;
use App\Models\Medicine;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\User;
use App\Models\VisitStatus;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrescriptionCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Medicine::where('status', 'ACTIVE')->where('unit', 'viên')->update(['price_per_unit' => 2000, 'price_per_blister' => 18000, 'price_per_box' => 150000]);
        $this->withoutVite();
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail());
    }

    private function visit(string $status = 'IN_EXAMINATION'): MedicalVisit
    {
        $visit = MedicalVisit::whereDoesntHave('prescription')->firstOrFail();
        $visit->update(['appointment_id' => null, 'visit_status_id' => VisitStatus::where('name', $status)->value('id')]);
        return $visit->fresh();
    }

    private function item(Medicine $medicine): array
    {
        return ['medicine_id' => $medicine->id, 'quantity' => '10', 'usage_unit' => 'viên',
            'dose_per_use' => '1 viên', 'times_per_day' => '2', 'duration_days' => '5',
            'usage_method' => 'Uống', 'administration_time' => 'Sau ăn', 'usage_instructions' => 'Theo chỉ dẫn'];
    }

    public function test_admin_creates_one_prescription_with_multiple_medicines_for_the_visit(): void
    {
        $visit = $this->visit();
        $medicines = Medicine::where('status', 'ACTIVE')->limit(2)->get();
        $this->assertCount(2, $medicines);
        $this->get(route('admin.visits.show', $visit))->assertOk()->assertSee(route('admin.visits.prescription.create', $visit));
        $this->get(route('admin.visits.prescription.create', $visit))->assertOk()->assertSee($visit->patient->full_name)->assertSee($medicines[0]->name);
        $response = $this->post(route('admin.visits.prescription.store', $visit), [
            'items' => [$this->item($medicines[0]), $this->item($medicines[1])],
            'general_instructions' => 'Lời dặn kiểm thử', 'doctor_id' => 999999, 'status' => 'CANCELLED', 'confirm_create' => 1,
        ])->assertSessionHasNoErrors();
        $prescription = Prescription::where('medical_visit_id', $visit->id)->sole();
        $response->assertRedirect(route('admin.prescriptions.show', $prescription));
        $this->assertSame($visit->doctor_id, $prescription->doctor_id);
        $this->assertSame('ACTIVE', $prescription->status);
        $this->assertCount(2, $prescription->items);
        $this->assertDatabaseHas('audit_logs', ['entity_type' => 'prescriptions', 'entity_id' => $prescription->id, 'action' => 'CREATE']);
        $this->get(route('admin.visits.show', $visit))->assertOk()->assertSee($prescription->prescription_code)
            ->assertDontSee('href="'.route('admin.visits.prescription.create', $visit).'"', false);
        $this->assertNotNull($visit->patient->user);
        $this->actingAs($visit->patient->user)->get(route('patient.prescriptions'))->assertOk()
            ->assertSee($prescription->prescription_code)->assertSee($medicines[0]->name);
    }

    public function test_invalid_medicines_and_dosage_do_not_create_partial_prescriptions(): void
    {
        $visit = $this->visit();
        $medicine = Medicine::where('status', 'ACTIVE')->firstOrFail();
        $item = $this->item($medicine);
        $this->post(route('admin.visits.prescription.store', $visit), ['items' => [$item, $item], 'confirm_create' => 1])
            ->assertSessionHasErrors('items.0.medicine_id');
        $this->post(route('admin.visits.prescription.store', $visit), ['items' => [[...$item, 'quantity' => '0', 'duration_days' => '0']], 'confirm_create' => 1])
            ->assertSessionHasErrors(['items.0.quantity', 'items.0.duration_days']);
        $medicine->update(['status' => 'INACTIVE']);
        $this->post(route('admin.visits.prescription.store', $visit), ['items' => [$item], 'confirm_create' => 1])
            ->assertSessionHasErrors('items.0.medicine_id');
        $this->assertFalse($visit->prescription()->exists());
    }

    public function test_creation_requires_active_consultation_and_cannot_duplicate_a_prescription(): void
    {
        $visit = $this->visit('WAITING_FOR_SERVICE');
        $item = $this->item(Medicine::where('status', 'ACTIVE')->firstOrFail());
        $this->get(route('admin.visits.show', $visit))->assertOk()->assertDontSee('href="'.route('admin.visits.prescription.create', $visit).'"', false);
        $this->post(route('admin.visits.prescription.store', $visit), ['items' => [$item], 'confirm_create' => 1])->assertSessionHasErrors('visit');
        $this->assertFalse($visit->prescription()->exists());
        $visit->update(['visit_status_id' => VisitStatus::where('name', 'IN_EXAMINATION')->value('id')]);
        $this->post(route('admin.visits.prescription.store', $visit), ['items' => [$item], 'confirm_create' => 1])->assertSessionHasNoErrors();
        $this->post(route('admin.visits.prescription.store', $visit), ['items' => [$item], 'confirm_create' => 1])->assertSessionHasErrors('prescription');
        $this->assertSame(1, Prescription::where('medical_visit_id', $visit->id)->count());
    }

    public function test_transaction_rolls_back_when_a_medicine_line_cannot_be_saved(): void
    {
        $visit = $this->visit();
        $item = $this->item(Medicine::where('status', 'ACTIVE')->firstOrFail());
        PrescriptionItem::creating(static function (): void { throw new \RuntimeException('Simulated save failure'); });
        try {
            $this->withoutExceptionHandling()->post(route('admin.visits.prescription.store', $visit), ['items' => [$item], 'confirm_create' => 1]);
            $this->fail('Expected a simulated save failure.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated save failure', $exception->getMessage());
        } finally {
            PrescriptionItem::flushEventListeners();
        }
        $this->assertFalse($visit->prescription()->exists());
    }

    public function test_patient_cannot_access_the_admin_prescription_creation_routes(): void
    {
        $visit = $this->visit();
        $patient = User::where('business_code', 'BN000001')->firstOrFail();
        $item = $this->item(Medicine::where('status', 'ACTIVE')->firstOrFail());
        $this->actingAs($patient)->get(route('admin.visits.prescription.create', $visit))->assertRedirect(route('admin.login'));
        $this->actingAs($patient)->post(route('admin.visits.prescription.store', $visit), ['items' => [$item], 'confirm_create' => 1])->assertRedirect(route('admin.login'));
        $this->assertFalse($visit->prescription()->exists());
    }

    public function test_price_is_snapshotted_and_purchase_requires_confirmation(): void
    {
        $visit = $this->visit();
        $medicine = Medicine::where('unit', 'viên')->where('status', 'ACTIVE')->firstOrFail();
        $item = [...$this->item($medicine), 'quantity' => '1.25', 'usage_unit' => 'vỉ', 'unit_price' => 1, 'line_total' => 1];
        $this->post(route('admin.visits.prescription.store', $visit), ['items' => [$item], 'confirm_create' => 1])->assertSessionHasNoErrors();
        $prescription = $visit->prescription()->sole();
        $line = $prescription->items()->sole();
        $this->assertSame('18000.00', $line->unit_price);
        $this->assertSame('22500.00', $line->line_total);
        $this->assertNull($prescription->payable);
        $medicine->update(['price_per_blister' => 100]);
        $this->assertSame('22500.00', $prescription->fresh()->pricedTotal());
        $this->get(route('admin.prescriptions.show', $prescription))->assertOk()->assertSee('22.500')->assertSee('Tạo khoản thu tiền thuốc');
        $this->post(route('admin.prescriptions.payable.store', $prescription), [])->assertSessionHasErrors('confirm_purchase');
        $this->assertNull($prescription->fresh()->payable);
        $this->post(route('admin.prescriptions.payable.store', $prescription), ['confirm_purchase' => 1, 'amount' => 1])->assertSessionHasNoErrors();
        $fee = $prescription->fresh()->payable;
        $this->assertSame('22500.00', $fee->amount);
        $this->assertSame('MEDICINE_FEE', $fee->payment_type);
        $this->assertSame('UNPAID', $fee->paymentStatus->name);
        $this->post(route('admin.prescriptions.payable.store', $prescription), ['confirm_purchase' => 1])->assertSessionHasErrors('confirm_purchase');
        $this->get(route('admin.payables.show', $fee))->assertOk()->assertSee('Tiền thuốc')->assertSee($prescription->prescription_code);
        $patient = $visit->patient->user;
        $this->actingAs($patient)->get(route('patient.prescriptions'))->assertOk()->assertSee('22.500')->assertSee($prescription->prescription_code);
        $this->get(route('patient.payments.index'))->assertOk()->assertSee($fee->payable_code)->assertSee('Tiền thuốc');
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail())
            ->post(route('admin.payables.payment-transactions.store', $fee), ['payment_method' => 'CASH', 'confirm_payment' => 1])->assertSessionHasNoErrors();
        $this->assertSame('PAID', $fee->fresh()->paymentStatus->name);
        $this->assertSame('22500.00', $fee->fresh()->transactions()->sole()->amount);
    }

    public function test_missing_or_wrong_unit_price_cannot_create_prescription(): void
    {
        $visit = $this->visit();
        $medicine = Medicine::where('unit', 'viên')->firstOrFail();
        $medicine->update(['price_per_box' => null]);
        $this->post(route('admin.visits.prescription.store', $visit), [
            'items' => [[...$this->item($medicine), 'usage_unit' => 'hộp']], 'confirm_create' => 1,
        ])->assertSessionHasErrors('items.0.usage_unit');
        $this->assertFalse($visit->prescription()->exists());
    }

    public function test_cancelled_purchase_can_be_recreated_without_erasing_history(): void
    {
        $visit = $this->visit();
        $medicine = Medicine::where('unit', 'viên')->firstOrFail();
        $this->post(route('admin.visits.prescription.store', $visit), ['items' => [$this->item($medicine)], 'confirm_create' => 1])->assertSessionHasNoErrors();
        $prescription = $visit->prescription()->sole();
        $this->post(route('admin.prescriptions.payable.store', $prescription), ['confirm_purchase' => 1])->assertSessionHasNoErrors();
        $first = $prescription->fresh()->payable;
        $this->patch(route('admin.payables.cancel', $first), ['confirm_cancel' => 1, 'cancel_reason' => 'Bệnh nhân chưa mua'])->assertSessionHasNoErrors();
        $this->assertSame('CANCELLED', $first->fresh()->paymentStatus->name);
        $this->post(route('admin.prescriptions.payable.store', $prescription), ['confirm_purchase' => 1])->assertSessionHasNoErrors();
        $this->assertNotEquals($first->id, $prescription->fresh()->payable->id);
        $this->assertSame(2, \App\Models\Payable::where('prescription_id', $prescription->id)->count());
    }

    public function test_price_management_validates_units_and_patient_cannot_create_charge(): void
    {
        $medicine = Medicine::where('unit', 'viên')->firstOrFail();
        $data = ['medicine_code' => $medicine->medicine_code, 'name' => $medicine->name, 'unit' => '',
            'price_per_unit' => 2000, 'status' => 'ACTIVE'];
        $this->put(route('admin.medicines.update', $medicine), $data)->assertSessionHasErrors('unit');
        $data['unit'] = 'vỉ';
        $data['price_per_blister'] = 18000;
        $this->put(route('admin.medicines.update', $medicine), $data)->assertSessionHasErrors('unit');
        $data['unit'] = 'viên';
        $data['price_per_unit'] = '12.5';
        $this->put(route('admin.medicines.update', $medicine), $data)->assertSessionHasErrors('price_per_unit');
        $visit = $this->visit();
        $this->post(route('admin.visits.prescription.store', $visit), ['items' => [$this->item($medicine)], 'confirm_create' => 1])->assertSessionHasNoErrors();
        $prescription = $visit->prescription()->sole();
        $this->actingAs($visit->patient->user)->post(route('admin.prescriptions.payable.store', $prescription), ['confirm_purchase' => 1])->assertRedirect(route('admin.login'));
        $this->assertNull($prescription->fresh()->payable);
    }
}
