<?php

namespace Tests\Feature\Portal;

use App\Models\MedicalVisit;
use App\Models\ServiceResult;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientResultsVisibilityTest extends TestCase
{
    use RefreshDatabase;

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
    }
}
