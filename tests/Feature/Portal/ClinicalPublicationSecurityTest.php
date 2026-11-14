<?php

namespace Tests\Feature\Portal;

use App\Models\Prescription;
use App\Models\ServiceResult;
use App\Models\ServiceStatus;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ClinicalPublicationSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_saved_service_results_are_visible_before_completion(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $user = User::where('business_code', 'BN000001')->firstOrFail();
        $result = ServiceResult::whereHas('serviceOrderItem.serviceOrder.medicalVisit', fn ($q) => $q->where('patient_id', $user->patient->id))->firstOrFail();
        $prescription = Prescription::whereHas('medicalVisit', fn ($q) => $q->where('patient_id', $user->patient->id))->firstOrFail();
        $item = $result->serviceOrderItem;
        $item->update(['service_status_id' => ServiceStatus::where('name', 'PROCESSING')->value('id')]);
        DB::table('service_results')->where('id', $result->id)->update(['patient_publication_status' => 'PUBLISHED', 'result_summary' => 'DRAFT-SENTINEL-RESULT']);
        DB::table('prescriptions')->where('id', $prescription->id)->update(['patient_publication_status' => 'PRIVATE']);

        $this->actingAs($user)->get(route('patient.results.visit', ['visit' => $item->serviceOrder->medical_visit_id, 'tab' => 'dich-vu']))
            ->assertOk()->assertSee('DRAFT-SENTINEL-RESULT');
        $this->get(route('patient.notifications', ['danh-muc' => 'ket-qua']))
            ->assertOk()->assertSee('Có kết quả dịch vụ');
        $this->get(route('patient.prescriptions'))->assertOk()->assertSee($prescription->prescription_code);
        $this->get(route('patient.results.service-print', $result))->assertOk();
        $this->get(route('patient.results.document', [$result->id, 'result_pdf']))->assertNotFound();
        $this->get(route('patient.prescriptions.document', $prescription->id))->assertNotFound();
    }

    public function test_only_own_completed_results_are_visible_regardless_of_legacy_flag(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $user = User::where('business_code', 'BN000001')->firstOrFail();
        $other = User::where('business_code', 'BN000002')->firstOrFail();
        $result = ServiceResult::whereHas('serviceOrderItem.serviceOrder.medicalVisit', fn ($q) => $q->where('patient_id', $user->patient->id))->firstOrFail();
        $foreign = ServiceResult::whereHas('serviceOrderItem.serviceOrder.medicalVisit', fn ($q) => $q->where('patient_id', $other->patient->id))->firstOrFail();
        $prescription = Prescription::whereHas('medicalVisit', fn ($q) => $q->where('patient_id', $user->patient->id))->firstOrFail();
        DB::table('service_results')->where('id', $foreign->id)->update(['patient_publication_status' => 'PUBLIC', 'result_summary' => 'FOREIGN-SENTINEL']);
        foreach (['PRIVATE', 'PUBLIC', 'PUBLISHED'] as $status) {
            DB::table('service_results')->where('id', $result->id)->update(['patient_publication_status' => $status, 'result_summary' => 'COMPLETED-SENTINEL']);
            DB::table('prescriptions')->where('id', $prescription->id)->update(['patient_publication_status' => $status]);
            $this->actingAs($user)->get(route('patient.results'))->assertOk()->assertDontSee('FOREIGN-SENTINEL');
            $this->get(route('patient.results.visit', ['visit' => $result->serviceOrderItem->serviceOrder->medical_visit_id, 'tab' => 'dich-vu', 'dich-vu' => $result->service_order_item_id]))
                ->assertOk()->assertSee('COMPLETED-SENTINEL')->assertDontSee('FOREIGN-SENTINEL');
            $this->get(route('patient.prescriptions'))->assertOk()->assertSee($prescription->prescription_code);
            $this->get(route('patient.results.service-print', $foreign))->assertNotFound();
            $this->get(route('patient.results.document', [$foreign->id, 'result_pdf']))->assertNotFound();
        }
    }

    public function test_completed_legacy_result_needs_no_admin_publication_action(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $patient = User::where('business_code', 'BN000001')->firstOrFail();
        $result = ServiceResult::whereHas('serviceOrderItem.serviceOrder.medicalVisit', fn ($query) => $query->where('patient_id', $patient->patient->id))->firstOrFail();
        $print = route('patient.results.service-print', $result);

        $this->assertFalse(\Illuminate\Support\Facades\Route::has('admin.service-results.publish'));
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail())
            ->get(route('admin.service-results.show', $result))->assertOk()->assertDontSee('Công bố cho bệnh nhân');
        $this->patch(url('/admin/service-results/'.$result->id.'/publish'))->assertNotFound();
        $this->actingAs($patient)->get($print)->assertOk()->assertSee($result->result_summary);
        $this->assertSame('PRIVATE', $result->fresh()->patient_publication_status);
    }

    public function test_completed_records_are_visible_without_legacy_publication_columns(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $user = User::where('business_code', 'BN000001')->firstOrFail();
        $prescription = Prescription::whereHas('medicalVisit', fn ($q) => $q->where('patient_id', $user->patient->id))->firstOrFail();
        $result = ServiceResult::whereHas('serviceOrderItem.serviceOrder.medicalVisit', fn ($q) => $q->where('patient_id', $user->patient->id))->firstOrFail();
        DB::table('service_results')->where('id', $result->id)->update(['result_summary' => 'COMPLETED-LEGACY-RESULT']);
        foreach (['service_results', 'prescriptions'] as $table) {
            \Illuminate\Support\Facades\Schema::table($table, function ($blueprint) {
                $blueprint->dropIndex(['patient_publication_status']);
                $blueprint->dropColumn('patient_publication_status');
            });
        }

        $this->actingAs($user)->get(route('patient.results.service-print', $result))->assertOk()->assertSee('COMPLETED-LEGACY-RESULT');
        $this->get(route('patient.prescriptions'))->assertOk()->assertSee($prescription->prescription_code);
        $this->get(route('patient.results.document', [$result->id, 'result_pdf']))->assertNotFound();
        $this->get(route('patient.prescriptions.document', $prescription->id))->assertNotFound();
        $this->get(route('patient.notifications', ['danh-muc' => 'ket-qua']))->assertOk()->assertSee($prescription->prescription_code);
    }

    public function test_service_pdf_is_visible_before_completion_but_not_for_cancelled_order(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $disk = \Illuminate\Support\Facades\Storage::fake('local');
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
        $item = $result->serviceOrderItem;
        $completedStatusId = $item->service_status_id;
        $item->update(['service_status_id' => ServiceStatus::where('name', 'PROCESSING')->value('id')]);

        $this->actingAs($user)->get($resultUrl)->assertOk();
        $this->get($prescriptionUrl)->assertOk();
        $item->update(['service_status_id' => $completedStatusId]);
        $this->get($resultUrl)->assertOk();
        $item->serviceOrder()->update(['status' => 'CANCELLED']);
        $this->get($resultUrl)->assertNotFound();
        $this->get(route('patient.results.service-print', $result))->assertNotFound();
    }
}
