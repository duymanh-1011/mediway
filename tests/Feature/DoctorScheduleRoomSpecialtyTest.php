<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\ExaminationRoom;
use App\Models\Specialty;
use App\Models\User;
use Tests\Fixtures\Seeders\WorkflowFixtureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorScheduleRoomSpecialtyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(WorkflowFixtureSeeder::class);
        $this->withoutVite();
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail());
    }

    private function data(): array
    {
        return ['doctor_id' => Doctor::where('doctor_code', 'BS000001')->value('id'),
            'specialty_id' => Specialty::where('specialty_code', 'CKTMH')->value('id'),
            'examination_room_id' => ExaminationRoom::where('room_code', 'PKTMH01')->value('id'),
            'work_date' => today()->addDays(50)->toDateString(), 'start_time' => '09:00', 'end_time' => '10:00', 'status' => 'ACTIVE'];
    }

    public function test_room_must_match_specialty_on_create_and_update(): void
    {
        $data = $this->data();
        $wrong = ExaminationRoom::where('room_code', 'PKTM01')->firstOrFail();
        $route = route('admin.doctor-schedules.month.update', $data['doctor_id']);
        $shift = ['date' => $data['work_date'], 'shift' => 'MORNING', 'state' => 'WORKING', 'specialty_id' => $data['specialty_id']];
        $this->put($route, [...$shift, 'room_id' => $wrong->id])->assertSessionHasErrors('examination_room_id');
        $wrong->update(['specialty_id' => null]);
        $this->put($route, [...$shift, 'room_id' => $wrong->id])->assertSessionHasErrors('examination_room_id');
        $this->put($route, [...$shift, 'room_id' => $data['examination_room_id']])->assertSessionHasNoErrors();
        $schedule = DoctorSchedule::create([...$data, 'work_date' => today()->addDays(51)->toDateString()]);
        $this->put(route('admin.doctor-schedules.update', $schedule), [...$data,
            'work_date' => $schedule->work_date->toDateString(), 'examination_room_id' => $wrong->id])->assertSessionHasErrors('examination_room_id');
        $this->assertSame($data['examination_room_id'], $schedule->fresh()->examination_room_id);
        $this->get(route('admin.doctor-schedules.month.show', [$data['doctor_id'], 'month' => substr($data['work_date'], 0, 7)]))
            ->assertOk()->assertSee('data-month-room', false)->assertSee('data-specialty="'.$data['specialty_id'].'"', false);
    }

    public function test_cannot_reassign_room_with_future_active_schedule_from_other_specialty(): void
    {
        $data = $this->data();
        $this->put(route('admin.doctor-schedules.month.update', $data['doctor_id']), [
            'date' => $data['work_date'], 'shift' => 'MORNING', 'state' => 'WORKING',
            'specialty_id' => $data['specialty_id'], 'room_id' => $data['examination_room_id'],
        ])->assertSessionHasNoErrors();
        $room = ExaminationRoom::findOrFail($data['examination_room_id']);
        $this->put(route('admin.examination-rooms.update', $room), [
            'room_code' => $room->room_code, 'name' => $room->name, 'room_number' => $room->room_number,
            'status' => 'ACTIVE', 'specialty_id' => Specialty::where('specialty_code', 'CKTM')->value('id'),
        ])->assertSessionHasErrors('specialty_id');
        $this->assertEquals($data['specialty_id'], $room->fresh()->specialty_id);
    }

    public function test_existing_unassigned_room_can_be_assigned_when_no_conflicting_future_schedule(): void
    {
        $room = ExaminationRoom::where('room_code', 'PKTMH01')->firstOrFail();
        $room->update(['specialty_id' => null]);
        $this->put(route('admin.examination-rooms.update', $room), [
            'room_code' => $room->room_code, 'name' => $room->name, 'room_number' => $room->room_number,
            'status' => 'ACTIVE', 'specialty_id' => Specialty::where('specialty_code', 'CKTMH')->value('id'),
        ])->assertSessionHasNoErrors();
        $this->assertEquals($this->data()['specialty_id'], $room->fresh()->specialty_id);
    }

    public function test_edit_preserves_existing_inactive_specialty_but_cannot_assign_another_inactive_one(): void
    {
        $room = ExaminationRoom::where('room_code', 'PKTMH01')->firstOrFail();
        $current = Specialty::findOrFail($room->specialty_id);
        $current->update(['status' => 'INACTIVE']);
        $data = ['room_code' => $room->room_code, 'name' => $room->name, 'room_number' => $room->room_number,
            'status' => 'ACTIVE', 'specialty_id' => $current->id];
        $this->get(route('admin.examination-rooms.edit', $room))->assertOk()->assertSee($current->name)->assertSee('Ngừng hoạt động');
        $this->put(route('admin.examination-rooms.update', $room), $data)->assertSessionHasNoErrors();
        $other = Specialty::where('specialty_code', 'CKTM')->firstOrFail();
        $other->update(['status' => 'INACTIVE']);
        $this->put(route('admin.examination-rooms.update', $room), [...$data, 'specialty_id' => $other->id])->assertSessionHasErrors('specialty_id');
        $this->assertEquals($current->id, $room->fresh()->specialty_id);
    }

    public function test_specialty_referenced_only_by_a_room_is_deactivated_without_deleting_room(): void
    {
        $specialty = Specialty::where('specialty_code', 'CKDL')->firstOrFail();
        $room = ExaminationRoom::where('room_code', 'PKDL01')->firstOrFail();
        $this->assertFalse($specialty->doctors()->exists());
        $this->delete(route('admin.specialties.destroy', $specialty))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('specialties', ['id' => $specialty->id, 'status' => 'INACTIVE']);
        $this->assertDatabaseHas('examination_rooms', ['id' => $room->id, 'specialty_id' => $specialty->id]);
    }
}
