<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentStatus;
use App\Models\AuditLog;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\ExaminationRoom;
use App\Models\MedicalVisit;
use App\Models\User;
use App\Models\VisitStatus;
use App\Services\DoctorMonthlyScheduleService;
use Tests\Fixtures\Seeders\WorkflowFixtureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class DoctorMonthlyScheduleServiceTest extends TestCase
{
    use RefreshDatabase;

    private DoctorMonthlyScheduleService $service;

    private User $admin;

    private DoctorSchedule $legacy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(WorkflowFixtureSeeder::class);
        $this->service = app(DoctorMonthlyScheduleService::class);
        $this->admin = User::where('business_code', 'QT000001')->firstOrFail();
        $this->legacy = DoctorSchedule::with('appointmentTimeSlots')->orderBy('id')->firstOrFail();
    }

    public function test_month_includes_all_days_and_keeps_legacy_schedules_separate(): void
    {
        $date = $this->legacy->work_date->toDateString();
        $month = $this->legacy->work_date->format('Y-m');
        $off = $this->update($date, 'AFTERNOON', 'OFF')->fresh();
        $day = $this->service->forMonth($this->admin, $this->legacy->doctor_id, $month)['days'];

        $this->assertCount($this->legacy->work_date->daysInMonth, $day);
        $this->assertSame('UNCONFIGURED', $day[$date]['shifts']['MORNING']['state']);
        $this->assertNull($day[$date]['shifts']['MORNING']['revision']);
        $this->assertSame('OFF', $day[$date]['shifts']['AFTERNOON']['state']);
        $this->assertTrue($off->is($day[$date]['shifts']['AFTERNOON']['schedule']));
        $this->assertTrue($this->legacy->is($day[$date]['legacy_schedules']->sole()));

        $otherDoctor = Doctor::whereKeyNot($this->legacy->doctor_id)->firstOrFail();
        $other = $this->service->forMonth($this->admin, $otherDoctor->id, $month)['days'][$date];
        $this->assertSame('UNCONFIGURED', $other['shifts']['AFTERNOON']['state']);
        $this->assertCount(29, $this->service->forMonth($this->admin, $this->legacy->doctor_id, '2028-02')['days']);
    }

    public function test_updates_only_the_selected_cell_and_audits_real_changes(): void
    {
        $date = '2090-02-01';
        $working = $this->update($date, 'MORNING', 'WORKING', assign: true);
        $off = $this->update($date, 'AFTERNOON', 'OFF')->fresh();
        $revision = $this->revision($date, 'MORNING');
        $this->update($date, 'MORNING', 'WORKING', $revision);
        $this->assertCount(1, $this->audit($working));

        $updated = $this->update($date, 'MORNING', 'OFF', $revision);
        $this->assertTrue($working->is($updated));
        $this->assertSame('INACTIVE', $updated->status);
        $this->assertSame($working->examination_room_id, $updated->examination_room_id);
        $this->assertSame('07:30:00', $updated->start_time);
        $this->assertSame('11:30:00', $updated->end_time);
        $this->assertSame($off->getAttributes(), $off->fresh()->getAttributes());
        $log = $this->audit($working)->last();
        $this->assertSame($this->admin->id, $log->user_id);
        $this->assertSame('ACTIVE', $log->old_values['status']);
        $this->assertSame('INACTIVE', $log->new_values['status']);

        $this->rejects('revision', fn () => $this->update($date, 'MORNING', 'WORKING', $revision));
        $this->rejects('revision', fn () => $this->update($date, 'AFTERNOON', 'OFF'));
        $this->assertCount(2, $this->audit($working));
    }

    public function test_working_shifts_generate_exactly_eight_and_seven_slots_and_reuse_them(): void
    {
        $date = '2090-02-01';
        $morning = $this->update($date, 'MORNING', 'WORKING', assign: true);
        $afternoon = $this->update($date, 'AFTERNOON', 'WORKING', assign: true);
        foreach ([[$morning, 8, '07:30:00', '11:30:00'], [$afternoon, 7, '13:30:00', '17:00:00']] as [$shift, $count, $start, $end]) {
            $slots = $shift->appointmentTimeSlots()->orderBy('start_time')->get();
            $this->assertCount($count, $slots);
            $this->assertSame($start, $slots->first()->start_time);
            $this->assertSame($end, $slots->last()->end_time);
            $this->assertCount($count, $slots->whereNull('max_patients'));
            $this->update($date, $shift->shift_code, 'OFF', $this->revision($date, $shift->shift_code));
            $this->update($date, $shift->shift_code, 'WORKING', $this->revision($date, $shift->shift_code));
            $this->assertSame($slots->modelKeys(), $shift->appointmentTimeSlots()->orderBy('start_time')->get()->modelKeys());
        }
        $off = $this->update('2090-02-02', 'MORNING', 'OFF');
        $this->assertFalse($off->appointmentTimeSlots()->exists());
    }

    public function test_legacy_overlap_and_invalid_working_assignments_are_rejected(): void
    {
        $date = $this->legacy->work_date->toDateString();
        $before = $this->legacy->getAttributes();
        $this->rejects('shift', fn () => $this->update($date, 'MORNING', 'WORKING', assign: true));
        $this->rejects('shift', fn () => $this->update($date, 'MORNING', 'OFF'));
        $this->rejects('specialty_id', fn () => $this->update('2090-02-01', 'MORNING', 'WORKING'));
        $this->rejects('date', fn () => $this->update('2090-02-30', 'MORNING', 'OFF'));
        $this->rejects('shift', fn () => $this->update('2090-02-01', 'EVENING', 'OFF'));
        $this->assertSame($before, $this->legacy->fresh()->getAttributes());
    }

    public function test_working_shifts_cannot_share_a_room_but_a_day_off_does_not_reserve_it(): void
    {
        $date = '2090-02-01';
        $first = $this->update($date, 'MORNING', 'WORKING', assign: true);
        $secondDoctor = Doctor::whereKeyNot($first->doctor_id)->firstOrFail();
        $secondDoctor->update(['specialty_id' => $first->specialty_id]);
        $createSecond = fn () => $this->service->updateShift(
            $this->admin, $secondDoctor->id, $date, 'MORNING', 'WORKING', null, $first->specialty_id, $first->examination_room_id,
        );
        $this->rejects('examination_room_id', $createSecond);
        $this->update($date, 'MORNING', 'OFF', $this->revision($date, 'MORNING'));
        $this->assertSame($secondDoctor->id, $createSecond()->doctor_id);
    }

    public function test_inactive_legacy_schedule_still_blocks_an_overlapping_shift_if_it_has_an_unresolved_booking(): void
    {
        $this->legacy->update(['status' => 'INACTIVE']);
        $booking = Appointment::whereIn('appointment_time_slot_id', $this->legacy->appointmentTimeSlots->modelKeys())->firstOrFail();
        $booking->update(['appointment_status_id' => AppointmentStatus::where('name', 'BOOKED')->value('id')]);
        $date = $this->legacy->work_date->toDateString();

        $this->rejects('shift', fn () => $this->update($date, 'MORNING', 'WORKING', assign: true));
        $this->rejects('shift', fn () => $this->update($date, 'MORNING', 'OFF'));
        $this->assertSame('INACTIVE', $this->legacy->fresh()->status);
        $this->assertSame('BOOKED', $booking->fresh()->appointmentStatus->name);
    }

    public function test_existing_appointments_keep_their_slot_and_assignment(): void
    {
        $date = '2090-02-01';
        $schedule = $this->update($date, 'MORNING', 'WORKING', assign: true);
        $slot = $schedule->appointmentTimeSlots()->where('start_time', '08:00:00')->sole();
        $appointment = Appointment::orderBy('id')->firstOrFail()->replicate()->fill([
            'appointment_code' => 'LH-SHIFT-TEST', 'checkin_token' => 'CHECKIN-SHIFT-TEST', 'appointment_time_slot_id' => $slot->id,
            'appointment_date' => $date, 'appointment_status_id' => AppointmentStatus::where('name', 'BOOKED')->value('id'),
        ]);
        $appointment->save();
        $revision = $this->revision($date, 'MORNING');
        $this->rejects('shift', fn () => $this->update($date, 'MORNING', 'OFF', $revision));
        $this->assertSame('ACTIVE', $schedule->fresh()->status);
        $this->assertCount(1, $this->audit($schedule));

        $appointment->update(['appointment_status_id' => AppointmentStatus::where('name', 'CANCELLED')->value('id')]);
        $room = ExaminationRoom::create([
            'room_code' => 'PK-SHIFT-TEST', 'name' => 'Phòng kiểm thử lịch tháng', 'room_number' => '501',
            'specialty_id' => $schedule->specialty_id, 'status' => 'ACTIVE',
        ]);
        $this->rejects('shift', fn () => $this->service->updateShift(
            $this->admin, $schedule->doctor_id, $date, 'MORNING', 'WORKING', $revision, $schedule->specialty_id, $room->id,
        ));
        $before = $appointment->fresh()->getAttributes();
        $this->update($date, 'MORNING', 'OFF', $revision);
        $this->assertSame($before, $appointment->fresh()->getAttributes());
        $this->assertSame($slot->getAttributes(), $slot->fresh()->getAttributes());
    }

    public function test_unfinished_walk_in_blocks_a_shift_from_becoming_off(): void
    {
        $date = '2090-02-01';
        $schedule = $this->update($date, 'MORNING', 'WORKING', assign: true);
        $visit = MedicalVisit::orderBy('id')->firstOrFail()->replicate()->fill([
            'visit_code' => 'LK-SHIFT-TEST', 'appointment_id' => null, 'reception_source' => 'WALK_IN',
            'doctor_id' => $schedule->doctor_id, 'specialty_id' => $schedule->specialty_id, 'examination_room_id' => $schedule->examination_room_id,
            'checked_in_at' => $date.' 09:00:00', 'examination_started_at' => null, 'examination_ended_at' => null,
            'visit_status_id' => VisitStatus::where('name', 'WAITING')->value('id'),
        ]);
        $visit->save();
        $this->rejects('shift', fn () => $this->update($date, 'MORNING', 'OFF', $this->revision($date, 'MORNING')));
        $this->assertSame('ACTIVE', $schedule->fresh()->status);
    }

    public function test_only_active_administrators_can_manage_monthly_shifts(): void
    {
        $patient = User::whereHas('role', fn ($query) => $query->where('name', 'Patient'))->firstOrFail();
        foreach ([
            fn () => $this->service->forMonth($patient, $this->legacy->doctor_id, '2090-02'),
            fn () => $this->service->updateShift($patient, $this->legacy->doctor_id, '2090-02-01', 'MORNING', 'OFF', null),
        ] as $action) {
            try {
                $action();
                $this->fail('A patient accessed schedule management.');
            } catch (HttpException $error) {
                $this->assertSame(403, $error->getStatusCode());
            }
        }
    }

    private function update(string $date, string $code, string $state, ?string $revision = null, bool $assign = false): DoctorSchedule
    {
        return $this->service->updateShift($this->admin, $this->legacy->doctor_id, $date, $code, $state, $revision,
            $assign ? $this->legacy->specialty_id : null, $assign ? $this->legacy->examination_room_id : null);
    }

    private function revision(string $date, string $code): ?string
    {
        return $this->service->forMonth($this->admin, $this->legacy->doctor_id, substr($date, 0, 7))['days'][$date]['shifts'][$code]['revision'];
    }

    private function audit(DoctorSchedule $schedule)
    {
        return AuditLog::where('entity_type', 'doctor_schedules')->where('entity_id', $schedule->id)->orderBy('id')->get();
    }

    private function rejects(string $field, callable $action): void
    {
        try {
            $action();
            $this->fail('The change should have been rejected.');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey($field, $error->errors());
        }
    }
}
