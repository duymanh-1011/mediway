<?php

namespace Tests\Feature;

use App\Models\MedicalVisit;
use App\Models\User;
use App\Models\VisitStatus;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorVisitFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $this->actingAs(User::where('business_code', 'BS000001')->firstOrFail());
    }

    public function test_search_finds_assigned_visits_without_leaking_other_doctors(): void
    {
        $own = MedicalVisit::where('visit_code', 'LK000001')->firstOrFail();
        $other = MedicalVisit::where('visit_code', 'LK000002')->firstOrFail();
        foreach ([$own->visit_code, $own->patient->patient_code, $own->patient->full_name] as $term) {
            $this->get(route('doctor.home', ['q' => ' '.$term.' ']))->assertOk()->assertSee($own->visit_code)->assertDontSee($other->visit_code);
        }
        $this->get(route('doctor.home', ['q' => $other->patient->patient_code]))->assertOk()->assertDontSee($other->visit_code);
        $this->get(route('doctor.visits.show', $other->id))->assertNotFound();
    }

    public function test_dates_status_and_combined_filters_are_applied(): void
    {
        $visit = MedicalVisit::where('visit_code', 'LK000001')->firstOrFail();
        $date = $visit->checked_in_at->format('Y-m-d');
        foreach ([['date_from' => $date], ['date_to' => $date], ['date_from' => $date, 'date_to' => $date, 'status' => $visit->visit_status_id, 'q' => $visit->visit_code]] as $filters) {
            $this->get(route('doctor.home', $filters))->assertOk()->assertSee($visit->visit_code);
        }
        $this->get(route('doctor.home', ['date_from' => '2099-01-01']))->assertOk()->assertSee('Không tìm thấy lượt khám phù hợp bộ lọc.');
        $this->get(route('doctor.home', ['date_from' => '2026-10-20', 'date_to' => '2026-10-10']))
            ->assertRedirect(route('doctor.home'))->assertSessionHasErrors('date_to')->assertSessionHasInput('date_from', '2026-10-20');
        $this->get(route('doctor.home', ['q' => ['bad']]))->assertRedirect(route('doctor.home'))->assertSessionHasErrors('q');
    }

    public function test_status_colors_match_in_list_and_detail_and_inactive_status_remains_filterable(): void
    {
        $visit = MedicalVisit::where('visit_code', 'LK000001')->firstOrFail();
        foreach (['WAITING' => 'waiting', 'IN_EXAMINATION' => 'examining', 'WAITING_FOR_SERVICE' => 'service', 'RETURNING' => 'returning', 'COMPLETED' => 'completed'] as $name => $tone) {
            $status = VisitStatus::where('name', $name)->firstOrFail();
            $visit->update(['visit_status_id' => $status->id]);
            $this->get(route('doctor.home', ['status' => $status->id]))->assertOk()->assertSee($visit->visit_code)->assertSee('doctor-visit-status--'.$tone);
            $this->get(route('doctor.visits.show', $visit->id))->assertOk()->assertSee('doctor-visit-status--'.$tone);
        }
        $custom = VisitStatus::create(['name' => 'CUSTOM_TEST', 'description' => 'Trạng thái khác', 'process_order' => 10, 'allows_transition' => false, 'is_active' => false]);
        $visit->update(['visit_status_id' => $custom->id]);
        $this->get(route('doctor.home', ['status' => $custom->id]))->assertOk()->assertSee('Trạng thái khác')->assertSee('doctor-visit-status--neutral');
        $this->get(route('doctor.visits.show', $visit->id))->assertOk()->assertSee('doctor-visit-status--neutral');
    }

    public function test_filters_are_kept_when_paginating_and_clear_link_resets_them(): void
    {
        $visit = MedicalVisit::where('visit_code', 'LK000001')->firstOrFail();
        for ($i = 0; $i < 11; $i++) {
            $copy = $visit->replicate();
            $copy->visit_code = 'TEST-FILTER-'.$i;
            $copy->appointment_id = null;
            $copy->save();
        }
        $this->get(route('doctor.home', ['q' => 'TEST-FILTER']))->assertOk()
            ->assertSee('q=TEST-FILTER', false)->assertSee('page=2', false)
            ->assertSee('href="'.route('doctor.home').'"', false);
        $this->get(route('doctor.home', ['q' => 'TEST-FILTER', 'page' => 2]))->assertOk()->assertSee('TEST-FILTER-');
    }

    public function test_missing_status_uses_a_neutral_label(): void
    {
        $html = view('doctor.visits._status', ['status' => null])->render();
        $this->assertStringContainsString('doctor-visit-status--neutral', $html);
        $this->assertStringContainsString('Chưa cập nhật trạng thái', $html);
    }
}
