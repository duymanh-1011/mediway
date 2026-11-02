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
}
