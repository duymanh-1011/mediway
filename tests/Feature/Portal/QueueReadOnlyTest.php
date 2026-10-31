<?php

namespace Tests\Feature\Portal;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\ExaminationRoom;
use App\Models\ExaminationRoomQueue;
use App\Models\MedicalVisit;
use App\Models\QueueStatus;
use App\Models\Specialty;
use App\Models\User;
use App\Models\VisitStatus;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class QueueReadOnlyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

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

    public function test_patient_can_open_schedule_and_queue_page(): void
    {
        $this->actingAs(
            $this->patientUser()
        )
            ->get(
                route(
                    'patient.queues.index'
                )
            )
            ->assertOk()
            ->assertSee(
                'Lịch khám và hàng đợi'
            );
    }

    public function test_patient_only_sees_own_appointments(): void
    {
        $user =
            $this->patientUser(
                'BN000001'
            );

        $ownAppointment =
            Appointment::query()
            ->where(
                'patient_id',
                $user->patient->id
            )
            ->firstOrFail();

        $otherAppointment =
            Appointment::query()
            ->where(
                'patient_id',
                '!=',
                $user->patient->id
            )
            ->firstOrFail();

        $response =
            $this->actingAs(
                $user
            )
            ->get(
                route(
                    'patient.queues.index'
                )
            );

        $response
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

    public function test_patient_only_sees_own_queue_records(): void
    {
        $user =
            $this->patientUser(
                'BN000001'
            );

        $ownQueue =
            ExaminationRoomQueue::query()
            ->whereHas(
                'medicalVisit',
                fn($query) =>
                $query->where(
                    'patient_id',
                    $user->patient->id
                )
            )
            ->with(
                'medicalVisit'
            )
            ->firstOrFail();

        $otherQueue =
            ExaminationRoomQueue::query()
            ->whereHas(
                'medicalVisit',
                fn($query) =>
                $query->where(
                    'patient_id',
                    '!=',
                    $user->patient->id
                )
            )
            ->with(
                'medicalVisit'
            )
            ->firstOrFail();

        $response =
            $this->actingAs(
                $user
            )
            ->get(
                route(
                    'patient.queues.index'
                )
            );

        $response
            ->assertOk()
            ->assertSee(
                $ownQueue
                    ->medicalVisit
                    ->visit_code
            )
            ->assertDontSee(
                $otherQueue
                    ->medicalVisit
                    ->visit_code
            );
    }

    /*
    |--------------------------------------------------------------------------
    | MedicalVisit không có Appointment vẫn phải hiển thị đúng
    |--------------------------------------------------------------------------
    */

    public function test_queue_uses_medical_visit_context_even_without_appointment(): void
    {
        $user =
            $this->patientUser(
                'BN000001'
            );

        $doctor =
            Doctor::query()
            ->where(
                'doctor_code',
                'BS000001'
            )
            ->firstOrFail();

        $specialty =
            Specialty::query()
            ->where(
                'specialty_code',
                'CKTMH'
            )
            ->firstOrFail();

        $room =
            ExaminationRoom::query()
            ->where(
                'room_code',
                'PKTMH01'
            )
            ->firstOrFail();

        $visitStatus =
            VisitStatus::query()
            ->where(
                'name',
                'WAITING'
            )
            ->firstOrFail();

        $queueStatus =
            QueueStatus::query()
            ->where(
                'name',
                'WAITING'
            )
            ->firstOrFail();

        $visit =
            MedicalVisit::query()
            ->create([
                'visit_code' =>
                'LKPORTALDIRECT',

                'appointment_id' =>
                null,

                'patient_id' =>
                $user->patient->id,

                'doctor_id' =>
                $doctor->id,

                'specialty_id' =>
                $specialty->id,

                'examination_room_id' =>
                $room->id,

                'visit_status_id' =>
                $visitStatus->id,

                'checked_in_at' =>
                now(),

                'reception_source' =>
                'COUNTER',
            ]);

        ExaminationRoomQueue::query()
            ->create([
                'medical_visit_id' =>
                $visit->id,

                'queue_status_id' =>
                $queueStatus->id,

                'queue_number' =>
                99,

                'queue_type' =>
                'INITIAL_EXAM',

                'queued_at' =>
                now(),

                'call_count' =>
                0,
            ]);

        $this->actingAs(
            $user
        )
            ->get(
                route(
                    'patient.queues.index'
                )
            )
            ->assertOk()
            ->assertSee(
                'LKPORTALDIRECT'
            )
            ->assertSee(
                $doctor->full_name
            )
            ->assertSee(
                $specialty->name
            )
            ->assertSee(
                $room->name
            )
            ->assertSee(
                $visitStatus->description
            )
            ->assertSee(
                $queueStatus->description
            )
            ->assertSee(
                'Tiếp nhận tại quầy'
            )
            ->assertSee(
                'Không có lịch hẹn'
            );
    }

    public function test_patient_queue_module_has_no_mutation_routes(): void
    {
        $this->assertFalse(
            Route::has(
                'patient.queues.call'
            )
        );

        $this->assertFalse(
            Route::has(
                'patient.queues.skip'
            )
        );

        $this->assertFalse(
            Route::has(
                'patient.queues.check-in'
            )
        );

        $this->assertFalse(
            Route::has(
                'patient.queues.start-service'
            )
        );

        $this->assertFalse(
            Route::has(
                'patient.queues.complete'
            )
        );
    }

    public function test_non_patient_account_cannot_open_patient_queue_page(): void
    {
        $admin =
            User::query()
            ->where(
                'business_code',
                'QT000001'
            )
            ->firstOrFail();

        $response =
            $this->actingAs(
                $admin
            )
            ->get(
                route(
                    'patient.queues.index'
                )
            );

        $response->assertStatus(
            302
        );

        /*
        |--------------------------------------------------------------------------
        | PatientMiddleware hiện tại redirect sai role về login Patient
        |--------------------------------------------------------------------------
        */

        $this->assertSame(
            route(
                'patient.login'
            ),
            $response
                ->baseResponse
                ->headers
                ->get(
                    'Location'
                )
        );
    }
}
