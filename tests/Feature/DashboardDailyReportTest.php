<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardDailyReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 22)->setTime(12, 0));
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail());
    }

    public function test_revenue_uses_confirmation_day_and_excludes_failed_and_next_day_transactions(): void
    {
        $successes = DB::table('payment_transactions')->where('transaction_result', 'SUCCESS')->orderBy('id')->limit(3)->pluck('id');
        DB::table('payment_transactions')->update(['confirmed_at' => '2026-10-14 12:00:00']);
        foreach ([['2026-10-15 00:00:00', 100000], ['2026-10-15 23:59:59', 200000], ['2026-10-16 00:00:00', 400000]] as $index => [$time, $amount]) {
            DB::table('payment_transactions')->where('id', $successes[$index])->update([
                'started_at' => '2026-10-14 12:00:00', 'confirmed_at' => $time, 'amount' => $amount,
            ]);
        }
        DB::table('payment_transactions')->where('transaction_result', 'FAILED')->update(['confirmed_at' => '2026-10-15 12:00:00', 'amount' => 900000]);
        $response = $this->get(route('admin.dashboard', ['date' => '2026-10-15']))->assertOk()
            ->assertViewHas('selectedDate', '2026-10-15')->assertViewHas('dailySuccessfulPayments', 2);
        $this->assertEquals(300000, $response->viewData('dailyRevenue'));
        $rows = $response->viewData('dailyRows');
        $this->assertCount(7, $rows);
        $this->assertSame('2026-10-09', $rows->first()['date']);
        $this->assertSame('2026-10-15', $rows->last()['date']);
        $this->assertEquals(300000, $rows->last()['revenue']);
        $this->assertSame(0, $rows->first()['appointments']);
        $response->assertSee('300.000 ₫')->assertSee('15/10/2026')->assertSee('Quy mô hồ sơ toàn hệ thống')
            ->assertDontSee('không phải bản chụp trạng thái')->assertDontSee('Ngày không phát sinh hoạt động vẫn hiển thị số 0.');
    }

    public function test_reports_distinguish_services_ordered_today_from_services_finished_today(): void
    {
        $completed = DB::table('service_order_items')->join('service_statuses', 'service_statuses.id', '=', 'service_order_items.service_status_id')
            ->where('service_statuses.name', 'COMPLETED')->limit(2)->pluck('service_order_items.id');
        $this->assertCount(2, $completed);
        DB::table('service_order_items')->whereIn('id', $completed)->update(['completed_at' => '2026-10-22 10:00:00']);
        $response = $this->get(route('admin.dashboard', ['date' => '2026-10-22']))->assertOk();
        $this->assertSame(2, $response->viewData('dailyCompletedServices'));
        $this->assertSame(0, $response->viewData('dailyServices'));
        $this->assertSame(2, $response->viewData('dailyRows')->last()['completed_services']);
    }

    public function test_default_date_empty_days_and_invalid_filters_are_handled_explicitly(): void
    {
        $this->get(route('admin.dashboard'))->assertOk()->assertViewHas('selectedDate', '2026-10-22');
        $response = $this->get(route('admin.dashboard', ['date' => '2026-11-01']))->assertOk();
        $this->assertCount(7, $response->viewData('dailyRows'));
        $this->assertEquals(0, $response->viewData('dailyRevenue'));
        $this->assertSame(0, $response->viewData('dailyAppointments'));
        $response->assertSee('Chưa có hoạt động trong ngày đã chọn.');
        $this->getJson(route('admin.dashboard', ['date' => 'not-a-date']))->assertUnprocessable()->assertJsonValidationErrors('date');
        $this->getJson(route('admin.dashboard', ['date' => ['invalid']]))->assertUnprocessable()->assertJsonValidationErrors('date');
    }
}
