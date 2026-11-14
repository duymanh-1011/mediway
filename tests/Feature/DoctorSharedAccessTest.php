<?php

namespace Tests\Feature;

use App\Models\Disease;
use App\Models\MedicalService;
use App\Models\MedicalVisit;
use App\Models\Medicine;
use App\Models\User;
use App\Models\VisitStatus;
use App\Services\DoctorClinicalVersion;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorSharedAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
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

    public function test_doctor_can_see_and_write_another_doctors_visit_but_patient_cannot(): void
    {
        $visit = $this->otherVisit('LK-SHARED-NOTE');
        $this->get(route('doctor.visits.index', ['q' => $visit->visit_code]))->assertOk()->assertSee($visit->visit_code);
        $this->get(route('doctor.visits.show', $visit))->assertOk()->assertSee($visit->doctor->full_name);
        $this->put(route('doctor.visits.clinical.update', $visit), [
            'record_version' => DoctorClinicalVersion::of(null), 'reason_for_visit' => 'Nội dung bác sĩ khác ghi',
        ])->assertSessionHasNoErrors();
        $disease = Disease::where('status', 'ACTIVE')->firstOrFail();
        $this->post(route('doctor.visits.diagnoses.store', $visit), ['disease_id' => $disease->id, 'diagnosis_type' => 'PRIMARY'])->assertSessionHasNoErrors();
        $this->put(route('doctor.visits.conclusion.save', $visit), [
            'record_version' => DoctorClinicalVersion::of(null), 'professional_conclusion' => 'Kết luận bác sĩ thực hiện', 'has_follow_up' => '0',
        ])->assertSessionHasNoErrors();
        $this->assertSame(User::where('business_code', 'BS000001')->firstOrFail()->doctor->id, $visit->fresh()->conclusion->doctor_id);
        $this->assertNotSame($visit->doctor_id, $visit->fresh()->conclusion->doctor_id);
        $this->flushSession()->actingAs(User::where('business_code', 'BN000001')->firstOrFail())
            ->get(route('doctor.visits.show', $visit))->assertForbidden();
    }

    public function test_orders_and_prescriptions_record_the_doctor_who_created_them(): void
    {
        $doctor = User::where('business_code', 'BS000001')->firstOrFail()->doctor;
        $visit = $this->otherVisit('LK-SHARED-ORDER');
        $service = MedicalService::where('service_code', 'DVXN007')->firstOrFail();
        $this->post(route('doctor.visits.orders.store', $visit), ['medical_service_ids' => [$service->id]])->assertSessionHasNoErrors();
        $this->assertSame($doctor->id, $visit->serviceOrders()->sole()->ordered_by_doctor_id);

        $visit = $this->otherVisit('LK-SHARED-RX');
        $medicine = Medicine::where('unit', 'viên')->where('status', 'ACTIVE')->firstOrFail();
        $medicine->update(['price_per_unit' => 2000]);
        $this->post(route('doctor.visits.prescriptions.store', $visit), [
            'confirm_create' => 1, 'items' => [['medicine_id' => $medicine->id, 'quantity' => 10, 'usage_unit' => 'viên',
                'dose_per_use' => '1 viên', 'times_per_day' => 2, 'duration_days' => 5, 'usage_method' => 'Uống']],
        ])->assertSessionHasNoErrors();
        $this->assertSame($doctor->id, $visit->fresh()->prescription->doctor_id);
        $this->assertNotSame($visit->doctor_id, $visit->fresh()->prescription->doctor_id);
    }
}
