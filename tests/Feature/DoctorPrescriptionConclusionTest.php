<?php

namespace Tests\Feature;

use App\Models\MedicalVisit;
use App\Models\Medicine;
use App\Models\Payable;
use App\Models\PaymentStatus;
use App\Models\User;
use App\Models\VisitStatus;
use App\Services\DoctorClinicalVersion;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorPrescriptionConclusionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $this->actingAs(User::where('business_code', 'BS000001')->firstOrFail());
    }

    private function visit(): MedicalVisit
    {
        $visit = MedicalVisit::where('visit_code', 'LK000004')->firstOrFail();
        $visit->update(['appointment_id' => null, 'visit_status_id' => VisitStatus::where('name', 'IN_EXAMINATION')->value('id')]);
        return $visit->fresh();
    }

    private function item(Medicine $medicine): array
    {
        return ['medicine_id' => $medicine->id, 'quantity' => '10', 'usage_unit' => 'viên', 'dose_per_use' => '1 viên',
            'times_per_day' => '2', 'duration_days' => '5', 'usage_method' => 'Uống'];
    }

    private function prescription(MedicalVisit $visit): void
    {
        $medicine = Medicine::where('unit', 'viên')->where('status', 'ACTIVE')->firstOrFail();
        $medicine->update(['price_per_unit' => 2000]);
        $this->post(route('doctor.visits.prescriptions.store', $visit), ['items' => [$this->item($medicine)], 'confirm_create' => '1'])
            ->assertSessionHasNoErrors();
    }

    public function test_doctor_creates_prescription_for_own_visit_and_patient_can_see_it(): void
    {
        $visit = $this->visit();
        $this->get(route('doctor.visits.prescriptions.create', $visit))->assertOk()->assertSee('Thêm thuốc');
        $this->prescription($visit);
        $prescription = $visit->fresh()->prescription;
        $this->assertNotNull($prescription);
        $this->assertSame($visit->doctor_id, $prescription->doctor_id);
        $this->assertCount(1, $prescription->items);
        $this->assertDatabaseHas('audit_logs', ['entity_type' => 'prescriptions', 'entity_id' => $prescription->id, 'role_name_snapshot' => 'Doctor']);
        $this->get(route('doctor.visits.show', $visit))->assertOk()->assertSee($prescription->prescription_code);
        $this->post(route('doctor.visits.prescriptions.store', $visit), ['items' => [$this->item($prescription->items->first()->medicine)], 'confirm_create' => '1'])->assertSessionHasErrors('prescription');
        $this->flushSession()->actingAs($visit->patient->user)->get(route('patient.prescriptions'))->assertOk()->assertSee($prescription->prescription_code);
    }

    public function test_prescription_rejects_invalid_input_and_other_doctors_visit(): void
    {
        $visit = $this->visit();
        $foreign = MedicalVisit::where('visit_code', 'LK000005')->firstOrFail();
        $medicine = Medicine::where('unit', 'viên')->firstOrFail();
        $this->post(route('doctor.visits.prescriptions.store', $visit), ['items' => [[...$this->item($medicine), 'quantity' => 0]], 'confirm_create' => '1'])->assertSessionHasErrors('items.0.quantity');
        $this->post(route('doctor.visits.prescriptions.store', $foreign), ['items' => [$this->item($medicine)], 'confirm_create' => '1'])->assertNotFound();
        $this->assertNull($visit->fresh()->prescription);
    }

    public function test_doctor_prints_only_prescription_for_assigned_visit(): void
    {
        $visit = $this->visit();
        $this->get(route('doctor.visits.prescriptions.print', $visit))->assertNotFound();
        $this->prescription($visit);
        $prescription = $visit->fresh()->prescription;
        $response = $this->get(route('doctor.visits.prescriptions.print', $visit))->assertOk()
            ->assertSee('ĐƠN THUỐC')->assertSee($prescription->prescription_code)
            ->assertSee($prescription->items->first()->medicine->name)
            ->assertSee('href="'.route('doctor.visits.show', $visit).'"', false)
            ->assertDontSee(route('patient.prescriptions.document', $prescription));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $other = MedicalVisit::where('visit_code', 'LK000002')->firstOrFail();
        $this->get(route('doctor.visits.prescriptions.print', $other))->assertNotFound();
        $this->flushSession()->actingAs(User::where('business_code', 'BN000001')->firstOrFail())
            ->get(route('doctor.visits.prescriptions.print', $visit))->assertForbidden();
    }

    public function test_prescription_accepts_multiple_distinct_medicines_and_rejects_duplicate_medicine(): void
    {
        $visit = $this->visit();
        $medicines = Medicine::where('unit', 'viên')->where('status', 'ACTIVE')->limit(2)->get();
        foreach ($medicines as $medicine) $medicine->update(['price_per_unit' => 2000]);
        $items = [$this->item($medicines[0]), $this->item($medicines[1])];
        $url = route('doctor.visits.prescriptions.store', $visit);
        $this->post($url, ['items' => [$items[0], $items[0]], 'confirm_create' => '1'])->assertSessionHasErrors('items.0.medicine_id');
        $this->assertNull($visit->fresh()->prescription);
        $this->post($url, ['items' => $items, 'confirm_create' => '1'])->assertSessionHasNoErrors();
        $this->assertSame(2, $visit->fresh()->prescription->items()->count());
    }

    public function test_prescription_cancel_checks_payable_and_stale_changes(): void
    {
        $visit = $this->visit();
        $this->prescription($visit);
        $prescription = $visit->fresh()->prescription;
        $url = route('doctor.visits.prescriptions.update', $visit);
        $version = DoctorClinicalVersion::of($prescription);
        $this->put($url, ['record_version' => $version, 'action' => 'save', 'general_instructions' => 'Lời dặn kiểm thử'])->assertSessionHasNoErrors();
        $this->put($url, ['record_version' => $version, 'action' => 'cancel'])->assertSessionHasErrors('record_version');
        $payable = Payable::create(['payable_code' => 'PTTESTRX', 'medical_visit_id' => $visit->id, 'prescription_id' => $prescription->id,
            'payment_type' => 'MEDICINE_FEE', 'description' => 'Kiểm thử', 'amount' => 20000,
            'payment_status_id' => PaymentStatus::where('name', 'UNPAID')->value('id'), 'generated_at' => now()]);
        $payload = ['record_version' => DoctorClinicalVersion::of($prescription->fresh()), 'action' => 'cancel'];
        $this->put($url, $payload)->assertSessionHasErrors('prescription');
        $this->assertSame('ACTIVE', $prescription->fresh()->status);
        $payable->update(['payment_status_id' => PaymentStatus::where('name', 'CANCELLED')->value('id')]);
        $this->put($url, $payload)->assertSessionHasNoErrors();
        $this->assertSame('CANCELLED', $prescription->fresh()->status);
    }

    public function test_conclusion_requires_valid_content_and_rejects_stale_or_unauthorized_writes(): void
    {
        $visit = $this->visit();
        $url = route('doctor.visits.conclusion.save', $visit);
        $emptyVersion = DoctorClinicalVersion::of(null);
        $this->put($url, ['record_version' => $emptyVersion, 'has_follow_up' => '0'])->assertSessionHasErrors('professional_conclusion');
        $payload = ['record_version' => $emptyVersion, 'professional_conclusion' => 'Kết luận kiểm thử', 'has_follow_up' => '0'];
        $this->put($url, $payload)->assertSessionHasNoErrors();
        $conclusion = $visit->fresh()->conclusion;
        $this->assertSame('Kết luận kiểm thử', $conclusion->professional_conclusion);
        $this->assertSame($visit->doctor_id, $conclusion->doctor_id);
        $this->put($url, $payload)->assertSessionHasErrors('record_version');
        $this->assertSame('Kết luận kiểm thử', $conclusion->fresh()->professional_conclusion);
        $foreign = MedicalVisit::where('visit_code', 'LK000005')->firstOrFail();
        $this->put(route('doctor.visits.conclusion.save', $foreign), $payload)->assertNotFound();
        $visit->update(['visit_status_id' => VisitStatus::where('name', 'COMPLETED')->value('id')]);
        $this->put($url, [...$payload, 'record_version' => DoctorClinicalVersion::of($conclusion->fresh())])->assertSessionHasErrors('visit');
    }

    public function test_follow_up_requires_date_and_reason_and_waiting_service_blocks_changes(): void
    {
        $visit = $this->visit();
        $url = route('doctor.visits.conclusion.save', $visit);
        $payload = ['record_version' => DoctorClinicalVersion::of(null), 'professional_conclusion' => 'Kết luận', 'has_follow_up' => '1'];
        $this->put($url, $payload)->assertSessionHasErrors(['follow_up_date', 'follow_up_reason']);
        $this->assertNull($visit->fresh()->conclusion);
        $visit->update(['visit_status_id' => VisitStatus::where('name', 'WAITING_FOR_SERVICE')->value('id')]);
        $this->put($url, [...$payload, 'has_follow_up' => '0'])->assertSessionHasErrors('visit');
        $this->assertNull($visit->fresh()->conclusion);
    }
}
