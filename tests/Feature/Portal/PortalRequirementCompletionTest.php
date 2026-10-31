<?php

namespace Tests\Feature\Portal;

use App\Http\Middleware\PatientMiddleware;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Specialty;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PortalRequirementCompletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(
            Carbon::parse(
                '2026-10-27 08:00:00'
            )
        );

        $this->seed(
            DatabaseSeeder::class
        );

        $this->withoutVite();
    }

    private function patientUser(
        string $code = 'BN000001'
    ): User {
        return User::query()
            ->where(
                'business_code',
                $code
            )
            ->firstOrFail();
    }

    /*
    |--------------------------------------------------------------------------
    | M05.3 - Trang bác sĩ
    |--------------------------------------------------------------------------
    */

    public function test_patient_can_open_active_doctor_profile_without_internal_doctor_code(): void
    {
        $doctor =
            Doctor::query()
            ->with(
                'specialty'
            )
            ->where(
                'doctor_code',
                'BS000001'
            )
            ->firstOrFail();

        $response =
            $this->actingAs(
                $this->patientUser()
            )
            ->get(
                route(
                    'patient.doctors.show',
                    $doctor
                )
            );

        $response
            ->assertOk()
            ->assertSee(
                $doctor->full_name
            )
            ->assertSee(
                $doctor
                    ->specialty
                    ->name
            )
            ->assertDontSee(
                $doctor->doctor_code
            );

        if (
            filled(
                $doctor
                    ->specialty
                    ->pre_visit_instructions
            )
        ) {
            $response->assertSee(
                $doctor
                    ->specialty
                    ->pre_visit_instructions
            );
        }
    }

    public function test_inactive_doctor_profile_is_not_exposed(): void
    {
        $doctor =
            Doctor::query()
            ->where(
                'doctor_code',
                'BS000001'
            )
            ->firstOrFail();

        $doctor->update([
            'status' =>
            'INACTIVE',
        ]);

        $this->actingAs(
            $this->patientUser()
        )
            ->get(
                route(
                    'patient.doctors.show',
                    $doctor
                )
            )
            ->assertNotFound();
    }

    /*
    |--------------------------------------------------------------------------
    | M05.3 - Trang chuyên khoa
    |--------------------------------------------------------------------------
    */

    public function test_patient_can_open_active_specialty_page(): void
    {
        $specialty =
            Specialty::query()
            ->where(
                'specialty_code',
                'CKTMH'
            )
            ->firstOrFail();

        $doctor =
            Doctor::query()
            ->where(
                'doctor_code',
                'BS000001'
            )
            ->firstOrFail();

        $response =
            $this->actingAs(
                $this->patientUser()
            )
            ->get(
                route(
                    'patient.specialties.show',
                    $specialty
                )
            );

        $response
            ->assertOk()
            ->assertSee(
                $specialty->name
            )
            ->assertSee(
                $doctor->full_name
            )
            ->assertDontSee(
                $doctor->doctor_code
            );

        if (
            filled(
                $specialty
                    ->pre_visit_instructions
            )
        ) {
            $response->assertSee(
                $specialty
                    ->pre_visit_instructions
            );
        }
    }

    public function test_inactive_specialty_page_is_not_exposed(): void
    {
        $specialty =
            Specialty::query()
            ->where(
                'specialty_code',
                'CKTMH'
            )
            ->firstOrFail();

        $specialty->update([
            'status' =>
            'INACTIVE',
        ]);

        $this->actingAs(
            $this->patientUser()
        )
            ->get(
                route(
                    'patient.specialties.show',
                    $specialty
                )
            )
            ->assertNotFound();
    }

    /*
    |--------------------------------------------------------------------------
    | M06.3 - Lịch sử lịch hẹn
    |--------------------------------------------------------------------------
    */

    public function test_patient_appointment_history_only_contains_own_appointments(): void
    {
        $user =
            $this->patientUser(
                'BN000001'
            );

        $ownAppointment =
            Appointment::query()
            ->where(
                'patient_id',
                $user
                    ->patient
                    ->id
            )
            ->firstOrFail();

        $otherAppointment =
            Appointment::query()
            ->where(
                'patient_id',
                '!=',
                $user
                    ->patient
                    ->id
            )
            ->firstOrFail();

        $this->actingAs(
            $user
        )
            ->get(
                route(
                    'patient.appointments.index'
                )
            )
            ->assertOk()
            ->assertSee(
                $ownAppointment
                    ->appointment_code
            )
            ->assertDontSee(
                $otherAppointment
                    ->appointment_code
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Security gate - tất cả route Tạo phụ trách
    |--------------------------------------------------------------------------
    */

    public function test_all_tao_routes_are_protected_by_patient_middleware(): void
    {
        $routeNames = [
            'patient.doctors.index',
            'patient.doctors.show',
            'patient.specialties.show',

            'patient.appointments.create',
            'patient.appointments.store',
            'patient.appointments.index',
            'patient.appointments.show',

            'patient.queues.index',

            'patient.payments.index',
        ];

        foreach ($routeNames as $routeName) {
            $route =
                Route::getRoutes()
                ->getByName(
                    $routeName
                );

            $this->assertNotNull(
                $route,
                "Thiếu route {$routeName}."
            );

            $this->assertContains(
                PatientMiddleware::class,
                $route->gatherMiddleware(),
                "Route {$routeName} phải được bảo vệ bởi PatientMiddleware."
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | M07 - chưa có cutoff nên phải tiếp tục disable
    |--------------------------------------------------------------------------
    */

    public function test_cancel_and_reschedule_stay_disabled_until_policy_is_approved(): void
    {
        foreach (
            [
                'patient.appointments.cancel.form',
                'patient.appointments.cancel',
                'patient.appointments.reschedule.form',
                'patient.appointments.reschedule',
            ] as $routeName
        ) {
            $this->assertTrue(
                \Illuminate\Support\Facades\Route::has($routeName)
            );
        }

        config()->set('patient_appointments.self_service_approved', false);

        $policy = app(
            \App\Support\Portal\AppointmentChangePolicy::class
        );

        $this->assertFalse($policy->enabled('cancel'));
        $this->assertFalse($policy->enabled('reschedule'));
    }
}
