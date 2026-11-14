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
        $this->withSession(['doctor_workspace_mode' => 'examination']);
        $this->actingAs(User::where('business_code', 'BS000001')->firstOrFail());
    }

    public function test_search_finds_visits_for_all_doctors(): void
    {
        $own = MedicalVisit::where('visit_code', 'LK000001')->firstOrFail();
        $other = MedicalVisit::where('visit_code', 'LK000002')->firstOrFail();
        foreach ([$own->visit_code, $own->patient->patient_code, $own->patient->full_name] as $term) {
            $this->get(route('doctor.visits.index', ['q' => ' '.$term.' ']))->assertOk()->assertSee($own->visit_code)->assertDontSee($other->visit_code);
        }
        $this->get(route('doctor.visits.index', ['q' => $other->patient->patient_code]))->assertOk()->assertSee($other->visit_code);
        $this->get(route('doctor.visits.show', $other->id))->assertOk();
    }

    public function test_dates_status_and_combined_filters_are_applied(): void
    {
        $visit = MedicalVisit::where('visit_code', 'LK000001')->firstOrFail();
        $date = $visit->checked_in_at->format('Y-m-d');
        foreach ([['date_from' => $date], ['date_to' => $date], ['date_from' => $date, 'date_to' => $date, 'status' => $visit->visit_status_id, 'q' => $visit->visit_code]] as $filters) {
            $this->get(route('doctor.visits.index', $filters))->assertOk()->assertSee($visit->visit_code);
        }
        $this->get(route('doctor.visits.index', ['date_from' => '2099-01-01']))->assertOk()->assertSee('Không tìm thấy lượt khám phù hợp bộ lọc.');
        $this->get(route('doctor.visits.index', ['date_from' => '2026-10-20', 'date_to' => '2026-10-10']))
            ->assertRedirect(route('doctor.visits.index'))->assertSessionHasErrors('date_to')->assertSessionHasInput('date_from', '2026-10-20');
        $this->get(route('doctor.visits.index', ['q' => ['bad']]))->assertRedirect(route('doctor.visits.index'))->assertSessionHasErrors('q');
    }

    public function test_status_colors_match_in_list_and_detail_and_inactive_status_remains_filterable(): void
    {
        $visit = MedicalVisit::where('visit_code', 'LK000001')->firstOrFail();
        foreach (['WAITING', 'IN_EXAMINATION', 'WAITING_FOR_SERVICE', 'RETURNING', 'COMPLETED'] as $name) {
            $status = VisitStatus::where('name', $name)->firstOrFail();
            $visit->update(['visit_status_id' => $status->id]);
            // Bộ lọc vẫn dựa trên trạng thái lưu, nhãn hiển thị theo tiến độ như Admin.
            $code = $visit->fresh()->displayStatus();
            $this->get(route('doctor.visits.index', ['status' => $status->id]))->assertOk()->assertSee($visit->visit_code)->assertSee('data-visit-status="'.$code.'"', false);
            $this->get(route('doctor.visits.show', $visit->id))->assertOk()->assertSee('data-visit-status="'.$code.'"', false)
                ->assertSee(\App\Models\AdminDisplay::label($code));
        }
        $custom = VisitStatus::create(['name' => 'CUSTOM_TEST', 'description' => 'Trạng thái khác', 'process_order' => 10, 'allows_transition' => false, 'is_active' => false]);
        $visit->update(['visit_status_id' => $custom->id]);
        $this->get(route('doctor.visits.index', ['status' => $custom->id]))->assertOk()->assertSee('Trạng thái khác')->assertSee('doctor-visit-status--neutral');
        $this->get(route('doctor.visits.show', $visit->id))->assertOk()->assertSee('doctor-visit-status--neutral');
    }

    public function test_derived_statuses_use_the_same_labels_as_admin_without_changing_the_saved_status(): void
    {
        $visit = MedicalVisit::where('visit_code', 'LK000001')->firstOrFail();
        $cases = [
            ['WAITING', true, false, false, false, 'WAITING_FOR_PAYMENT'],
            ['WAITING', false, false, false, false, 'WAITING'],
            ['WAITING_FOR_SERVICE', false, true, true, false, 'WAITING_FOR_SERVICE'],
            ['WAITING_FOR_SERVICE', false, true, false, true, 'WAITING_FOR_RETURN'],
            ['WAITING_FOR_SERVICE', false, true, false, false, 'SERVICES_RESOLVED'],
        ];
        foreach ($cases as [$saved, $unpaid, $items, $unresolved, $queued, $expected]) {
            // Giả lập các giá trị đã nạp, không ghi trạng thái tính toán vào cơ sở dữ liệu.
            $visit->setRelation('status', VisitStatus::where('name', $saved)->firstOrFail());
            $visit->setAttribute('has_unpaid_examination_fee', $unpaid);
            $visit->setAttribute('has_service_items', $items);
            $visit->setAttribute('has_unresolved_service_items', $unresolved);
            $visit->setAttribute('has_return_queue', $queued);
            $this->assertSame($expected, $visit->displayStatus());
            $html = view('doctor.visits._status', ['visit' => $visit])->render();
            $this->assertStringContainsString('data-visit-status="'.$expected.'"', $html);
            $this->assertStringContainsString(\App\Models\AdminDisplay::label($expected), $html);
            $this->assertSame($saved, $visit->status->name);
        }
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
        $this->get(route('doctor.visits.index', ['q' => 'TEST-FILTER']))->assertOk()
            ->assertSee('q=TEST-FILTER', false)->assertSee('page=2', false)
            ->assertSee('href="'.route('doctor.visits.index').'"', false);
        $this->get(route('doctor.visits.index', ['q' => 'TEST-FILTER', 'page' => 2]))->assertOk()->assertSee('TEST-FILTER-');
    }

    public function test_missing_status_uses_a_neutral_label(): void
    {
        $html = view('doctor.visits._status', ['status' => null])->render();
        $this->assertStringContainsString('doctor-visit-status--neutral', $html);
        $this->assertStringContainsString('Chưa cập nhật trạng thái', $html);
    }
}
