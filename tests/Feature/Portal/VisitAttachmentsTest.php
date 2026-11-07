<?php

namespace Tests\Feature\Portal;

use App\Models\MedicalVisit;
use App\Models\ServiceResult;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VisitAttachmentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_attachments_follow_their_visit_and_cannot_be_accessed_by_another_patient(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        Storage::fake('local');
        $patient = User::where('business_code', 'BN000001')->firstOrFail();
        $other = User::where('business_code', 'BN000002')->firstOrFail();
        $own = ServiceResult::whereHas('serviceOrderItem.serviceOrder.medicalVisit', fn ($q) => $q->where('patient_id', $patient->patient->id))->with('serviceOrderItem.serviceOrder')->firstOrFail();
        $second = ServiceResult::whereHas('serviceOrderItem.serviceOrder.medicalVisit', fn ($q) => $q->where('patient_id', $other->patient->id))->with('serviceOrderItem.serviceOrder')->firstOrFail();
        $firstVisit = $own->serviceOrderItem->serviceOrder->medical_visit_id;
        $secondVisit = $second->serviceOrderItem->serviceOrder->medical_visit_id;
        MedicalVisit::whereKey($secondVisit)->update(['patient_id' => $patient->patient->id, 'appointment_id' => null]);

        foreach ([$own, $second] as $result) {
            $path = 'service-results/'.$result->service_order_item_id.'/result.pdf';
            Storage::disk('local')->put($path, "%PDF-1.4\n%%EOF");
            $result->update(['result_pdf_path' => $path]);
        }
        $imagePath = 'service-results/'.$second->service_order_item_id.'/image.png';
        Storage::disk('local')->put($imagePath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a3p8AAAAASUVORK5CYII='));
        $second->update(['result_image_path' => $imagePath]);
        $prescription = $patient->patient->medicalVisits()->whereKey($firstVisit)->firstOrFail()->prescription;
        $this->assertNotNull($prescription);
        $prescriptionPath = 'prescriptions/'.$prescription->id.'/prescription.pdf';
        Storage::disk('local')->put($prescriptionPath, "%PDF-1.4\n%%EOF");
        $prescription->update(['prescription_pdf_path' => $prescriptionPath]);

        $firstUrl = route('patient.results.document', [$own->id, 'result_pdf']);
        $secondUrl = route('patient.results.document', [$second->id, 'result_pdf']);
        $imageUrl = route('patient.results.document', [$second->id, 'result_image']);
        $prescriptionUrl = route('patient.prescriptions.document', $prescription->id);
        $this->actingAs($patient)->get(route('patient.results'))->assertOk()
            ->assertSee(route('patient.results.visit', $firstVisit))->assertSee(route('patient.results.visit', $secondVisit))
            ->assertDontSee($firstUrl)->assertDontSee($secondUrl);
        $this->get(route('patient.results.visit', ['visit' => $firstVisit, 'tab' => 'giay-to']))->assertOk()
            ->assertSee($firstUrl)->assertSee($prescriptionUrl)->assertDontSee($secondUrl)->assertDontSee($imageUrl);
        $this->get(route('patient.results.visit', ['visit' => $secondVisit, 'tab' => 'giay-to']))->assertOk()
            ->assertSee($secondUrl)->assertSee($imageUrl)->assertDontSee($firstUrl)->assertDontSee($prescriptionUrl);
        $this->get($firstUrl)->assertOk();
        $this->actingAs($other)->get($firstUrl)->assertNotFound();
        $this->get($imageUrl)->assertNotFound();
        $this->get($prescriptionUrl)->assertNotFound();
    }

    public function test_personal_documents_are_available_in_profile_not_as_visit_attachments(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        Storage::fake('local');
        $patient = User::where('business_code', 'BN000001')->firstOrFail();
        $path = 'patient-documents/'.$patient->patient->id.'/profile.pdf';
        Storage::disk('local')->put($path, "%PDF-1.4\n%%EOF");
        $patient->patient->detail->update(['profile_pdf_path' => $path]);
        $url = route('patient.documents.show', 'profile-pdf');
        $visit = $patient->patient->medicalVisits()->firstOrFail();
        $this->actingAs($patient)->get(route('patient.results.visit', ['visit' => $visit->id, 'tab' => 'giay-to']))->assertOk()
            ->assertDontSee($url)->assertSee('Lượt khám này chưa có tệp đính kèm.');
        $this->get(route('patient.profile.show'))->assertOk()->assertSee($url)->assertSee('Giấy tờ cá nhân');
        $this->get($url)->assertOk();
        $other = User::where('business_code', 'BN000002')->firstOrFail();
        $this->actingAs($other)->get($url)->assertNotFound();
    }
}
