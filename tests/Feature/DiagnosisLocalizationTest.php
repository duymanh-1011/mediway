<?php

namespace Tests\Feature;

use App\Models\Disease;
use App\Models\MedicalVisit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiagnosisLocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_vietnamese_severity_saves_the_existing_code_and_remains_readable(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $admin = User::where('business_code', 'QT000001')->firstOrFail();
        $visit = MedicalVisit::where('visit_code', 'LK000005')->firstOrFail();
        $disease = Disease::where('status', 'ACTIVE')->firstOrFail();
        $this->actingAs($admin)->get(route('admin.visits.diagnoses.create', $visit))
            ->assertOk()->assertSee('Trung bình')->assertSee('Nhẹ')->assertSee('Nặng');
        $this->post(route('admin.visits.diagnoses.store', $visit), [
            'disease_id' => $disease->id, 'diagnosis_type' => 'SECONDARY', 'severity' => 'Trung bình',
        ])->assertSessionHasNoErrors();
        $diagnosis = $visit->diagnoses()->where('disease_id', $disease->id)->where('diagnosis_type', 'SECONDARY')->firstOrFail();
        $this->assertSame('MODERATE', $diagnosis->severity);
        $this->get(route('admin.diagnoses.edit', $diagnosis))->assertOk()->assertSee('Trung bình')->assertSee('Chẩn đoán phụ');
    }
}
