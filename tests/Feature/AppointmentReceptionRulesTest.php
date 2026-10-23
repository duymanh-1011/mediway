<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentTimeSlot;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\ExaminationRoom;
use App\Models\ExaminationRoomQueue;
use App\Models\MedicalVisit;
use App\Models\Patient;
use App\Models\QueueStatus;
use App\Models\Specialty;
use App\Models\User;
use App\Models\VisitStatus;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AppointmentReceptionRulesTest extends TestCase
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

    private function slot(int $days = 0): AppointmentTimeSlot
    {
        $schedule = DoctorSchedule::create([
            'doctor_id' => Doctor::where('doctor_code', 'BS000001')->value('id'),
            'specialty_id' => Specialty::where('specialty_code', 'CKTMH')->value('id'),
            'examination_room_id' => ExaminationRoom::where('room_code', 'PKTMH01')->value('id'),
            'work_date' => today()->addDays($days)->toDateString(),
            'start_time' => '09:00', 'end_time' => '11:00', 'status' => 'ACTIVE',
        ]);

        return $schedule->appointmentTimeSlots()->create(['start_time' => '09:00', 'end_time' => '09:30', 'max_patients' => 10, 'status' => 'ACTIVE']);
    }

    private function data(AppointmentTimeSlot $slot, string $patient = 'BN000005'): array
    {
        return ['patient_id' => Patient::where('patient_code', $patient)->value('id'),
            'appointment_time_slot_id' => $slot->id, 'reason_for_visit' => 'Khám theo lịch'];
    }

    private function book(AppointmentTimeSlot $slot, string $patient = 'BN000005'): Appointment
    {
        $this->post(route('admin.appointments.store'), $this->data($slot, $patient))->assertSessionHasNoErrors();

        return Appointment::where('appointment_time_slot_id', $slot->id)->where('patient_id', $this->data($slot, $patient)['patient_id'])->firstOrFail();
    }

    public function test_future_and_expired_appointments_cannot_be_received_but_same_day_can(): void
    {
        $appointment = $this->book($this->slot(1));
        $this->get(route('admin.appointments.show', $appointment))->assertOk()->assertDontSee('action="'.route('admin.appointments.check-in', $appointment).'"', false);
        $this->post(route('admin.appointments.check-in', $appointment))->assertSessionHasErrors('appointment');
        $this->assertFalse($appointment->medicalVisit()->exists());
        $this->travelTo(today()->addDays(2)->setTime(7, 0));
        $this->post(route('admin.appointments.check-in', $appointment))->assertSessionHasErrors('appointment');
        $this->assertFalse($appointment->medicalVisit()->exists());
        $this->travelTo(today()->subDay()->setTime(7, 0));
        $this->post(route('admin.appointments.check-in', $appointment))->assertSessionHasNoErrors();
        $this->assertSame(1, $appointment->medicalVisit()->count());
        $this->post(route('admin.appointments.check-in', $appointment));
        $this->assertSame(1, $appointment->medicalVisit()->count());
    }

    public function test_duplicate_bookings_and_duplicate_updates_are_rejected_while_other_patients_can_book(): void
    {
        $slot = $this->slot(1);
        $first = $this->book($slot);
        $this->post(route('admin.appointments.store'), $this->data($slot))->assertSessionHasErrors('patient_id');
        $other = $this->book($slot, 'BN000004');
        $this->put(route('admin.appointments.update', $other), $this->data($slot))->assertSessionHasErrors('patient_id');
        $this->assertNotEquals($first->patient_id, $other->fresh()->patient_id);
        $this->assertSame(2, $slot->appointments()->count());
        $this->put(route('admin.appointments.update', $first), $this->data($slot))->assertSessionHasNoErrors();
    }

    public function test_cancelled_booking_does_not_prevent_a_new_booking(): void
    {
        $slot = $this->slot(1);
        $first = $this->book($slot);
        $this->post(route('admin.appointments.cancel', $first), ['cancellation_reason' => 'Đổi kế hoạch'])->assertSessionHasNoErrors();
        $this->post(route('admin.appointments.store'), $this->data($slot))->assertSessionHasNoErrors();
        $this->assertSame(2, $slot->appointments()->count());
    }

    public function test_existing_duplicate_records_cannot_both_create_visits(): void
    {
        $first = $this->book($this->slot());
        $duplicate = $first->replicate();
        $duplicate->appointment_code = 'LHDUPLICATE';
        $duplicate->checkin_token = (string) Str::uuid();
        $duplicate->save();
        $this->post(route('admin.appointments.check-in', $first))->assertSessionHasNoErrors();
        $this->post(route('admin.appointments.check-in', $duplicate))->assertSessionHasErrors('appointment');
        $this->assertFalse($duplicate->medicalVisit()->exists());
        $this->assertSame('BOOKED', $duplicate->fresh()->appointmentStatus->name);
    }

    public function test_legacy_future_visit_cannot_start_examination_or_write_clinical_data(): void
    {
        $appointment = $this->book($this->slot(1));
        $visit = MedicalVisit::create([
            'visit_code' => 'LKFUTURE', 'appointment_id' => $appointment->id, 'patient_id' => $appointment->patient_id,
            'doctor_id' => $appointment->doctor_id, 'specialty_id' => $appointment->specialty_id,
            'examination_room_id' => $appointment->examination_room_id,
            'visit_status_id' => VisitStatus::where('name', 'WAITING')->value('id'), 'checked_in_at' => now(), 'reception_source' => 'COUNTER',
        ]);
        $queue = ExaminationRoomQueue::create([
            'medical_visit_id' => $visit->id, 'queue_status_id' => QueueStatus::where('name', 'CALLED')->value('id'),
            'queue_number' => 90, 'queue_type' => 'INITIAL_EXAM', 'queued_at' => now(), 'call_count' => 1,
        ]);
        $this->post(route('admin.queues.start-service', $queue))->assertSessionHasErrors('visit');
        $this->patch(route('admin.visits.update-status', $visit), ['visit_status_id' => VisitStatus::where('name', 'IN_EXAMINATION')->value('id')])->assertSessionHasErrors('visit');
        $this->assertSame('WAITING', $visit->fresh()->status->name);
        $visit->update(['visit_status_id' => VisitStatus::where('name', 'IN_EXAMINATION')->value('id')]);
        $this->post(route('admin.visits.clinical-note.store', $visit), ['reason_for_visit' => 'Không được khám sớm']);
        $this->assertFalse($visit->clinicalNote()->exists());
    }
}
