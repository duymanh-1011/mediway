<?php

namespace Tests\Feature;

use App\Models\Disease;
use App\Models\MedicalVisit;
use App\Models\User;
use App\Models\VisitStatus;
use App\Services\DoctorClinicalVersion;
use Tests\Fixtures\Seeders\WorkflowFixtureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorSharedAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(WorkflowFixtureSeeder::class);
        $this->withoutVite();
        $this->withSession(['doctor_workspace_mode' => 'examination']);
        $this->actingAs(User::where('business_code', 'BS000001')->firstOrFail());
    }

    private function otherVisit(string $code): MedicalVisit
    {
        $source = MedicalVisit::where('visit_code', 'LK000005')->firstOrFail();
        $visit = $source->replicate();
        $visit->visit_code = $code;
        $visit->appointment_id = null;
        $visit->reception_queue_number = null;
        $visit->checked_in_at = now()->subHour();
        $visit->examination_started_at = now()->subMinutes(30);
        $visit->examination_ended_at = null;
        $visit->visit_status_id = VisitStatus::where('name', 'IN_EXAMINATION')->value('id');
        $visit->save();
        return $visit;
    }

    public function test_doctor_cannot_read_or_write_another_doctors_visit(): void
    {
        $visit = $this->otherVisit('LK-SHARED-NOTE');
        $this->get(route('doctor.visits.index', ['q' => $visit->visit_code]))->assertOk()->assertDontSee($visit->visit_code);
        $this->get(route('doctor.visits.show', $visit))->assertNotFound();
        $this->put(route('doctor.visits.clinical.update', $visit), [
            'record_version' => DoctorClinicalVersion::of(null), 'reason_for_visit' => 'Nội dung bác sĩ khác ghi',
        ])->assertNotFound();
        $disease = Disease::where('status', 'ACTIVE')->firstOrFail();
        $this->post(route('doctor.visits.diagnoses.store', $visit), [
            'disease_id' => $disease->id, 'diagnosis_type' => 'PRIMARY',
        ])->assertNotFound();
        $this->put(route('doctor.visits.conclusion.save', $visit), [
            'record_version' => DoctorClinicalVersion::of(null),
            'professional_conclusion' => 'Kết luận không được phép lưu',
            'has_follow_up' => '0',
        ])->assertNotFound();
        $this->assertNull($visit->clinicalNote()->first());
        $this->assertNull($visit->fresh()->conclusion);
    }

    public function test_doctor_cannot_create_orders_or_prescriptions_for_another_doctors_visit(): void
    {
        $visit = $this->otherVisit('LK-SHARED-ORDER');
        $serviceId = \App\Models\MedicalService::where('status', 'ACTIVE')->value('id');
        $this->post(route('doctor.visits.orders.store', $visit), ['medical_service_ids' => [$serviceId]])->assertNotFound();
        $this->assertFalse($visit->serviceOrders()->exists());

        $visit = $this->otherVisit('LK-SHARED-RX');
        $medicineId = \App\Models\Medicine::where('unit', 'viên')->where('status', 'ACTIVE')->value('id');
        $this->post(route('doctor.visits.prescriptions.store', $visit), [
            'confirm_create' => 1, 'items' => [['medicine_id' => $medicineId, 'quantity' => 10, 'usage_unit' => 'viên',
                'dose_per_use' => '1 viên', 'times_per_day' => 2, 'duration_days' => 5, 'usage_method' => 'Uống']],
        ])->assertNotFound();
        $this->assertNull($visit->fresh()->prescription);
    }
}
