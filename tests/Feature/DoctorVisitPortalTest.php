<?php

namespace Tests\Feature;

use App\Models\MedicalVisit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorVisitPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
    }

    public function test_doctor_uses_common_login_and_sees_only_assigned_visits(): void
    {
        $doctor = User::where('business_code', 'BS000001')->firstOrFail();
        $own = MedicalVisit::where('visit_code', 'LK000001')->firstOrFail();
        $other = MedicalVisit::where('visit_code', 'LK000002')->firstOrFail();
        $this->post(route('patient.login.store'), ['phone' => $doctor->phone, 'password' => 'Mediway@123'])
            ->assertRedirect(route('doctor.home'));
        $this->get(route('doctor.visits.index'))->assertOk()->assertSee($own->visit_code)->assertSee($other->visit_code);
        $this->get(route('doctor.visits.show', $own->id))->assertOk()->assertSee($own->patient->full_name);
        $this->get(route('doctor.visits.show', $other->id))->assertOk();
    }

    public function test_non_doctors_and_inactive_doctors_cannot_access_visits(): void
    {
        $visit = MedicalVisit::where('visit_code', 'LK000001')->firstOrFail();
        $this->get(route('doctor.home'))->assertRedirect(route('patient.login'));
        foreach (['BN000001', 'NV000002', 'QT000001'] as $code) {
            $this->flushSession();
            $this->actingAs(User::where('business_code', $code)->firstOrFail())
                ->get(route('doctor.visits.show', $visit->id))->assertForbidden();
        }
        $doctor = User::where('business_code', 'BS000001')->firstOrFail();
        $doctor->doctor->update(['status' => 'INACTIVE']);
        $this->flushSession();
        $this->actingAs($doctor)->get(route('doctor.home'))->assertForbidden();
    }

    public function test_clinical_form_stays_open_and_keeps_input_after_validation(): void
    {
        $doctor = User::where('business_code', 'BS000001')->firstOrFail();
        $visit = MedicalVisit::where('visit_code', 'LK000004')->firstOrFail();

        // Giữ nội dung bác sĩ vừa nhập khi biểu mẫu cần sửa lỗi.
        $visit->update(['visit_status_id' => \App\Models\VisitStatus::where('name', 'IN_EXAMINATION')->value('id')]);
        $response = $this->actingAs($doctor)->withSession(['_old_input' => [
            'form_key' => 'clinical_note',
            'record_version' => 'test-version',
            'reason_for_visit' => 'Đau đầu từ hôm qua',
        ]])->get(route('doctor.visits.show', $visit->id));

        $response->assertOk()->assertSee('Đau đầu từ hôm qua');
        $this->assertMatchesRegularExpression('/<details class="doctor-visit__editor"\s+open\s*>/', $response->getContent());
    }

    public function test_diagnosis_summary_shows_saved_severity_and_notes(): void
    {
        $doctor = User::where('business_code', 'BS000001')->firstOrFail();
        $visit = MedicalVisit::where('visit_code', 'LK000001')->firstOrFail();
        $diagnosis = $visit->diagnoses()->firstOrFail();
        $diagnosis->update(['severity' => 'Mức độ nhẹ', 'notes' => 'Theo dõi triệu chứng']);

        // Nội dung phải xuất hiện cả khi hồ sơ hoàn tất và khi còn được phép sửa.
        foreach (['COMPLETED', 'IN_EXAMINATION'] as $status) {
            $visit->update(['visit_status_id' => \App\Models\VisitStatus::where('name', $status)->value('id')]);
            $this->actingAs($doctor)->get(route('doctor.visits.show', $visit->id))
                ->assertOk()->assertSee('Mức độ nhẹ')->assertSee('Theo dõi triệu chứng');
        }
    }

    public function test_conclusion_summary_shows_saved_fields_for_editable_and_completed_visits(): void
    {
        $doctor = User::where('business_code', 'BS000001')->firstOrFail();
        $visit = MedicalVisit::where('visit_code', 'LK000001')->firstOrFail();
        $visit->conclusion()->firstOrFail()->update([
            'disposition' => 'Điều trị ngoại trú', 'treatment_plan' => 'Theo dõi điều trị',
            'instructions' => 'Uống đủ nước', 'has_follow_up' => true,
            'follow_up_date' => '2026-12-01', 'follow_up_reason' => 'Kiểm tra lại', 'notes' => 'Ghi chú riêng',
        ]);

        foreach (['COMPLETED', 'IN_EXAMINATION'] as $status) {
            $visit->update(['visit_status_id' => \App\Models\VisitStatus::where('name', $status)->value('id')]);
            $response = $this->actingAs($doctor)->get(route('doctor.visits.show', $visit->id))->assertOk();
            $html = $response->getContent();
            preg_match('/<dl class="doctor-conclusion-summary">(.*?)<\/dl>/s', $html, $summary);
            $this->assertNotEmpty($summary);
            foreach (['Điều trị ngoại trú', 'Theo dõi điều trị', 'Uống đủ nước', '01/12/2026', 'Kiểm tra lại', 'Ghi chú riêng'] as $value) {
                $this->assertStringContainsString($value, $summary[1]);
            }
        }
    }
}
