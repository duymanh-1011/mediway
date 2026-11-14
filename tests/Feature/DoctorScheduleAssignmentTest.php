<?php

namespace Tests\Feature;

use App\Models\DoctorSchedule;
use App\Models\User;
use Tests\Fixtures\Seeders\WorkflowFixtureSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DoctorScheduleAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(WorkflowFixtureSeeder::class);
        $this->withoutVite();
    }

    public function test_standard_days_off_can_exist_without_assignment_and_have_no_available_shift(): void
    {
        $working = DoctorSchedule::create($this->shiftData('MORNING'));
        $dayOff = DoctorSchedule::create([
            ...$this->shiftData('AFTERNOON'), 'status' => 'INACTIVE', 'specialty_id' => null, 'examination_room_id' => null,
        ]);

        $this->assertTrue($working->isStandardShift());
        $this->assertTrue($dayOff->isStandardShift());
        $this->assertNull($dayOff->specialty);
        $this->assertNull($dayOff->examinationRoom);
        $this->assertFalse($dayOff->appointmentTimeSlots()->exists());
        $this->assertSame([$working->id], DoctorSchedule::effectiveActive()->whereKey([$working->id, $dayOff->id])->pluck('id')->all());

        $this->travelTo($working->work_date->setTime(12, 0), function () use ($working): void {
            $this->assertSame('INACTIVE', $working->effectiveStatus());
            $this->assertSame('ACTIVE', $working->fresh()->status);
        });
    }

    public function test_database_preserves_assignment_requirements_for_working_and_legacy_shifts(): void
    {
        $data = $this->shiftData('MORNING');
        $invalid = [
            ['specialty_id' => null, 'examination_room_id' => null],
            ['shift_code' => null, 'status' => 'INACTIVE', 'specialty_id' => null, 'examination_room_id' => null],
            ['status' => 'INACTIVE', 'specialty_id' => null],
            ['status' => 'UNKNOWN'],
        ];
        foreach ($invalid as $overrides) {
            try {
                DoctorSchedule::create([...$data, ...$overrides]);
                $this->fail('The database accepted an invalid schedule assignment.');
            } catch (QueryException $error) {
                $this->assertStringContainsString('doctor_schedules_shift_assignment_check', $error->getMessage());
            }
        }

        $working = DoctorSchedule::create($data);
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('doctor_schedules_shift_assignment_check');
        DB::table('doctor_schedules')->where('id', $working->id)->update(['examination_room_id' => null]);
    }

    public function test_old_create_submission_redirects_without_creating_a_daily_schedule(): void
    {
        $this->signInAsAdmin();
        $data = $this->shiftData('MORNING');
        DoctorSchedule::create([...$data, 'status' => 'INACTIVE', 'specialty_id' => null, 'examination_room_id' => null]);
        $fields = Arr::except($data, ['shift_code', 'start_time', 'end_time']);

        $this->post(route('admin.doctor-schedules.store'), [...$fields, 'start_time' => '11:30', 'end_time' => '12:00'])
            ->assertStatus(303)->assertRedirect(route('admin.doctor-schedules.month.index'))->assertSessionHas('error');

        $this->assertSame(1, DoctorSchedule::where('doctor_id', $data['doctor_id'])->whereDate('work_date', $data['work_date'])->count());
    }

    public function test_manual_routes_cannot_edit_delete_or_add_slots_to_standard_shifts(): void
    {
        $this->signInAsAdmin();
        $data = $this->shiftData('MORNING');
        $schedule = DoctorSchedule::create($data);
        $update = [...Arr::except($data, 'shift_code'), 'start_time' => '08:00', 'end_time' => '11:00'];

        $this->get(route('admin.doctor-schedules.show', $schedule))->assertOk();
        $this->get(route('admin.doctor-schedules.edit', $schedule))->assertStatus(409);
        $this->put(route('admin.doctor-schedules.update', $schedule), $update)->assertStatus(409);
        $this->delete(route('admin.doctor-schedules.destroy', $schedule))->assertStatus(409);
        $this->post(route('admin.doctor-schedules.time-slots.store', $schedule), [
            'start_time' => '08:00', 'end_time' => '08:30', 'max_patients' => 3, 'status' => 'ACTIVE',
        ])->assertStatus(409);

        $this->assertSame($data['start_time'], $schedule->fresh()->start_time);
        $this->assertFalse($schedule->appointmentTimeSlots()->exists());
    }

    private function signInAsAdmin(): void
    {
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail());
    }

    private function shiftData(string $code): array
    {
        $legacy = DoctorSchedule::orderBy('id')->firstOrFail();
        $hours = config('doctor_schedules.shifts.'.$code);

        return [
            'doctor_id' => $legacy->doctor_id,
            'specialty_id' => $legacy->specialty_id,
            'examination_room_id' => $legacy->examination_room_id,
            'work_date' => today()->addDays(50)->toDateString(),
            'start_time' => $hours['start'],
            'end_time' => $hours['end'],
            'shift_code' => $code,
            'status' => 'ACTIVE',
        ];
    }
}
