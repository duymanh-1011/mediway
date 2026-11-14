<?php

namespace Tests\Feature;

use App\Models\Disease;
use App\Models\MedicalVisit;
use App\Models\User;
use App\Models\VisitStatus;
use App\Services\DoctorClinicalVersion;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorClinicalWriteTest extends TestCase
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

    private function activeVisit(): MedicalVisit
    {
        $visit = MedicalVisit::where('visit_code', 'LK000004')->firstOrFail();
        $visit->update(['visit_status_id' => VisitStatus::where('name', 'IN_EXAMINATION')->value('id')]);
        return $visit->fresh();
    }

    public function test_assigned_doctor_saves_note_and_cannot_overwrite_newer_data(): void
    {
        $visit = $this->activeVisit();
        $version = DoctorClinicalVersion::of($visit->clinicalNote);
        $url = route('doctor.visits.clinical.update', $visit->id);
        $this->put($url, ['record_version' => $version, 'reason_for_visit' => 'Đau đầu kiểm thử'])
            ->assertSessionHasNoErrors();
        $this->assertSame('Đau đầu kiểm thử', $visit->fresh()->clinicalNote->reason_for_visit);
        $this->get(route('doctor.visits.show', $visit))->assertOk()->assertSee('Đau đầu kiểm thử');
        $this->put($url, ['record_version' => $version, 'reason_for_visit' => 'Dữ liệu cũ'])
            ->assertSessionHasErrors('record_version');
        $this->assertSame('Đau đầu kiểm thử', $visit->fresh()->clinicalNote->reason_for_visit);
    }

    public function test_any_doctor_can_write_open_visit_and_closed_visit_is_read_only(): void
    {
        $visit = $this->activeVisit();
        $foreign = MedicalVisit::where('visit_code', 'LK000005')->firstOrFail();
        $payload = ['record_version' => DoctorClinicalVersion::of($foreign->clinicalNote), 'reason_for_visit' => 'Không được phép'];
        $this->put(route('doctor.visits.clinical.update', $foreign->id), $payload)->assertSessionHasNoErrors();
        $this->assertSame('Không được phép', $foreign->fresh()->clinicalNote->reason_for_visit);
        $visit->update(['visit_status_id' => VisitStatus::where('name', 'COMPLETED')->value('id')]);
        $payload['record_version'] = DoctorClinicalVersion::of($visit->clinicalNote);
        $this->put(route('doctor.visits.clinical.update', $visit->id), $payload)->assertSessionHasErrors('visit');
        $this->get(route('doctor.visits.show', $visit->id))->assertOk()->assertDontSee('Lưu nội dung khám');
    }

    public function test_diagnosis_uses_real_catalog_rejects_duplicates_and_supports_safe_edit(): void
    {
        $visit = $this->activeVisit();
        $disease = Disease::where('status', 'ACTIVE')->whereNotIn('id', $visit->diagnoses()->pluck('disease_id'))->firstOrFail();
        $payload = ['disease_id' => $disease->id, 'diagnosis_type' => 'PRIMARY', 'diagnosis_description' => 'Mô tả kiểm thử'];
        $url = route('doctor.visits.diagnoses.store', $visit->id);
        $this->post($url, [...$payload, 'disease_id' => 999999])->assertSessionHasErrors('disease_id');
        $this->post($url, $payload)->assertSessionHasNoErrors();
        $item = $visit->diagnoses()->where('disease_id', $disease->id)->firstOrFail();
        $this->post($url, $payload)->assertSessionHasErrors('diagnosis_type');
        $version = DoctorClinicalVersion::of($item);
        $edit = route('doctor.visits.diagnoses.update', [$visit->id, $item->id]);
        $this->put($edit, [...$payload, 'record_version' => $version, 'diagnosis_description' => 'Đã cập nhật'])->assertSessionHasNoErrors();
        $this->assertSame('Đã cập nhật', $item->fresh()->diagnosis_description);
        $this->put($edit, [...$payload, 'record_version' => $version])->assertSessionHasErrors('record_version');
        $this->assertSame('Đã cập nhật', $item->fresh()->diagnosis_description);
        $otherItem = MedicalVisit::where('visit_code', 'LK000002')->firstOrFail()->diagnoses()->firstOrFail();
        $this->put(route('doctor.visits.diagnoses.update', [$visit->id, $otherItem->id]), [...$payload, 'record_version' => DoctorClinicalVersion::of($otherItem)])->assertNotFound();
    }
}
