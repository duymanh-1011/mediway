<?php

namespace Tests\Feature\Portal;

use App\Models\MedicalVisit;
use App\Models\ServiceResult;
use App\Models\Prescription;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientResultsVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_sees_own_prescriptions_without_publication_and_cannot_open_others(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $patient = User::where('business_code', 'BN000001')->firstOrFail();
        $other = User::where('business_code', 'BN000002')->firstOrFail();
        $own = Prescription::whereHas('medicalVisit', fn ($q) => $q->where('patient_id', $patient->patient->id))->with('items.medicine')->firstOrFail();
        $otherPrescription = Prescription::whereHas('medicalVisit', fn ($q) => $q->where('patient_id', $other->patient->id))->firstOrFail();
        $this->assertSame('PRIVATE', $own->getAttribute('patient_publication_status'));
        $response = $this->actingAs($patient)->get(route('patient.prescriptions'))->assertOk()
            ->assertSee($own->prescription_code)->assertDontSee($otherPrescription->prescription_code);
        $response->assertSee($own->items->first()->medicine->name);
        $this->get(route('patient.prescriptions.document', $otherPrescription->id))->assertNotFound();
    }

    public function test_prescription_quantities_are_easy_to_read_without_losing_decimals(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $patient = User::where('business_code', 'BN000001')->firstOrFail();
        $prescription = Prescription::whereHas('medicalVisit', fn ($query) => $query->where('patient_id', $patient->patient->id))->with('items')->firstOrFail();
        $prescription->items->first()->update(['quantity' => 10, 'times_per_day' => 2.5]);

        $this->actingAs($patient)->get(route('patient.prescriptions'))->assertOk()
            ->assertSee('10 viên')->assertSee('2,5 lần/ngày')->assertDontSee('10.00 viên');
    }

    public function test_patient_sees_own_service_and_visit_results_as_soon_as_they_exist(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $patient = User::where('business_code', 'BN000001')->firstOrFail();
        $other = User::where('business_code', 'BN000002')->firstOrFail();
        $own = ServiceResult::whereHas('serviceOrderItem.serviceOrder.medicalVisit', fn ($q) => $q->where('patient_id', $patient->patient->id))->firstOrFail();
        $others = ServiceResult::whereHas('serviceOrderItem.serviceOrder.medicalVisit', fn ($q) => $q->where('patient_id', $other->patient->id))->firstOrFail();
        $own->update(['result_summary' => 'Kết quả dịch vụ của bệnh nhân A', 'verified_by_user_id' => null]);
        $others->update(['result_summary' => 'Kết quả riêng của bệnh nhân B']);
        $visit = MedicalVisit::where('patient_id', $patient->patient->id)->whereHas('conclusion')->firstOrFail();
        $visit->conclusion->update(['professional_conclusion' => 'Kết luận khám của bệnh nhân A']);
        $otherVisit = MedicalVisit::where('patient_id', $other->patient->id)->whereHas('conclusion')->firstOrFail();
        $otherVisit->conclusion->update(['professional_conclusion' => 'Kết luận riêng của bệnh nhân B']);

        $this->actingAs($patient)->get(route('patient.results'))
            ->assertOk()->assertSee('Kết quả dịch vụ của bệnh nhân A')->assertSee('Chưa xác minh')
            ->assertSee('Kết luận khám của bệnh nhân A')->assertDontSee('Kết quả riêng của bệnh nhân B')
            ->assertDontSee('Kết luận riêng của bệnh nhân B');
        $this->get(route('patient.results.document', [$others->id, 'result_pdf']))->assertNotFound();
        $this->get(route('patient.notifications', ['danh-muc' => 'ket-qua']))->assertOk()->assertSee('Có kết quả dịch vụ');
    }
}
