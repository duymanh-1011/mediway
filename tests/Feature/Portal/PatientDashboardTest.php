<?php

namespace Tests\Feature\Portal;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PatientDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
    }

    public function test_access_is_restricted_and_missing_patient_is_safe(): void
    {
        $this->get(route('patient.home'))->assertRedirect(route('patient.login'));
        $this->get(route('patient.appointments'))->assertRedirect(route('patient.login'));
        foreach (['Administrator', 'Doctor', 'Employee'] as $role) {
            $user = User::whereHas('role', fn ($q) => $q->where('name', $role))->firstOrFail();
            $this->actingAs($user)->get(route('patient.home'))->assertRedirect(route('patient.login'));
            $this->actingAs($user)->get(route('patient.appointments'))->assertRedirect(route('patient.login'));
        }
        $user = User::where('business_code', 'BN000001')->firstOrFail();
        DB::table('patients')->where('user_id', $user->id)->update(['user_id' => null]);
        $this->actingAs($user->fresh())->get(route('patient.home'))->assertRedirect(route('patient.login'));
    }

    public function test_appointments_are_scoped_cancelled_is_excluded_and_visits_are_not_shown(): void
    {
        $a = User::where('business_code', 'BN000001')->firstOrFail();
        $b = User::where('business_code', 'BN000002')->firstOrFail();
        $booked = DB::table('appointment_statuses')->where('name', 'BOOKED')->value('id');
        DB::table('appointments')->where('appointment_code', 'LH000001')->update(['appointment_date' => today()->addDays(2), 'appointment_status_id' => $booked]);
        DB::table('appointments')->where('appointment_code', 'LH000006')->update(['appointment_date' => today()->addDay()]);
        DB::table('appointments')->where('appointment_code', 'LH000002')->update(['appointment_date' => today()->addDay(), 'appointment_status_id' => $booked]);
        DB::table('medical_visits')->where('visit_code', 'LK000001')->update(['visit_status_id' => DB::table('visit_statuses')->where('name', 'IN_EXAMINATION')->value('id')]);
        DB::table('examination_room_queues')->where('medical_visit_id', DB::table('medical_visits')->where('visit_code', 'LK000001')->value('id'))->delete();
        $otherPayable = DB::table('payables')->join('medical_visits', 'medical_visits.id', '=', 'payables.medical_visit_id')->where('medical_visits.patient_id', $b->patient->id)->value('payables.payable_code');
        $this->assertNotNull($otherPayable);
        $this->actingAs($a)->get(route('patient.home'))->assertOk()->assertSee(route('patient.appointments'))
            ->assertDontSee('LH000001')->assertDontSee('LK000001');
        $this->actingAs($a)->get(route('patient.appointments', ['patient_id' => $b->patient->id]))
            ->assertOk()->assertSee('LH000001')->assertDontSee('LK000001')->assertDontSee('Lượt khám hiện tại')
            ->assertDontSee('Thông tin hàng đợi đang được cập nhật')->assertDontSee('LH000006')->assertDontSee('LH000002')
            ->assertDontSee('LK000002')->assertDontSee($otherPayable)->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_empty_states_and_cards_have_no_dead_links(): void
    {
        $user = User::where('business_code', 'BN000001')->firstOrFail();
        DB::table('patients')->where('id', $user->patient->id)->update(['date_of_birth' => null, 'gender' => null]);
        $response = $this->actingAs($user->fresh())->get(route('patient.home'));
        $response->assertOk()->assertSee('hoàn thiện thông tin hồ sơ')->assertSee('Thông tin khoản cần thanh toán đang được cập nhật')
            ->assertDontSee('href="#"', false)->assertSee(route('patient.notifications'))->assertSee(route('public.support'));
        $this->get(route('patient.appointments'))->assertOk()->assertSee('Bạn chưa có lịch khám sắp tới')->assertDontSee('Lượt khám hiện tại');
        foreach (['Lịch khám', 'Hồ sơ cá nhân', 'Hồ sơ sức khỏe', 'Kết quả khám bệnh', 'Đơn thuốc', 'Thanh toán'] as $card) {
            $response->assertSee($card);
        }
    }

    public function test_appointment_disappears_after_its_slot_end_when_the_page_is_requested_again(): void
    {
        $user = User::where('business_code', 'BN000001')->firstOrFail();
        $appointment = DB::table('appointments')->where('appointment_code', 'LH000001')->first();
        $slot = DB::table('appointment_time_slots')->where('id', $appointment->appointment_time_slot_id)->first();
        $date = '2026-10-15';
        DB::table('appointments')->where('id', $appointment->id)->update([
            'appointment_date' => $date,
            'appointment_status_id' => DB::table('appointment_statuses')->where('name', 'BOOKED')->value('id'),
        ]);

        $this->travelTo(\Carbon\Carbon::parse($date.' '.$slot->end_time));
        $this->actingAs($user)->get(route('patient.appointments'))->assertOk()->assertSee('LH000001');
        $this->travel(1)->seconds();
        $this->get(route('patient.appointments'))->assertOk()->assertDontSee('LH000001')->assertSee('Bạn chưa có lịch khám sắp tới');
        $this->assertSame('BOOKED', DB::table('appointments')->join('appointment_statuses', 'appointment_statuses.id', '=', 'appointments.appointment_status_id')->where('appointments.id', $appointment->id)->value('appointment_statuses.name'));
    }

    public function test_admin_route_and_query_count(): void
    {
        $admin = User::where('business_code', 'QT000001')->firstOrFail();
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $user = User::where('business_code', 'BN000001')->firstOrFail();
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($user)->get(route('patient.home'))->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();
        $this->assertLessThanOrEqual(15, $count);
    }
}
