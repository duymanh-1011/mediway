<?php

namespace Tests\Feature\Portal;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\ExaminationRoom;
use App\Models\Specialty;
use App\Models\User;
use Tests\Fixtures\Seeders\WorkflowFixtureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentBookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(
            now()
                ->setDate(2026, 10, 27)
                ->setTime(8, 0, 0)
        );

        $this->seed(WorkflowFixtureSeeder::class);        // M06_TEST_PROFILE_FIXTURE_20261030
        \Illuminate\Support\Facades\DB::table('patient_details')
            ->whereIn(
                'patient_id',
                \Illuminate\Support\Facades\DB::table('patients')
                    ->whereIn(
                        'patient_code',
                        ['BN000001', 'BN000002']
                    )
                    ->pluck('id')
            )
            ->update([
                'current_address' => '12 Pho Bach Mai, Ha Noi',
            ]);
        $this->withoutVite();
    }

    private function patientUser(
        string $patientCode = 'BN000001'
    ): User {
        return User::query()
            ->where('business_code', $patientCode)
            ->firstOrFail();
    }

    private function createSlot(
        int $daysFromToday = 1,
        int $maxPatients = 1,
        string $slotStatus = 'ACTIVE',
        string $scheduleStatus = 'ACTIVE',
        string $doctorCode = 'BS000001',
    ) {
        $doctor = Doctor::query()
            ->where('doctor_code', $doctorCode)
            ->firstOrFail();

        $specialty = Specialty::query()
            ->whereKey($doctor->specialty_id)
            ->firstOrFail();

        $room = ExaminationRoom::query()
            ->where('specialty_id', $specialty->id)
            ->firstOrFail();

        $schedule = DoctorSchedule::create([
            'doctor_id' => $doctor->id,
            'specialty_id' => $specialty->id,
            'examination_room_id' => $room->id,
            'work_date' => today()
                ->addDays($daysFromToday)
                ->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'status' => $scheduleStatus,
        ]);

        $slot = $schedule
            ->appointmentTimeSlots()
            ->create([
                'start_time' => '09:00:00',
                'end_time' => '09:30:00',
                'max_patients' => $maxPatients,
                'status' => $slotStatus,
            ]);

        return [
            $schedule,
            $slot,
            $doctor,
            $specialty,
            $room,
        ];
    }

    private function bookingData(
        int $slotId
    ): array {
        return [
            'appointment_time_slot_id' => $slotId,
            'reason_for_visit' => 'Đau họng và ho trong hai ngày.',
            'patient_notes' => 'Không có ghi chú thêm.',
            'confirmed_information' => '1',
        ];
    }

    public function test_patient_can_book_an_available_slot(): void
    {
        [, $slot] =
            $this->createSlot();

        $user =
            $this->patientUser();

        $this->actingAs($user)
            ->post(
                route(
                    'patient.appointments.store'
                ),
                $this->bookingData(
                    $slot->id
                )
            )
            ->assertSessionHasNoErrors();

        $appointment =
            Appointment::query()
            ->where(
                'patient_id',
                $user->patient->id
            )
            ->where(
                'appointment_time_slot_id',
                $slot->id
            )
            ->first();

        $this->assertNotNull(
            $appointment
        );

        $this->assertSame(
            'BOOKED',
            $appointment
                ->appointmentStatus
                ->name
        );

        $this->assertSame(
            'Đau họng và ho trong hai ngày.',
            $appointment
                ->reason_for_visit
        );

        $this->assertNotNull(
            $appointment
                ->appointment_code
        );

        $this->assertNotNull(
            $appointment
                ->checkin_token
        );
    }

    public function test_duplicate_submit_does_not_create_second_appointment(): void
    {
        [, $slot] =
            $this->createSlot(
                maxPatients: 3
            );

        $user =
            $this->patientUser();

        $this->actingAs($user);

        $this->post(
            route(
                'patient.appointments.store'
            ),
            $this->bookingData(
                $slot->id
            )
        )->assertSessionHasNoErrors();

        $this->post(
            route(
                'patient.appointments.store'
            ),
            $this->bookingData(
                $slot->id
            )
        )->assertSessionHasErrors(
            'appointment_time_slot_id'
        );

        $this->assertSame(
            1,
            Appointment::query()
                ->where(
                    'patient_id',
                    $user->patient->id
                )
                ->where(
                    'appointment_time_slot_id',
                    $slot->id
                )
                ->count()
        );
    }

    public function test_two_patients_can_book_the_same_slot_regardless_of_legacy_capacity(): void
    {
        [, $slot] =
            $this->createSlot(
                maxPatients: 1
            );

        $firstPatient =
            $this->patientUser(
                'BN000001'
            );

        $secondPatient =
            $this->patientUser(
                'BN000002'
            );

        $this->actingAs(
            $firstPatient
        )
            ->post(
                route(
                    'patient.appointments.store'
                ),
                $this->bookingData(
                    $slot->id
                )
            )
            ->assertSessionHasNoErrors();

        $this->actingAs(
            $secondPatient
        )
            ->post(
                route(
                    'patient.appointments.store'
                ),
                $this->bookingData(
                    $slot->id
                )
            )
            ->assertSessionHasNoErrors();

        $this->assertSame(
            2,
            Appointment::query()
                ->where(
                    'appointment_time_slot_id',
                    $slot->id
                )
                ->count()
        );
    }

    public function test_today_is_afternoon_only_and_post_rechecks_the_cutoff_at_thirteen_thirty(): void
    {
        $this->actingAs($this->patientUser());
        [$schedule, $morning] = $this->createSlot(daysFromToday: 0);
        $afternoonSchedule = $schedule->replicate();
        $afternoonSchedule->fill(['start_time' => '13:30:00', 'end_time' => '17:00:00'])->save();
        $afternoon = $afternoonSchedule->appointmentTimeSlots()->create([
            'start_time' => '15:00:00', 'end_time' => '15:30:00', 'status' => 'ACTIVE', 'max_patients' => null,
        ]);
        [, $tomorrow] = $this->createSlot(daysFromToday: 1);
        [, $dayAfter] = $this->createSlot(daysFromToday: 2);
        [, $fourthDay] = $this->createSlot(daysFromToday: 4);
        [, $tooLate] = $this->createSlot(daysFromToday: 5);

        $this->travelTo(today()->setTime(9, 0));
        $this->get(route('patient.appointments.create', $morning))->assertRedirect(route('patient.doctors.index'));
        $this->get(route('patient.appointments.create', $afternoon))->assertOk();
        $this->get(route('patient.appointments.create', $tooLate))->assertRedirect(route('patient.doctors.index'));
        $this->get(route('patient.appointments.create', $fourthDay))->assertOk();
        $this->travelTo(today()->setTime(13, 29));
        $this->get(route('patient.appointments.create', $afternoon))->assertOk();
        $this->travelTo(today()->setTime(13, 30));
        $this->post(route('patient.appointments.store'), $this->bookingData($afternoon->id))->assertSessionHasErrors('appointment_time_slot_id');
        $this->get(route('patient.appointments.create', $tomorrow))->assertOk();
        $this->get(route('patient.appointments.create', $dayAfter))->assertOk();
        $this->travelTo(today()->setTime(18, 0));
        $this->get(route('patient.appointments.create', $afternoon))->assertRedirect(route('patient.doctors.index'));
        $this->get(route('patient.appointments.create', $tomorrow))->assertOk();
        $this->assertFalse($afternoon->appointments()->exists());
    }

    public function test_same_patient_cannot_book_two_different_slots_with_overlapping_times(): void
    {
        [, $first] = $this->createSlot();
        [, $second] = $this->createSlot(doctorCode: 'BS000002');
        $this->actingAs($this->patientUser());
        $this->post(route('patient.appointments.store'), $this->bookingData($first->id))->assertSessionHasNoErrors();
        $this->post(route('patient.appointments.store'), $this->bookingData($second->id))->assertSessionHasErrors('appointment_time_slot_id');
        $this->assertFalse($second->appointments()->exists());
    }

    public function test_patient_cannot_view_another_patients_appointment(): void
    {
        [, $slot] =
            $this->createSlot();

        $owner =
            $this->patientUser(
                'BN000001'
            );

        $anotherPatient =
            $this->patientUser(
                'BN000002'
            );

        $this->actingAs(
            $owner
        )
            ->post(
                route(
                    'patient.appointments.store'
                ),
                $this->bookingData(
                    $slot->id
                )
            )
            ->assertSessionHasNoErrors();

        $appointment =
            Appointment::query()
            ->where(
                'patient_id',
                $owner->patient->id
            )
            ->where(
                'appointment_time_slot_id',
                $slot->id
            )
            ->firstOrFail();

        $this->actingAs(
            $anotherPatient
        )
            ->get(
                route(
                    'patient.appointments.show',
                    $appointment
                )
            )
            ->assertNotFound();
    }

    public function test_owner_can_view_own_appointment(): void
    {
        [, $slot] =
            $this->createSlot();

        $owner =
            $this->patientUser();

        $this->actingAs(
            $owner
        )
            ->post(
                route(
                    'patient.appointments.store'
                ),
                $this->bookingData(
                    $slot->id
                )
            )
            ->assertSessionHasNoErrors();

        $appointment =
            Appointment::query()
            ->where(
                'patient_id',
                $owner->patient->id
            )
            ->where(
                'appointment_time_slot_id',
                $slot->id
            )
            ->firstOrFail();

        $this->actingAs(
            $owner
        )
            ->get(
                route(
                    'patient.appointments.show',
                    $appointment
                )
            )
            ->assertOk();
    }

    public function test_booking_outside_booking_window_is_rejected(): void
    {
        [, $slot] =
            $this->createSlot(
                Appointment::BOOKING_ADVANCE_DAYS
                    +
                    1
            );

        $user =
            $this->patientUser();

        $this->actingAs(
            $user
        )
            ->post(
                route(
                    'patient.appointments.store'
                ),
                $this->bookingData(
                    $slot->id
                )
            )
            ->assertSessionHasErrors(
                'appointment_time_slot_id'
            );

        $this->assertSame(
            0,
            Appointment::query()
                ->where(
                    'patient_id',
                    $user->patient->id
                )
                ->where(
                    'appointment_time_slot_id',
                    $slot->id
                )
                ->count()
        );
    }

    public function test_inactive_slot_is_rejected(): void
    {
        [, $slot] =
            $this->createSlot(
                slotStatus: 'INACTIVE'
            );

        $user =
            $this->patientUser();

        $this->actingAs(
            $user
        )
            ->post(
                route(
                    'patient.appointments.store'
                ),
                $this->bookingData(
                    $slot->id
                )
            )
            ->assertSessionHasErrors(
                'appointment_time_slot_id'
            );

        $this->assertSame(
            0,
            Appointment::query()
                ->where(
                    'appointment_time_slot_id',
                    $slot->id
                )
                ->count()
        );
    }

    public function test_inactive_schedule_is_rejected(): void
    {
        [, $slot] =
            $this->createSlot(
                scheduleStatus: 'INACTIVE'
            );

        $user =
            $this->patientUser();

        $this->actingAs(
            $user
        )
            ->post(
                route(
                    'patient.appointments.store'
                ),
                $this->bookingData(
                    $slot->id
                )
            )
            ->assertSessionHasErrors(
                'appointment_time_slot_id'
            );

        $this->assertSame(
            0,
            Appointment::query()
                ->where(
                    'appointment_time_slot_id',
                    $slot->id
                )
                ->count()
        );
    }

    public function test_inactive_doctor_is_rejected(): void
    {
        [, $slot, $doctor] =
            $this->createSlot();

        $doctor->update([
            'status' => 'INACTIVE',
        ]);

        $user =
            $this->patientUser();

        $this->actingAs(
            $user
        )
            ->post(
                route(
                    'patient.appointments.store'
                ),
                $this->bookingData(
                    $slot->id
                )
            )
            ->assertSessionHasErrors(
                'appointment_time_slot_id'
            );

        $this->assertSame(
            0,
            Appointment::query()
                ->where(
                    'appointment_time_slot_id',
                    $slot->id
                )
                ->count()
        );
    }

    public function test_inactive_specialty_is_rejected(): void
    {
        [, $slot,, $specialty] =
            $this->createSlot();

        $specialty->update([
            'status' => 'INACTIVE',
        ]);

        $user =
            $this->patientUser();

        $this->actingAs(
            $user
        )
            ->post(
                route(
                    'patient.appointments.store'
                ),
                $this->bookingData(
                    $slot->id
                )
            )
            ->assertSessionHasErrors(
                'appointment_time_slot_id'
            );

        $this->assertSame(
            0,
            Appointment::query()
                ->where(
                    'appointment_time_slot_id',
                    $slot->id
                )
                ->count()
        );
    }

    public function test_inactive_room_is_rejected(): void
    {
        [, $slot,,, $room] =
            $this->createSlot();

        $room->update([
            'status' => 'INACTIVE',
        ]);

        $user =
            $this->patientUser();

        $this->actingAs(
            $user
        )
            ->post(
                route(
                    'patient.appointments.store'
                ),
                $this->bookingData(
                    $slot->id
                )
            )
            ->assertSessionHasErrors(
                'appointment_time_slot_id'
            );

        $this->assertSame(
            0,
            Appointment::query()
                ->where(
                    'appointment_time_slot_id',
                    $slot->id
                )
                ->count()
        );
    }

    public function test_reason_for_visit_cannot_be_only_numbers(): void
    {
        [, $slot] =
            $this->createSlot();

        $user =
            $this->patientUser();

        $this->actingAs(
            $user
        )
            ->post(
                route(
                    'patient.appointments.store'
                ),
                [
                    'appointment_time_slot_id'
                    => $slot->id,

                    'reason_for_visit'
                    => '333444555',
                ]
            )
            ->assertSessionHasErrors(
                'reason_for_visit'
            );

        $this->assertSame(
            0,
            Appointment::query()
                ->where(
                    'patient_id',
                    $user->patient->id
                )
                ->where(
                    'appointment_time_slot_id',
                    $slot->id
                )
                ->count()
        );
    }

    public function test_reason_for_visit_cannot_be_only_special_characters(): void
    {
        [, $slot] =
            $this->createSlot();

        $user =
            $this->patientUser();

        $this->actingAs(
            $user
        )
            ->post(
                route(
                    'patient.appointments.store'
                ),
                [
                    'appointment_time_slot_id'
                    => $slot->id,

                    'reason_for_visit'
                    => '!!!@@@###',
                ]
            )
            ->assertSessionHasErrors(
                'reason_for_visit'
            );

        $this->assertSame(
            0,
            Appointment::query()
                ->where(
                    'appointment_time_slot_id',
                    $slot->id
                )
                ->count()
        );
    }
    public function test_booking_without_confirmation_is_rejected(): void
    {
        [, $slot] = $this->createSlot();
        $user = $this->patientUser();
        $this->actingAs($user)
            ->post(
                route('patient.appointments.store'),
                [
                    'appointment_time_slot_id' => $slot->id,
                    'reason_for_visit' => 'Đau đầu và sốt.',
                ]
            )
            ->assertSessionHasErrors('confirmed_information');
        $this->assertDatabaseMissing('appointments', [
            'patient_id' => $user->patient->id,
            'appointment_time_slot_id' => $slot->id,
        ]);
    }}
