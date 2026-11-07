<?php

namespace Tests\Feature\Portal;

use App\Models\Prescription;
use App\Models\ServiceResult;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ClinicalPublicationSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_private_records_and_direct_documents_are_not_available_to_the_patient(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $user = User::where('business_code', 'BN000001')->firstOrFail();
        $result = ServiceResult::whereHas('serviceOrderItem.serviceOrder.medicalVisit', fn ($q) => $q->where('patient_id', $user->patient->id))->firstOrFail();
        $prescription = Prescription::whereHas('medicalVisit', fn ($q) => $q->where('patient_id', $user->patient->id))->firstOrFail();
        DB::table('service_results')->where('id', $result->id)->update(['patient_publication_status' => 'PRIVATE', 'result_summary' => 'PRIVATE-SENTINEL-RESULT']);
        DB::table('prescriptions')->where('id', $prescription->id)->update(['patient_publication_status' => 'PRIVATE']);

        $this->actingAs($user)->get(route('patient.results'))->assertOk()->assertDontSee('PRIVATE-SENTINEL-RESULT');
        $this->get(route('patient.prescriptions'))->assertOk()->assertDontSee($prescription->prescription_code);
        $this->get(route('patient.results.document', [$result->id, 'result_pdf']))->assertNotFound();
        $this->get(route('patient.prescriptions.document', $prescription->id))->assertNotFound();
    }

    public function test_only_explicitly_published_own_records_are_visible(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $user = User::where('business_code', 'BN000001')->firstOrFail();
        $other = User::where('business_code', 'BN000002')->firstOrFail();
        $result = ServiceResult::whereHas('serviceOrderItem.serviceOrder.medicalVisit', fn ($q) => $q->where('patient_id', $user->patient->id))->firstOrFail();
        $foreign = ServiceResult::whereHas('serviceOrderItem.serviceOrder.medicalVisit', fn ($q) => $q->where('patient_id', $other->patient->id))->firstOrFail();
        $prescription = Prescription::whereHas('medicalVisit', fn ($q) => $q->where('patient_id', $user->patient->id))->firstOrFail();
        DB::table('service_results')->where('id', $foreign->id)->update(['patient_publication_status' => 'PUBLIC', 'result_summary' => 'FOREIGN-SENTINEL']);
        foreach (['PUBLIC', 'PUBLISHED'] as $status) {
            DB::table('service_results')->where('id', $result->id)->update(['patient_publication_status' => $status, 'result_summary' => 'PUBLIC-SENTINEL']);
            DB::table('prescriptions')->where('id', $prescription->id)->update(['patient_publication_status' => $status]);
            $this->actingAs($user)->get(route('patient.results'))->assertOk()->assertSee('PUBLIC-SENTINEL')->assertDontSee('FOREIGN-SENTINEL');
            $this->get(route('patient.prescriptions'))->assertOk()->assertSee($prescription->prescription_code);
            $this->get(route('patient.results.document', [$foreign->id, 'result_pdf']))->assertNotFound();
        }
    }

    public function test_missing_publication_columns_fail_closed_without_server_error(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $user = User::where('business_code', 'BN000001')->firstOrFail();
        $prescription = Prescription::whereHas('medicalVisit', fn ($q) => $q->where('patient_id', $user->patient->id))->firstOrFail();
        $result = ServiceResult::whereHas('serviceOrderItem.serviceOrder.medicalVisit', fn ($q) => $q->where('patient_id', $user->patient->id))->firstOrFail();
        DB::table('service_results')->where('id', $result->id)->update(['result_summary' => 'SCHEMA-MISSING-SECRET']);
        foreach (['service_results', 'prescriptions'] as $table) {
            \Illuminate\Support\Facades\Schema::table($table, function ($blueprint) {
                $blueprint->dropIndex(['patient_publication_status']);
                $blueprint->dropColumn('patient_publication_status');
            });
        }

        $this->actingAs($user)->get(route('patient.results'))->assertOk()->assertDontSee('SCHEMA-MISSING-SECRET');
        $this->get(route('patient.prescriptions'))->assertOk()->assertDontSee($prescription->prescription_code);
        $this->get(route('patient.results.document', [$result->id, 'result_pdf']))->assertNotFound();
        $this->get(route('patient.prescriptions.document', $prescription->id))->assertNotFound();
        $this->get(route('patient.notifications', ['danh-muc' => 'ket-qua']))->assertOk()->assertDontSee($prescription->prescription_code);
    }

    public function test_actual_private_pdf_is_denied_and_published_pdf_is_accessible(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $disk = \Illuminate\Support\Facades\Storage::build(['driver' => 'local', 'root' => storage_path('app/clinical-test-'.\Illuminate\Support\Str::ulid())]);
        \Illuminate\Support\Facades\Storage::set('local', $disk);
        $user = User::where('business_code', 'BN000001')->firstOrFail();
        $result = ServiceResult::whereHas('serviceOrderItem.serviceOrder.medicalVisit', fn ($q) => $q->where('patient_id', $user->patient->id))->firstOrFail();
        $prescription = Prescription::whereHas('medicalVisit', fn ($q) => $q->where('patient_id', $user->patient->id))->firstOrFail();
        $resultPath = 'service-results/'.$result->service_order_item_id.'/test.pdf';
        $prescriptionPath = 'prescriptions/'.$prescription->id.'/test.pdf';
        $disk->put($resultPath, "%PDF-1.4\n%%EOF");
        $disk->put($prescriptionPath, "%PDF-1.4\n%%EOF");
        DB::table('service_results')->where('id', $result->id)->update(['patient_publication_status' => 'PRIVATE', 'result_pdf_path' => $resultPath]);
        DB::table('prescriptions')->where('id', $prescription->id)->update(['patient_publication_status' => 'PRIVATE', 'prescription_pdf_path' => $prescriptionPath]);
        $resultUrl = route('patient.results.document', [$result->id, 'result_pdf']);
        $prescriptionUrl = route('patient.prescriptions.document', $prescription->id);

        $this->actingAs($user)->get($resultUrl)->assertNotFound();
        $this->get($prescriptionUrl)->assertNotFound();
        DB::table('service_results')->where('id', $result->id)->update(['patient_publication_status' => 'PUBLIC']);
        DB::table('prescriptions')->where('id', $prescription->id)->update(['patient_publication_status' => 'PUBLISHED']);
        $this->get($resultUrl)->assertOk();
        $this->get($prescriptionUrl)->assertOk();
    }
}
