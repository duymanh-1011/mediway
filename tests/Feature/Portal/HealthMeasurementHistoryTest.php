<?php

namespace Tests\Feature\Portal;

use App\Models\PersonalHealthRecord;
use App\Models\User;
use Tests\Fixtures\Seeders\WorkflowFixtureSeeder;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthMeasurementHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(WorkflowFixtureSeeder::class);
        $this->withoutVite();
    }

    public function test_patient_sees_only_their_measurements_in_closed_detail_panels(): void
    {
        $patient = User::where('business_code', 'BN000001')->firstOrFail();
        $other = User::where('business_code', 'BN000002')->firstOrFail();
        $own = $patient->patient->healthRecords()->latest('recorded_at')->firstOrFail();
        $otherRecord = $other->patient->healthRecords()->latest('recorded_at')->firstOrFail();
        $own->update(['blood_glucose' => 7.15, 'notes' => 'Ghi chú nội bộ riêng']);
        $otherRecord->update(['blood_glucose' => 17.35]);

        $response = $this->actingAs($patient)->get(route('patient.health-history'))->assertOk()
            ->assertSee('Lịch sử đo chỉ số sức khỏe')->assertSee('7.15')->assertSee('mmol/L')
            ->assertDontSee('17.35')->assertDontSee('Ghi chú nội bộ riêng')->assertDontSee('Lịch sử khám');
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent(), LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $xpath = new DOMXPath($document);
        $records = $xpath->query('//article[contains(concat(" ", normalize-space(@class), " "), " health-record ")]');
        $panels = $xpath->query('//article[contains(concat(" ", normalize-space(@class), " "), " health-record ")]//details[not(@open)]/summary');
        $this->assertCount(2, $records);
        $this->assertCount(2, $panels);
        $this->assertStringContainsString('Xem chỉ số chi tiết', $panels->item(0)->textContent);
        $this->assertFalse($response->viewData('healthRecords')->contains('id', $otherRecord->id));
    }

    public function test_measurements_are_paginated_newest_first_and_require_patient_authentication(): void
    {
        $this->get(route('patient.health-history'))->assertRedirect(route('patient.login'));
        $patient = User::where('business_code', 'BN000001')->firstOrFail();
        foreach (range(1, 12) as $day) {
            PersonalHealthRecord::create(['patient_id' => $patient->patient->id, 'patient_code' => $patient->patient->patient_code,
                'height_cm' => 160, 'recorded_at' => sprintf('2026-11-%02d 09:00:00', $day)]);
        }
        $response = $this->actingAs($patient)->get(route('patient.health-history', ['health_page' => 2]))->assertOk();
        $records = $response->viewData('healthRecords');
        $this->assertSame(14, $records->total());
        $this->assertSame(['2026-11-02', '2026-11-01', '2026-10-15', '2026-10-01'],
            $records->map(fn ($record) => $record->recorded_at->format('Y-m-d'))->all());
    }

    public function test_empty_history_has_a_clear_message(): void
    {
        $patient = User::where('business_code', 'BN000001')->firstOrFail();
        $patient->patient->healthRecords()->delete();
        $this->actingAs($patient)->get(route('patient.health-history'))->assertOk()->assertSee('Chưa có lần đo sức khỏe');
    }
}
