<?php

namespace Tests\Feature;

use App\Models\AppointmentTimeSlot;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\ExaminationRoom;
use App\Models\Patient;
use App\Models\Specialty;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentBookingSelectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 23)->setTime(7, 0));
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail());
    }

    private function slot(int $days): AppointmentTimeSlot
    {
        $schedule = DoctorSchedule::create([
            'doctor_id' => Doctor::where('doctor_code', 'BS000001')->value('id'),
            'specialty_id' => Specialty::where('specialty_code', 'CKTMH')->value('id'),
            'examination_room_id' => ExaminationRoom::where('room_code', 'PKTMH01')->value('id'),
            'work_date' => today()->addDays($days)->toDateString(), 'start_time' => '09:00', 'end_time' => '11:00', 'status' => 'ACTIVE',
        ]);

        return $schedule->appointmentTimeSlots()->create(['start_time' => '09:00', 'end_time' => '09:30', 'max_patients' => 10, 'status' => 'ACTIVE']);
    }

    private function data(AppointmentTimeSlot $slot, string $patient = 'BN000005'): array
    {
        return ['patient_id' => Patient::where('patient_code', $patient)->value('id'),
            'appointment_time_slot_id' => $slot->id, 'reason_for_visit' => 'Khám theo lịch'];
    }

    public function test_only_today_through_the_third_following_day_are_exposed_and_bookable(): void
    {
        $today = $this->slot(0);
        $last = $this->slot(3);
        $outside = $this->slot(4);
        $response = $this->get(route('admin.appointments.create'))->assertOk();
        $this->assertEqualsCanonicalizing([$today->doctor_schedule_id, $last->doctor_schedule_id], $response->viewData('schedules')->pluck('id')->all());
        preg_match('/<script[^>]*data-booking-schedules>(.*?)<\/script>/s', $response->getContent(), $metadata);
        $data = json_decode($metadata[1], true, 512, JSON_THROW_ON_ERROR);
        $this->assertEqualsCanonicalizing(['2026-10-23', '2026-10-26'], array_column($data, 'date'));
        foreach (['doctor', 'room', 'specialty'] as $kind) {
            $response->assertSee('data-booking-kind="'.$kind.'"', false);
        }
        $this->post(route('admin.appointments.store'), $this->data($last))->assertSessionHasNoErrors();
        $this->post(route('admin.appointments.store'), $this->data($outside))->assertSessionHasErrors('appointment_time_slot_id');
        $this->assertSame(0, $outside->appointments()->count());
        $appointment = $last->appointments()->sole();
        $this->put(route('admin.appointments.update', $appointment), $this->data($outside))->assertSessionHasErrors('appointment_time_slot_id');
        $this->assertSame($last->id, $appointment->fresh()->appointment_time_slot_id);
    }

    public function test_each_booking_direction_accepts_only_its_own_matching_schedule(): void
    {
        $slot = $this->slot(1);
        $schedule = $slot->doctorSchedule;
        $criteria = ['booking_doctor_id' => $schedule->doctor_id, 'booking_room_id' => $schedule->examination_room_id, 'booking_specialty_id' => $schedule->specialty_id];
        foreach (array_keys($criteria) as $index => $field) {
            $this->post(route('admin.appointments.store'), [...$this->data($slot, 'BN00000'.($index + 1)), $field => $criteria[$field]])->assertSessionHasNoErrors();
        }
        $this->assertSame(3, $slot->appointments()->count());
        foreach ([
            'booking_doctor_id' => Doctor::whereKeyNot($schedule->doctor_id)->value('id'),
            'booking_room_id' => ExaminationRoom::whereKeyNot($schedule->examination_room_id)->value('id'),
            'booking_specialty_id' => Specialty::whereKeyNot($schedule->specialty_id)->value('id'),
        ] as $field => $id) {
            $this->post(route('admin.appointments.store'), [...$this->data($slot), $field => $id])->assertSessionHasErrors('appointment_time_slot_id');
        }
        $this->assertSame(3, $slot->appointments()->count());
    }

    public function test_multiple_directions_cannot_be_combined_on_create_or_update(): void
    {
        $slot = $this->slot(1);
        $multiple = ['booking_doctor_id' => $slot->doctorSchedule->doctor_id, 'booking_room_id' => $slot->doctorSchedule->examination_room_id];
        $this->post(route('admin.appointments.store'), [...$this->data($slot), ...$multiple])->assertSessionHasErrors(['booking_doctor_id', 'booking_room_id']);
        $this->assertSame(0, $slot->appointments()->count());
        $this->post(route('admin.appointments.store'), $this->data($slot))->assertSessionHasNoErrors();
        $appointment = $slot->appointments()->sole();
        $this->put(route('admin.appointments.update', $appointment), [...$this->data($slot), ...$multiple])->assertSessionHasErrors(['booking_doctor_id', 'booking_room_id']);
        $this->assertSame($slot->id, $appointment->fresh()->appointment_time_slot_id);
    }

    public function test_edit_and_validation_failures_preserve_the_selected_schedule_and_direction(): void
    {
        $slot = $this->slot(1);
        $this->post(route('admin.appointments.store'), $this->data($slot))->assertSessionHasNoErrors();
        $appointment = $slot->appointments()->sole();
        $response = $this->withSession(['_old_input' => [...$this->data($slot), 'booking_room_id' => $slot->doctorSchedule->examination_room_id]])
            ->get(route('admin.appointments.edit', $appointment))->assertOk();
        $response->assertSee('data-selected-slot="'.$slot->id.'"', false);
        $this->assertContains($slot->doctor_schedule_id, $response->viewData('schedules')->pluck('id')->all());
        $this->put(route('admin.appointments.update', $appointment), [...$this->data($slot), 'booking_room_id' => $slot->doctorSchedule->examination_room_id])->assertSessionHasNoErrors();
        $this->assertSame($slot->id, $appointment->fresh()->appointment_time_slot_id);
    }
}
