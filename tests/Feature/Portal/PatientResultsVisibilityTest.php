<?php

namespace Tests\Feature\Portal;

use App\Models\AuditLog;
use App\Models\MedicalVisit;
use App\Models\ServiceResult;
use App\Models\Prescription;
use App\Models\User;
use App\Models\VisitStatus;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientResultsVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_sees_own_completed_visit_prescription_without_publication_and_cannot_open_others(): void
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
        $this->get(route('patient.prescriptions.show', $own->id))->assertOk()->assertSee($own->items->first()->medicine->name)
            ->assertSee('prescription-doctor-signature')->assertSee('BÁC SĨ KÊ ĐƠN')
            ->assertSee($own->doctor->full_name)->assertSee('(Ký, ghi rõ họ tên)');
        $this->get(route('patient.prescriptions.show', $otherPrescription->id))->assertNotFound();
        $this->get(route('patient.prescriptions.document', $otherPrescription->id))->assertNotFound();
        $this->get(route('patient.prescriptions', ['visit' => $otherPrescription->medical_visit_id]))->assertNotFound();
        $this->get(route('patient.prescriptions', ['visit' => $own->medical_visit_id]))->assertOk()->assertSee($own->prescription_code);
        $this->assertSame('PRIVATE', $own->fresh()->patient_publication_status);
    }

    public function test_prescription_quantities_are_easy_to_read_without_losing_decimals(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $patient = User::where('business_code', 'BN000001')->firstOrFail();
        $prescription = Prescription::whereHas('medicalVisit', fn ($query) => $query->where('patient_id', $patient->patient->id))->with('items')->firstOrFail();
        \Illuminate\Support\Facades\DB::table('prescriptions')->where('id', $prescription->id)->update(['patient_publication_status' => 'PUBLIC']);
        $prescription->items->first()->update(['quantity' => 10, 'times_per_day' => 2.5]);

        $this->actingAs($patient)->get(route('patient.prescriptions'))->assertOk()
            ->assertSee('10 viên')->assertSee('2,5 lần/ngày')->assertDontSee('10.00 viên');
    }

    public function test_prescription_access_follows_visit_completion_for_list_detail_pdf_and_notifications(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        \Illuminate\Support\Facades\Storage::fake('local');
        $patient = User::where('business_code', 'BN000001')->firstOrFail();
        $prescription = Prescription::whereHas('medicalVisit', fn ($query) => $query->where('patient_id', $patient->patient->id))->firstOrFail();
        $visit = $prescription->medicalVisit;
        $path = 'prescriptions/'.$prescription->id.'/prescription.pdf';
        \Illuminate\Support\Facades\Storage::disk('local')->put($path, "%PDF-1.4\n%%EOF");
        $prescription->update(['prescription_pdf_path' => $path]);
        $this->actingAs($patient);
        foreach (['WAITING', 'IN_EXAMINATION', 'WAITING_FOR_SERVICE'] as $status) {
            $visit->update(['visit_status_id' => VisitStatus::where('name', $status)->firstOrFail()->id]);
            $this->get(route('patient.prescriptions'))->assertOk()->assertDontSee($prescription->prescription_code);
            $this->get(route('patient.results.visit', ['visit' => $visit->id, 'tab' => 'don-thuoc']))->assertOk()->assertDontSee($prescription->prescription_code);
            $this->get(route('patient.prescriptions.show', $prescription))->assertNotFound();
            $this->get(route('patient.prescriptions.document', $prescription))->assertNotFound();
            $this->get(route('patient.notifications', ['danh-muc' => 'ket-qua']))->assertOk()->assertDontSee($prescription->prescription_code);
        }
        $visit->update(['visit_status_id' => VisitStatus::where('name', 'COMPLETED')->firstOrFail()->id]);
        $this->assertSame('PRIVATE', $prescription->fresh()->patient_publication_status);
        $this->get(route('patient.prescriptions'))->assertOk()->assertSee($prescription->prescription_code);
        $this->get(route('patient.results.visit', ['visit' => $visit->id, 'tab' => 'don-thuoc']))->assertOk()->assertSee($prescription->prescription_code);
        $this->get(route('patient.prescriptions.show', $prescription))->assertOk();
        $this->get(route('patient.prescriptions.document', $prescription))->assertOk();
        $this->get(route('patient.notifications', ['danh-muc' => 'ket-qua']))->assertOk()->assertSee($prescription->prescription_code);
    }

    public function test_patient_sees_published_service_results_and_own_visit_results(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $patient = User::where('business_code', 'BN000001')->firstOrFail();
        $other = User::where('business_code', 'BN000002')->firstOrFail();
        $own = ServiceResult::whereHas('serviceOrderItem.serviceOrder.medicalVisit', fn ($q) => $q->where('patient_id', $patient->patient->id))->firstOrFail();
        $others = ServiceResult::whereHas('serviceOrderItem.serviceOrder.medicalVisit', fn ($q) => $q->where('patient_id', $other->patient->id))->firstOrFail();
        $own->update(['result_summary' => 'Kết quả dịch vụ của bệnh nhân A', 'verified_by_user_id' => null]);
        \Illuminate\Support\Facades\DB::table('service_results')->where('id', $own->id)->update(['patient_publication_status' => 'PUBLIC']);
        $others->update(['result_summary' => 'Kết quả riêng của bệnh nhân B']);
        $visit = MedicalVisit::where('patient_id', $patient->patient->id)->whereHas('conclusion')->firstOrFail();
        $visit->conclusion->update(['professional_conclusion' => 'Kết luận khám của bệnh nhân A']);
        $otherVisit = MedicalVisit::where('patient_id', $other->patient->id)->whereHas('conclusion')->firstOrFail();
        $otherVisit->conclusion->update(['professional_conclusion' => 'Kết luận riêng của bệnh nhân B']);

        $this->actingAs($patient)->get(route('patient.results'))->assertOk()
            ->assertSee(route('patient.results.visit', $visit->id))->assertDontSee($otherVisit->visit_code);
        $this->get(route('patient.results.visit', ['visit' => $visit->id, 'tab' => 'dich-vu', 'dich-vu' => $own->service_order_item_id]))
            ->assertOk()->assertSee('Kết quả dịch vụ của bệnh nhân A')->assertSee('Chưa xác minh')
            ->assertSee('Đơn giá ghi nhận')->assertSee('Số lượng')->assertDontSee('Kết quả riêng của bệnh nhân B');
        $this->get(route('patient.results.visit', $visit->id))->assertOk()
            ->assertSee('Kết luận khám của bệnh nhân A')->assertDontSee('Kết luận riêng của bệnh nhân B');
        $this->get(route('patient.results.visit', $otherVisit->id))->assertNotFound();
        $this->get(route('patient.results.document', [$others->id, 'result_pdf']))->assertNotFound();
        $this->get(route('patient.notifications', ['danh-muc' => 'ket-qua']))->assertOk()->assertSee('Có kết quả dịch vụ');
    }

    public function test_legacy_completed_visit_only_recovers_the_time_of_a_recorded_transition(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $patient = User::where('business_code', 'BN000001')->firstOrFail();
        $visit = $patient->patient->medicalVisits()->whereHas('status', fn ($status) => $status->where('name', 'COMPLETED'))->firstOrFail();
        $visit->update(['examination_ended_at' => null]);
        $url = route('patient.results.visit', $visit->id);
        $this->actingAs($patient)->get($url)->assertOk()->assertSee('Chưa ghi nhận thời điểm');

        $completion = $visit->checked_in_at->copy()->addHours(2);
        AuditLog::create([
            'user_id' => User::where('business_code', 'QT000001')->value('id'),
            'role_name_snapshot' => 'Administrator', 'action' => 'STATUS_CHANGE',
            'entity_type' => 'medical_visits', 'entity_id' => $visit->id,
            'old_values' => ['visit_status_id' => VisitStatus::where('name', 'IN_EXAMINATION')->value('id')],
            'new_values' => ['visit_status_id' => $visit->visit_status_id], 'performed_at' => $completion,
        ]);
        $this->get($url)->assertOk()->assertSee($completion->format('d/m/Y H:i'));
        $this->get(route('patient.results'))->assertOk()->assertSee($completion->format('d/m/Y H:i'));
        $this->assertNull($visit->fresh()->examination_ended_at);
    }
}
