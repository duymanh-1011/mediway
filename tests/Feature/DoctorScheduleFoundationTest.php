<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\CarbonImmutable;
use Tests\Fixtures\Seeders\WorkflowFixtureSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DoctorScheduleFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrative_shifts_have_the_required_boundaries_and_fit_half_hour_slots(): void
    {
        $shifts = config('doctor_schedules.shifts');
        $this->assertSame([
            'MORNING' => ['start' => '07:30:00', 'end' => '11:30:00'],
            'AFTERNOON' => ['start' => '13:30:00', 'end' => '17:00:00'],
        ], $shifts);
        $this->assertSame(30, config('doctor_schedules.slot_minutes'));
        $this->assertSame('Asia/Ho_Chi_Minh', config('app.timezone'));

        $date = CarbonImmutable::parse('2026-11-30', config('app.timezone'));
        $morningEnds = $date->setTimeFromTimeString($shifts['MORNING']['end']);
        $afternoonStarts = $date->setTimeFromTimeString($shifts['AFTERNOON']['start']);
        $this->assertTrue($morningEnds->isBefore($afternoonStarts));
        $this->assertSame('2026-11-30T06:30:00+00:00', $afternoonStarts->utc()->toIso8601String());

        foreach (['MORNING' => 8, 'AFTERNOON' => 7] as $code => $expectedSlots) {
            $start = $date->setTimeFromTimeString($shifts[$code]['start']);
            $end = $date->setTimeFromTimeString($shifts[$code]['end']);
            $this->assertTrue($start->addMinutes($expectedSlots * config('doctor_schedules.slot_minutes'))->equalTo($end));
        }
    }

    public function test_shift_key_allows_two_legacy_rows_but_rejects_a_duplicate_standard_shift_even_if_inactive(): void
    {
        $this->seed(WorkflowFixtureSeeder::class);
        $data = $this->scheduleData();
        DB::table('doctor_schedules')->insert([
            $data,
            [...$data, 'start_time' => '17:00:00', 'end_time' => '18:00:00'],
            [...$data, 'start_time' => '13:30:00', 'end_time' => '17:00:00', 'shift_code' => 'AFTERNOON'],
        ]);
        $this->assertSame(2, DB::table('doctor_schedules')
            ->where('doctor_id', $data['doctor_id'])->where('work_date', $data['work_date'])->whereNull('shift_code')->count());

        $this->expectException(QueryException::class);
        $this->expectExceptionMessageMatches('/Duplicate entry|UNIQUE constraint failed/');
        DB::table('doctor_schedules')->insert([
            ...$data, 'start_time' => '13:30:00', 'end_time' => '17:00:00', 'shift_code' => 'AFTERNOON', 'status' => 'INACTIVE',
        ]);
    }

    public function test_database_rejects_unknown_shift_codes(): void
    {
        $this->seed(WorkflowFixtureSeeder::class);

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('shift_code');
        DB::table('doctor_schedules')->insert([...$this->scheduleData(), 'shift_code' => 'EVENING']);
    }

    public function test_admin_time_overlap_check_includes_legacy_rows_and_does_not_move_them(): void
    {
        $this->seed(WorkflowFixtureSeeder::class);
        $this->withoutVite();
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail());
        $data = $this->scheduleData();
        $oldId = DB::table('doctor_schedules')->insertGetId($data);
        $route = route('admin.doctor-schedules.month.update', $data['doctor_id']);
        $fields = ['date' => $data['work_date'], 'state' => 'WORKING', 'specialty_id' => $data['specialty_id'],
            'room_id' => $data['examination_room_id']];

        $this->put($route, [...$fields, 'shift' => 'MORNING'])->assertSessionHasErrors('shift');
        $this->assertDatabaseHas('doctor_schedules', [
            'id' => $oldId, 'shift_code' => null, 'start_time' => '08:00:00', 'end_time' => '11:00:00',
        ]);

        $this->put($route, [...$fields, 'shift' => 'AFTERNOON'])->assertSessionHasNoErrors();
        $this->assertSame(2, DB::table('doctor_schedules')->where('doctor_id', $data['doctor_id'])->whereDate('work_date', $data['work_date'])->count());
    }

    private function scheduleData(): array
    {
        $legacy = DB::table('doctor_schedules')->orderBy('id')->first();

        return [
            'doctor_id' => $legacy->doctor_id,
            'specialty_id' => $legacy->specialty_id,
            'examination_room_id' => $legacy->examination_room_id,
            'work_date' => today()->addDays(50)->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '11:00:00',
            'status' => 'ACTIVE',
            'shift_code' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
