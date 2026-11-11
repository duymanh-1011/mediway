<?php

namespace Tests\Feature;

use App\Models\DoctorSchedule;
use App\Services\DoctorShiftService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use App\Models\User;

class DoctorShiftServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(DatabaseSeeder::class)->setContainer($this->app)->__invoke(['includePracticeData' => false]);
    }

    public function test_standard_shifts_generate_consecutive_unlimited_half_hour_slots(): void
    {
        $service = app(DoctorShiftService::class);
        $existing = DoctorSchedule::query()->firstOrFail();
        foreach (['MORNING' => 9, 'AFTERNOON' => 6] as $shift => $expectedCount) {
            [$start, $end] = $service->times($shift);
            $schedule = DoctorSchedule::create([
                'doctor_id' => $existing->doctor_id, 'specialty_id' => $existing->specialty_id,
                'examination_room_id' => $existing->examination_room_id, 'work_date' => today()->addDays(7),
                'start_time' => $start, 'end_time' => $end, 'status' => 'ACTIVE',
            ]);
            $this->assertSame($expectedCount, $service->createSlots($schedule->fresh()));
            $slots = $schedule->appointmentTimeSlots()->orderBy('start_time')->get();
            $this->assertCount($expectedCount, $slots);
            $this->assertSame($start, substr($slots->first()->start_time, 0, 5));
            $this->assertSame($end, substr($slots->last()->end_time, 0, 5));
            foreach ($slots as $index => $slot) {
                $this->assertNull($slot->max_patients);
                if ($index > 0) $this->assertSame($slots[$index - 1]->end_time, $slot->start_time);
            }
        }
    }

    public function test_unknown_shift_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        app(DoctorShiftService::class)->times('EVENING');
    }

    public function test_admin_creates_shift_and_server_ignores_submitted_time(): void
    {
        $existing = DoctorSchedule::firstOrFail();
        $date = today()->addDays(18)->toDateString();
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail())
            ->post(route('admin.doctor-schedules.store'), [
                'doctor_id' => $existing->doctor_id, 'specialty_id' => $existing->specialty_id,
                'examination_room_id' => $existing->examination_room_id, 'work_date' => $date,
                'shift' => 'MORNING', 'start_time' => '00:00', 'end_time' => '23:59', 'status' => 'ACTIVE',
            ])->assertSessionHasNoErrors();
        $schedule = DoctorSchedule::whereDate('work_date', $date)->firstOrFail();
        $this->assertSame('07:00', substr($schedule->start_time, 0, 5));
        $this->assertSame('11:30', substr($schedule->end_time, 0, 5));
        $this->assertSame(9, $schedule->appointmentTimeSlots()->count());
    }

    public function test_admin_can_change_unbooked_shift_but_not_shift_with_appointments(): void
    {
        $existing = DoctorSchedule::firstOrFail();
        $date = today()->addDays(19)->toDateString();
        $data = [
            'doctor_id' => $existing->doctor_id, 'specialty_id' => $existing->specialty_id,
            'examination_room_id' => $existing->examination_room_id, 'work_date' => $date,
            'shift' => 'MORNING', 'status' => 'ACTIVE',
        ];
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail());
        $this->post(route('admin.doctor-schedules.store'), $data)->assertSessionHasNoErrors();
        $schedule = DoctorSchedule::whereDate('work_date', $date)->firstOrFail();
        $this->put(route('admin.doctor-schedules.update', $schedule), [...$data, 'shift' => 'AFTERNOON'])->assertSessionHasNoErrors();
        $this->assertSame('13:30', substr($schedule->fresh()->start_time, 0, 5));
        $this->assertSame(6, $schedule->appointmentTimeSlots()->count());

        $appointment = \Illuminate\Support\Facades\DB::table('appointments')->first();
        $this->assertNotNull($appointment);
        \Illuminate\Support\Facades\DB::table('appointments')->where('id', $appointment->id)->update(['appointment_time_slot_id' => $schedule->appointmentTimeSlots()->firstOrFail()->id]);
        $this->put(route('admin.doctor-schedules.update', $schedule), $data)->assertSessionHasErrors('schedule_conflict');
        $this->assertSame('13:30', substr($schedule->fresh()->start_time, 0, 5));
        $this->assertSame(6, $schedule->appointmentTimeSlots()->count());
    }

    public function test_inactive_shift_can_be_activated_without_losing_its_slots(): void
    {
        $existing = DoctorSchedule::firstOrFail();
        $data = [
            'doctor_id' => $existing->doctor_id, 'specialty_id' => $existing->specialty_id,
            'examination_room_id' => $existing->examination_room_id,
            'work_date' => today()->addDays(21)->toDateString(), 'shift' => 'MORNING', 'status' => 'INACTIVE',
        ];
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail());
        $this->post(route('admin.doctor-schedules.store'), $data)->assertSessionHasNoErrors();
        $schedule = DoctorSchedule::whereDate('work_date', $data['work_date'])->firstOrFail();
        $this->assertSame(9, $schedule->appointmentTimeSlots()->where('status', 'ACTIVE')->count());
        $this->put(route('admin.doctor-schedules.update', $schedule), [...$data, 'shift' => 'LEGACY', 'status' => 'ACTIVE'])->assertSessionHasNoErrors();
        $this->assertSame('ACTIVE', $schedule->fresh()->status);
        $this->assertSame(9, $schedule->appointmentTimeSlots()->where('status', 'ACTIVE')->count());
    }

    public function test_schedule_update_rejects_missing_shift_and_cannot_change_legacy_time(): void
    {
        $schedule = DoctorSchedule::firstOrFail();
        $start = $schedule->start_time;
        $data = [
            'doctor_id' => $schedule->doctor_id, 'specialty_id' => $schedule->specialty_id,
            'examination_room_id' => $schedule->examination_room_id, 'work_date' => $schedule->work_date->format('Y-m-d'),
            'start_time' => '00:00', 'end_time' => '23:59', 'status' => 'INACTIVE',
        ];
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail())
            ->put(route('admin.doctor-schedules.update', $schedule), $data)->assertSessionHasErrors('shift');
        $this->assertSame($start, $schedule->fresh()->start_time);
    }

    public function test_manual_new_slot_must_be_thirty_minutes(): void
    {
        $schedule = DoctorSchedule::firstOrFail();
        $schedule->update(['status' => 'ACTIVE']);
        $schedule->appointmentTimeSlots()->whereDoesntHave('appointments')->delete();
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail())
            ->post(route('admin.doctor-schedules.time-slots.store', $schedule), [
                'start_time' => '10:00', 'end_time' => '10:45', 'status' => 'ACTIVE',
            ])->assertSessionHasErrors('slot_conflict');
        $this->assertDatabaseMissing('appointment_time_slots', [
            'doctor_schedule_id' => $schedule->id, 'start_time' => '10:00:00', 'end_time' => '10:45:00',
        ]);
    }
}
