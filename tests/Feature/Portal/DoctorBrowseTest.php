<?php

namespace Tests\Feature\Portal;

use App\Models\Appointment;
use App\Models\AppointmentTimeSlot;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\ExaminationRoom;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class DoctorBrowseTest extends TestCase
{
    use RefreshDatabase;

    public function test_unavailable_slot_redirect_explains_why_booking_failed(): void
    {
        $doctor = $this->activeDoctors(1)->firstOrFail();
        $slot = $this->createSlot($this->createSchedule($doctor), '09:00', '09:30', 1, 'FULL');

        $this->followingRedirects()->get(route('patient.appointments.create', $slot))
            ->assertOk()->assertSeeText('Khung giờ đã chọn không còn nhận lịch. Vui lòng chọn khung giờ khác.');
    }

    public function test_slots_outside_their_schedule_are_not_advertised(): void
    {
        $doctor = $this->activeDoctors(1)->firstOrFail();
        $schedule = $this->createSchedule($doctor);
        $valid = $this->createSlot($schedule, '09:00', '09:30');
        $invalid = $this->createSlot($schedule, '10:45', '11:30');

        foreach (['patient.doctors.index', 'patient.doctors.show'] as $route) {
            $response = $this->get(route($route, $route === 'patient.doctors.show' ? $doctor : []));
            $response->assertOk();
            $slots = $response->viewData('schedules')->firstWhere('id', $schedule->id)->appointmentTimeSlots;
            $this->assertTrue($slots->contains('id', $valid->id));
            $this->assertFalse($slots->contains('id', $invalid->id));
        }

        $this->get(route('patient.appointments.create', $invalid))
            ->assertRedirect(route('patient.doctors.index'))
            ->assertSessionHasErrors('appointment_time_slot_id');
    }

    public function test_booking_list_shows_the_room_for_each_shift_without_registration_counts(): void
    {
        $doctor = $this->activeDoctors(1)->firstOrFail();
        $morning = $this->createSchedule($doctor);
        $morning->examinationRoom->update(['name' => 'Phòng khám A', 'room_number' => '101', 'building' => 'Tòa A']);
        $firstSlot = $this->createSlot($morning);
        $otherRoom = $morning->examinationRoom->replicate();
        $otherRoom->fill(['room_code' => 'TEST-02', 'name' => 'Phòng khám B', 'room_number' => '202', 'building' => 'Tòa B'])->save();
        $afternoon = $this->createSchedule($doctor, 1, 'ACTIVE', '17:30', '19:00');
        $afternoon->update(['shift_code' => 'AFTERNOON', 'examination_room_id' => $otherRoom->id]);
        $secondSlot = $this->createSlot($afternoon, '17:30', '18:00');

        $this->get(route('patient.doctors.index', ['doctor_id' => $doctor->id]))->assertOk()
            ->assertSee('data-slot-room="Phòng khám A"', false)->assertSee(route('patient.appointments.create', $firstSlot))
            ->assertSee('data-slot-room="Phòng khám B"', false)->assertSee(route('patient.appointments.create', $secondSlot))
            ->assertSeeText('Phòng 101 · Tòa A')->assertSeeText('Phòng 202 · Tòa B')
            ->assertDontSeeText('Đã đăng ký 0')->assertDontSee('data-day-status', false)
            ->assertDontSeeText('khung giờ còn đặt được');
    }

    protected function setUp(): void
    {
        parent::setUp();

        /*
        |--------------------------------------------------------------------------
        | Cố định thời gian test
        |--------------------------------------------------------------------------
        |
        | Không để test phụ thuộc ngày thật của máy.
        |
        */

        $this->travelTo(
            now()
                ->setDate(2026, 10, 27)
                ->setTime(8, 0, 0)
        );

        $this->seed(DatabaseSeeder::class);        // M06_TEST_PROFILE_FIXTURE_20261030
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

        /*
        |--------------------------------------------------------------------------
        | M05 là chức năng Patient Portal
        |--------------------------------------------------------------------------
        */

        $this->actingAs(
            $this->patientUser('BN000001')
        );
    }

    private function patientUser(
        string $patientCode = 'BN000001'
    ): User {
        return User::query()
            ->where(
                'business_code',
                $patientCode
            )
            ->firstOrFail();
    }

    private function activeDoctors(
        int $count = 2
    ): Collection {
        return Doctor::query()
            ->with([
                'specialty',
            ])
            ->where(
                'status',
                'ACTIVE'
            )
            ->whereHas(
                'specialty',
                fn($query) =>
                $query->where(
                    'status',
                    'ACTIVE'
                )
            )
            ->orderBy(
                'id'
            )
            ->limit(
                $count
            )
            ->get();
    }

    private function activeRoom(): ExaminationRoom
    {
        return ExaminationRoom::query()
            ->where(
                'status',
                'ACTIVE'
            )
            ->orderBy(
                'id'
            )
            ->firstOrFail();
    }

    private function createSchedule(
        Doctor $doctor,
        int $daysFromToday = 1,
        string $status = 'ACTIVE',
        string $startTime = '09:00',
        string $endTime = '11:00'
    ): DoctorSchedule {
        return DoctorSchedule::create([
            'doctor_id' =>
            $doctor->id,

            'specialty_id' =>
            $doctor->specialty_id,

            'examination_room_id' =>
            $this->activeRoom()->id,

            'work_date' =>
            today()
                ->addDays(
                    $daysFromToday
                )
                ->toDateString(),

            'start_time' =>
            $startTime,

            'end_time' =>
            $endTime,

            'status' =>
            $status,
        ]);
    }

    private function createSlot(
        DoctorSchedule $schedule,
        string $startTime = '09:00',
        string $endTime = '09:30',
        int $maxPatients = 2,
        string $status = 'ACTIVE'
    ): AppointmentTimeSlot {
        return $schedule
            ->appointmentTimeSlots()
            ->create([
                'start_time' =>
                $startTime,

                'end_time' =>
                $endTime,

                'max_patients' =>
                $maxPatients,

                'status' =>
                $status,
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | M05.1 - Patient có thể mở trang tra cứu
    |--------------------------------------------------------------------------
    */

    public function test_active_patient_can_open_doctor_browse_page(): void
    {
        $doctor =
            $this
            ->activeDoctors(1)
            ->firstOrFail();

        $schedule =
            $this->createSchedule(
                $doctor,
                1
            );

        $this->createSlot(
            $schedule
        );

        $response =
            $this->get(
                route(
                    'patient.doctors.index'
                )
            );

        $response
            ->assertOk()
            ->assertSee('href="'.route('patient.doctors.show', $doctor).'"', false)
            ->assertViewIs(
                'portal.doctors.index'
            );

        $scheduleIds =
            $response
            ->viewData(
                'schedules'
            )
            ->pluck(
                'id'
            )
            ->all();

        $this->assertContains(
            $schedule->id,
            $scheduleIds
        );
    }

    /*
    |--------------------------------------------------------------------------
    | M05.1 - Lọc chuyên khoa
    |--------------------------------------------------------------------------
    */

    public function test_patient_can_filter_by_specialty(): void
    {
        $doctors =
            $this->activeDoctors(2);

        $this->assertCount(
            2,
            $doctors
        );

        $firstDoctor =
            $doctors->get(0);

        $secondDoctor =
            $doctors->get(1);

        $firstSchedule =
            $this->createSchedule(
                $firstDoctor,
                1
            );

        $secondSchedule =
            $this->createSchedule(
                $secondDoctor,
                2
            );

        $this->createSlot(
            $firstSchedule
        );

        $this->createSlot(
            $secondSchedule
        );

        $response =
            $this->get(
                route(
                    'patient.doctors.index',
                    [
                        'specialty_id' =>
                        $firstDoctor
                            ->specialty_id,
                    ]
                )
            );

        $response->assertOk();

        $schedules =
            $response->viewData(
                'schedules'
            );

        $this->assertTrue(
            $schedules->isNotEmpty()
        );

        $this->assertTrue(
            $schedules->every(
                fn($schedule) =>
                (int) $schedule
                    ->specialty_id
                    ===
                    (int) $firstDoctor
                        ->specialty_id
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | M05.1 - Lọc bác sĩ
    |--------------------------------------------------------------------------
    */

    public function test_patient_can_filter_by_doctor(): void
    {
        $doctors =
            $this->activeDoctors(2);

        $this->assertCount(
            2,
            $doctors
        );

        $firstDoctor =
            $doctors->get(0);

        $secondDoctor =
            $doctors->get(1);

        $firstSchedule =
            $this->createSchedule(
                $firstDoctor,
                1
            );

        $secondSchedule =
            $this->createSchedule(
                $secondDoctor,
                2
            );

        $this->createSlot(
            $firstSchedule
        );

        $this->createSlot(
            $secondSchedule
        );

        $response =
            $this->get(
                route(
                    'patient.doctors.index',
                    [
                        'doctor_id' =>
                        $firstDoctor->id,
                    ]
                )
            );

        $response->assertOk();

        $scheduleIds =
            $response
            ->viewData(
                'schedules'
            )
            ->pluck(
                'id'
            )
            ->all();

        $this->assertContains(
            $firstSchedule->id,
            $scheduleIds
        );

        $this->assertNotContains(
            $secondSchedule->id,
            $scheduleIds
        );
    }

    /*
    |--------------------------------------------------------------------------
    | M05.1 - Lọc ngày khám
    |--------------------------------------------------------------------------
    */

    public function test_patient_can_filter_by_date(): void
    {
        $doctor =
            $this
            ->activeDoctors(1)
            ->firstOrFail();

        $firstSchedule =
            $this->createSchedule(
                $doctor,
                1,
                'ACTIVE',
                '09:00',
                '11:00'
            );

        $secondSchedule =
            $this->createSchedule(
                $doctor,
                2,
                'ACTIVE',
                '13:00',
                '15:00'
            );

        $this->createSlot(
            $firstSchedule
        );

        $this->createSlot(
            $secondSchedule,
            '13:00',
            '13:30'
        );

        $date =
            today()
            ->addDay()
            ->toDateString();

        $response =
            $this->get(
                route(
                    'patient.doctors.index',
                    [
                        'date' => $date,
                    ]
                )
            );

        $response->assertOk();

        $scheduleIds =
            $response
            ->viewData(
                'schedules'
            )
            ->pluck(
                'id'
            )
            ->all();

        $this->assertContains(
            $firstSchedule->id,
            $scheduleIds
        );

        $this->assertNotContains(
            $secondSchedule->id,
            $scheduleIds
        );

        $this->assertSame(
            $date,
            $response->viewData(
                'date'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | M05.2 - Không hiển thị slot đã qua giờ
    |--------------------------------------------------------------------------
    */

    public function test_browse_only_offers_afternoon_slots_today_before_the_cutoff(): void
    {
        $doctor = $this->activeDoctors(1)->firstOrFail();
        $schedule = $this->createSchedule($doctor, 0, 'ACTIVE', '06:00', '17:00');
        $past = $this->createSlot($schedule, '06:30', '07:00');
        $morning = $this->createSlot($schedule, '09:00', '09:30');
        $afternoon = $this->createSlot($schedule, '13:30', '14:00');

        $response = $this->get(route('patient.doctors.index'))->assertOk();
        $returned = $response->viewData('schedules')->firstWhere('id', $schedule->id);
        $this->assertNotNull($returned);
        $this->assertSame([$afternoon->id], $returned->appointmentTimeSlots->modelKeys());
        $response->assertDontSee(route('patient.appointments.create', $past))
            ->assertDontSee(route('patient.appointments.create', $morning))
            ->assertSee(route('patient.appointments.create', $afternoon));
    }

    /*
    |--------------------------------------------------------------------------
    | M05.2 - Slot INACTIVE không được hiển thị
    |--------------------------------------------------------------------------
    */

    public function test_inactive_time_slot_is_not_exposed(): void
    {
        $doctor =
            $this
            ->activeDoctors(1)
            ->firstOrFail();

        $schedule =
            $this->createSchedule(
                $doctor,
                1
            );

        $activeSlot =
            $this->createSlot(
                $schedule,
                '09:00',
                '09:30',
                2,
                'ACTIVE'
            );

        $inactiveSlot =
            $this->createSlot(
                $schedule,
                '09:30',
                '10:00',
                2,
                'INACTIVE'
            );

        $response =
            $this->get(
                route(
                    'patient.doctors.index'
                )
            );

        $response->assertOk();

        $returnedSchedule =
            $response
            ->viewData(
                'schedules'
            )
            ->firstWhere(
                'id',
                $schedule->id
            );

        $this->assertNotNull(
            $returnedSchedule
        );

        $slotIds =
            $returnedSchedule
            ->appointmentTimeSlots
            ->pluck(
                'id'
            )
            ->all();

        $this->assertContains(
            $activeSlot->id,
            $slotIds
        );

        $this->assertNotContains(
            $inactiveSlot->id,
            $slotIds
        );
    }

    /*
    |--------------------------------------------------------------------------
    | M05.2 - Schedule INACTIVE không được hiển thị
    |--------------------------------------------------------------------------
    */

    public function test_inactive_schedule_is_not_exposed(): void
    {
        $doctor =
            $this
            ->activeDoctors(1)
            ->firstOrFail();

        $activeSchedule =
            $this->createSchedule(
                $doctor,
                1,
                'ACTIVE',
                '09:00',
                '11:00'
            );

        $inactiveSchedule =
            $this->createSchedule(
                $doctor,
                2,
                'INACTIVE',
                '13:00',
                '15:00'
            );

        $this->createSlot(
            $activeSchedule
        );

        $this->createSlot(
            $inactiveSchedule,
            '13:00',
            '13:30'
        );

        $response =
            $this->get(
                route(
                    'patient.doctors.index'
                )
            );

        $response->assertOk();

        $scheduleIds =
            $response
            ->viewData(
                'schedules'
            )
            ->pluck(
                'id'
            )
            ->all();

        $this->assertContains(
            $activeSchedule->id,
            $scheduleIds
        );

        $this->assertNotContains(
            $inactiveSchedule->id,
            $scheduleIds
        );
    }

    /*
    |--------------------------------------------------------------------------
    | M05.2 - Bác sĩ INACTIVE không được lộ
    |--------------------------------------------------------------------------
    */

    public function test_inactive_doctor_schedule_is_not_exposed(): void
    {
        $doctor =
            $this
            ->activeDoctors(1)
            ->firstOrFail();

        $schedule =
            $this->createSchedule(
                $doctor,
                1
            );

        $this->createSlot(
            $schedule
        );

        $doctor->update([
            'status' => 'INACTIVE',
        ]);

        $response =
            $this->get(
                route(
                    'patient.doctors.index'
                )
            );

        $response->assertOk();

        $scheduleIds =
            $response
            ->viewData(
                'schedules'
            )
            ->pluck(
                'id'
            )
            ->all();

        $this->assertNotContains(
            $schedule->id,
            $scheduleIds
        );

        $doctorIds =
            $response
            ->viewData(
                'doctors'
            )
            ->pluck(
                'id'
            )
            ->all();

        $this->assertNotContains(
            $doctor->id,
            $doctorIds
        );
    }

    /*
    |--------------------------------------------------------------------------
    | M05.2 - Slot hết chỗ không được hiển thị
    |--------------------------------------------------------------------------
    */

    public function test_browse_hides_registration_count_and_keeps_the_slot_bookable(): void
    {
        $doctor = $this->activeDoctors(1)->firstOrFail();
        $schedule = $this->createSchedule($doctor, 1);
        $slot = $this->createSlot($schedule, '09:00', '09:30', 1);

        $this->actingAs($this->patientUser('BN000002'))
            ->post(route('patient.appointments.store'), [
                'appointment_time_slot_id' => $slot->id,
                'reason_for_visit' => 'Đau họng và ho nhiều.',
                'confirmed_information' => '1',
            ])->assertSessionHasNoErrors();

        $response = $this->actingAs($this->patientUser('BN000001'))
            ->get(route('patient.doctors.index'))->assertOk();
        $returned = $response->viewData('schedules')->firstWhere('id', $schedule->id);
        $this->assertEquals(1, $returned->appointmentTimeSlots->firstWhere('id', $slot->id)->booked_count);
        $response->assertDontSeeText('Đã đăng ký')->assertSee(route('patient.appointments.create', $slot));
    }

    /*
    |--------------------------------------------------------------------------
    | M05 Request - Validate ngày
    |--------------------------------------------------------------------------
    */

    public function test_date_filter_rejects_past_and_out_of_booking_range_dates(): void
    {
        $indexRoute =
            route(
                'patient.doctors.index'
            );

        /*
         * Ngày quá khứ.
         */

        $this
            ->from(
                $indexRoute
            )
            ->get(
                route(
                    'patient.doctors.index',
                    [
                        'date' =>
                        today()
                            ->subDay()
                            ->toDateString(),
                    ]
                )
            )
            ->assertRedirect(
                $indexRoute
            )
            ->assertSessionHasErrors(
                'date'
            );

        /*
         * Vượt quá số ngày được phép đặt trước.
         */

        $this
            ->from(
                $indexRoute
            )
            ->get(
                route(
                    'patient.doctors.index',
                    [
                        'date' =>
                        today()
                            ->addDays(
                                Appointment::BOOKING_ADVANCE_DAYS
                                    +
                                    1
                            )
                            ->toDateString(),
                    ]
                )
            )
            ->assertRedirect(
                $indexRoute
            )
            ->assertSessionHasErrors(
                'date'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | M05 Request - Validate specialty / doctor
    |--------------------------------------------------------------------------
    */

    public function test_invalid_specialty_and_doctor_filters_are_rejected(): void
    {
        $indexRoute =
            route(
                'patient.doctors.index'
            );

        $this
            ->from(
                $indexRoute
            )
            ->get(
                route(
                    'patient.doctors.index',
                    [
                        'specialty_id' =>
                        999999999,
                    ]
                )
            )
            ->assertRedirect(
                $indexRoute
            )
            ->assertSessionHasErrors(
                'specialty_id'
            );

        $this
            ->from(
                $indexRoute
            )
            ->get(
                route(
                    'patient.doctors.index',
                    [
                        'doctor_id' =>
                        999999999,
                    ]
                )
            )
            ->assertRedirect(
                $indexRoute
            )
            ->assertSessionHasErrors(
                'doctor_id'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Không cho tài khoản Admin dùng Patient Portal
    |--------------------------------------------------------------------------
    */

    public function test_non_patient_account_cannot_use_doctor_browse_page(): void
    {
        $admin =
            User::query()
            ->where(
                'business_code',
                'QT000001'
            )
            ->firstOrFail();

        $this->actingAs(
            $admin
        );

        $this
            ->get(
                route(
                    'patient.doctors.index'
                )
            )
            ->assertRedirect(
                route(
                    'patient.login'
                )
            );
    }
}
