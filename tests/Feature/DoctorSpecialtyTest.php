<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentStatus;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\ExaminationRoom;
use App\Models\MedicalVisit;
use App\Models\Patient;
use App\Models\Specialty;
use App\Models\User;
use App\Models\VisitConclusion;
use App\Models\VisitStatus;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorSpecialtyTest extends TestCase
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

    private function doctor(): Doctor
    {
        return Doctor::where('doctor_code', 'BS000001')->firstOrFail();
    }

    private function specialty(string $code = 'CKTMH'): Specialty
    {
        return Specialty::where('specialty_code', $code)->firstOrFail();
    }

    private function scheduleData(?int $specialtyId = null): array
    {
        return ['doctor_id' => $this->doctor()->id, 'specialty_id' => $specialtyId ?? $this->specialty()->id,
            'examination_room_id' => ExaminationRoom::where('room_code', 'PKTMH01')->value('id'),
            'work_date' => today()->addDay()->toDateString(), 'start_time' => '09:00', 'end_time' => '11:00', 'status' => 'ACTIVE'];
    }

    private function profileData(?int $specialtyId = null): array
    {
        return ['full_name' => 'Bác sĩ kiểm thử', 'specialty_id' => $specialtyId ?? $this->specialty()->id, 'status' => 'ACTIVE'];
    }

    public function test_doctor_creation_requires_an_active_specialty_and_persists_the_choice(): void
    {
        $data = [...$this->profileData(), 'phone' => '0987654321', 'password' => 'DoctorTest!123', 'password_confirmation' => 'DoctorTest!123'];
        $this->post(route('admin.doctors.store'), [...$data, 'specialty_id' => null])->assertSessionHasErrors('specialty_id');
        $this->post(route('admin.doctors.store'), [...$data, 'specialty_id' => 999999])->assertSessionHasErrors('specialty_id');
        $inactive = $this->specialty('CKDL');
        $inactive->update(['status' => 'INACTIVE']);
        $this->post(route('admin.doctors.store'), [...$data, 'specialty_id' => $inactive->id])->assertSessionHasErrors('specialty_id');
        $this->post(route('admin.doctors.store'), $data)->assertSessionHasNoErrors();
        $created = Doctor::where('full_name', $data['full_name'])->sole();
        $this->assertSame($data['specialty_id'], $created->specialty_id);
        $this->get(route('admin.doctors.show', $created))->assertOk()->assertSee('Tai Mũi Họng');
        $this->get(route('admin.doctors.edit', $created))->assertOk()->assertSee('name="specialty_id"', false);
    }

    public function test_lookup_choices_filter_in_both_directions_and_include_general_doctors(): void
    {
        $general = Doctor::where('doctor_code', 'BS000003')->firstOrFail();
        $general->update(['specialty_id' => $this->specialty('CKDK')->id]);
        $results = $this->getJson(route('admin.lookups', ['type' => 'doctors', 'specialty_id' => $this->specialty()->id]))->assertOk()->json('results');
        $this->assertEqualsCanonicalizing([(string) $this->doctor()->id, (string) $general->id], array_column($results, 'value'));
        $this->assertArrayHasKey('specialty_id', $results[0]);
        $this->assertArrayHasKey('is_general', $results[0]);
        $this->getJson(route('admin.lookups', ['type' => 'specialties', 'doctor_id' => $this->doctor()->id]))
            ->assertOk()->assertJsonCount(1, 'results')->assertJsonPath('results.0.value', (string) $this->specialty()->id);
        $this->getJson(route('admin.lookups', ['type' => 'specialties', 'doctor_id' => $general->id]))
            ->assertOk()->assertJsonCount(Specialty::count(), 'results');
        $this->get(route('admin.doctors.index', ['specialty_id' => $this->specialty()->id]))->assertOk()
            ->assertViewHas('doctors', fn ($rows) => $rows->pluck('id')->sort()->values()->all() === collect([$this->doctor()->id, $general->id])->sort()->values()->all());
    }

    public function test_wrong_specialty_is_rejected_on_schedule_create_and_update(): void
    {
        $wrong = $this->scheduleData($this->specialty('CKDL')->id);
        foreach (['ACTIVE', 'INACTIVE'] as $status) {
            $this->post(route('admin.doctor-schedules.store'), [...$wrong, 'status' => $status])->assertSessionHasErrors('doctor_id');
        }
        $data = $this->scheduleData();
        $this->post(route('admin.doctor-schedules.store'), $data)->assertSessionHasNoErrors();
        $schedule = DoctorSchedule::whereDate('work_date', $data['work_date'])->where('doctor_id', $data['doctor_id'])->sole();
        $this->put(route('admin.doctor-schedules.update', $schedule), $wrong)->assertSessionHasErrors('doctor_id');
        $this->assertSame($data['specialty_id'], $schedule->fresh()->specialty_id);
    }

    public function test_general_doctors_can_be_scheduled_in_every_specialty(): void
    {
        $this->doctor()->update(['specialty_id' => $this->specialty('CKDK')->id]);
        foreach (Specialty::all() as $index => $specialty) {
            $this->post(route('admin.doctor-schedules.store'), [...$this->scheduleData($specialty->id),
                'work_date' => today()->addDays($index + 1)->toDateString()])->assertSessionHasNoErrors();
        }
        $this->assertSame(Specialty::count(), DoctorSchedule::where('doctor_id', $this->doctor()->id)->whereDate('work_date', '>', today())->count());
    }

    public function test_invalid_or_unassigned_doctor_schedules_cannot_be_booked_or_checked_in(): void
    {
        foreach ([$this->specialty('CKDL')->id, $this->specialty()->id] as $index => $specialtyId) {
            if ($index === 1) {
                $this->doctor()->update(['specialty_id' => null]);
            }
            $schedule = DoctorSchedule::create([...$this->scheduleData($specialtyId), 'work_date' => today()->toDateString()]);
            $slot = $schedule->appointmentTimeSlots()->create(['start_time' => '09:00:00', 'end_time' => '09:30:00', 'max_patients' => 10, 'status' => 'ACTIVE']);
            $data = ['patient_id' => Patient::where('patient_code', 'BN000005')->value('id'), 'appointment_time_slot_id' => $slot->id, 'reason_for_visit' => 'Kiểm thử'];
            $this->get(route('admin.appointments.create'))->assertOk()->assertViewHas('schedules', fn ($rows) => ! $rows->contains('id', $schedule->id));
            $this->post(route('admin.appointments.store'), $data)->assertSessionHasErrors('appointment_time_slot_id');
            $legacy = Appointment::create([...$data, 'appointment_code' => 'LHLEGACY'.$index, 'doctor_id' => $schedule->doctor_id,
                'specialty_id' => $specialtyId, 'examination_room_id' => $schedule->examination_room_id,
                'appointment_date' => today(), 'appointment_status_id' => AppointmentStatus::where('name', 'BOOKED')->value('id'),
                'checkin_token' => 'legacy-'.$index, 'booked_at' => now()]);
            $this->post(route('admin.appointments.check-in', $legacy))->assertSessionHasErrors('appointment');
            $this->assertDatabaseMissing('medical_visits', ['appointment_id' => $legacy->id]);
            $this->assertDatabaseHas('appointments', ['id' => $legacy->id, 'specialty_id' => $specialtyId]);
            $this->put(route('admin.doctor-schedules.update', $schedule), [...$this->scheduleData($specialtyId),
                'work_date' => today()->toDateString(), 'status' => 'INACTIVE'])->assertSessionHasNoErrors();
        }
    }

    public function test_profile_specialty_changes_preserve_history_but_reject_incompatible_future_schedules(): void
    {
        $doctor = $this->doctor();
        $history = $doctor->schedules()->firstOrFail()->only(['id', 'specialty_id']);
        $future = DoctorSchedule::create($this->scheduleData());
        $this->put(route('admin.doctors.update', $doctor), $this->profileData($this->specialty('CKTM')->id))->assertSessionHasErrors('specialty_id');
        $this->assertSame($this->specialty()->id, $doctor->fresh()->specialty_id);
        $future->update(['status' => 'INACTIVE']);
        $this->put(route('admin.doctors.update', $doctor), $this->profileData($this->specialty('CKTM')->id))->assertSessionHasErrors('specialty_id');
        MedicalVisit::where('doctor_id', $doctor->id)->update(['visit_status_id' => VisitStatus::where('name', 'COMPLETED')->value('id')]);
        $this->put(route('admin.doctors.update', $doctor), $this->profileData($this->specialty('CKTM')->id))->assertSessionHasNoErrors();
        $this->assertSame($this->specialty('CKTM')->id, $doctor->fresh()->specialty_id);
        $this->assertDatabaseHas('doctor_schedules', $history);
        $this->post(route('admin.appointments.store'), ['patient_id' => Patient::first()->id, 'appointment_time_slot_id' => 99999, 'reason_for_visit' => 'Kiểm thử'])->assertSessionHasErrors();
    }

    public function test_specialties_assigned_to_doctors_cannot_be_deleted_even_without_schedules(): void
    {
        $specialty = $this->specialty('CKDL');
        $this->doctor()->update(['specialty_id' => $specialty->id]);
        $this->delete(route('admin.specialties.destroy', $specialty))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('specialties', ['id' => $specialty->id, 'status' => 'INACTIVE']);
    }

    public function test_conclusion_doctors_are_limited_to_the_visit_specialty_or_general_practice(): void
    {
        $conclusion = VisitConclusion::where('doctor_id', $this->doctor()->id)->firstOrFail();
        $conclusion->medicalVisit->update(['visit_status_id' => VisitStatus::where('name', 'IN_EXAMINATION')->value('id')]);
        $data = ['professional_conclusion' => 'Kết luận kiểm thử', 'has_follow_up' => 0];
        $wrongDoctor = Doctor::where('doctor_code', 'BS000002')->firstOrFail();
        $this->get(route('admin.conclusions.edit', $conclusion))->assertOk()
            ->assertViewHas('doctors', fn ($doctors) => ! $doctors->contains('id', $wrongDoctor->id));
        $this->put(route('admin.conclusions.update', $conclusion), [...$data, 'doctor_id' => $wrongDoctor->id])->assertSessionHasErrors('doctor_id');
        $general = Doctor::where('doctor_code', 'BS000003')->firstOrFail();
        $general->update(['specialty_id' => $this->specialty('CKDK')->id]);
        $this->put(route('admin.conclusions.update', $conclusion), [...$data, 'doctor_id' => $general->id])->assertSessionHasNoErrors();
        $this->assertSame($general->id, $conclusion->fresh()->doctor_id);
    }
}
