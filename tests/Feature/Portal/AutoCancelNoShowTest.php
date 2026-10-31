<?php

namespace Tests\Feature\Portal;

use App\Models\Appointment;
use App\Models\AppointmentStatus;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AutoCancelNoShowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(
            now()
                ->setDate(2026, 10, 16)
                ->setTime(12, 0, 0)
        );

        $this->seed(DatabaseSeeder::class);

        config()->set(
            'appointments_auto_cancel.enabled',
            true
        );

        config()->set(
            'appointments_auto_cancel.grace_minutes',
            30
        );
    }

    private function bookedFixture(
        string $code = 'LH000006'
    ): Appointment {
        $appointment = Appointment::query()
            ->where('appointment_code', $code)
            ->firstOrFail();

        $appointment->update([
            'appointment_status_id' =>
            AppointmentStatus::query()
                ->where('name', 'BOOKED')
                ->value('id'),

            'cancelled_at' => null,
            'cancellation_reason' => null,
        ]);

        return $appointment->fresh();
    }

    public function test_expired_booked_appointment_is_cancelled_and_audited(): void
    {
        $appointment = $this->bookedFixture();

        $this->assertFalse(
            $appointment->medicalVisit()->exists()
        );

        $this->artisan(
            'appointments:expire-unattended'
        )->assertExitCode(0);

        $appointment->refresh();

        $this->assertSame(
            'CANCELLED',
            $appointment->appointmentStatus->name
        );

        $this->assertStringStartsWith(
            '[AUTO_NO_SHOW]',
            (string) $appointment->cancellation_reason
        );

        $this->assertNotNull(
            $appointment->cancelled_at
        );

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'entity_type' => 'Appointment',
                'entity_id' => $appointment->id,
                'action' => 'AUTO_CANCEL_NO_SHOW',
            ]
        );
    }

    public function test_appointment_with_medical_visit_is_not_cancelled(): void
    {
        $appointment = $this->bookedFixture(
            'LH000001'
        );

        $this->assertTrue(
            $appointment->medicalVisit()->exists()
        );

        $this->artisan(
            'appointments:expire-unattended'
        )->assertExitCode(0);

        $this->assertSame(
            'BOOKED',
            $appointment->fresh()
                ->appointmentStatus->name
        );
    }

    public function test_dry_run_does_not_write(): void
    {
        $appointment = $this->bookedFixture();

        $this->artisan(
            'appointments:expire-unattended',
            ['--dry-run' => true]
        )->assertExitCode(0);

        $this->assertSame(
            'BOOKED',
            $appointment->fresh()
                ->appointmentStatus->name
        );

        $this->assertSame(
            0,
            DB::table('audit_logs')
                ->where(
                    'entity_id',
                    $appointment->id
                )
                ->where(
                    'action',
                    'AUTO_CANCEL_NO_SHOW'
                )
                ->count()
        );
    }

    public function test_not_yet_past_grace_period_is_not_cancelled(): void
    {
        $this->travelTo(
            now()
                ->setDate(2026, 10, 15)
                ->setTime(9, 25, 0)
        );

        $appointment = $this->bookedFixture();

        $this->artisan(
            'appointments:expire-unattended'
        )->assertExitCode(0);

        $this->assertSame(
            'BOOKED',
            $appointment->fresh()
                ->appointmentStatus->name
        );
    }

    public function test_running_twice_does_not_duplicate_audit(): void
    {
        $appointment = $this->bookedFixture();

        $this->artisan(
            'appointments:expire-unattended'
        )->assertExitCode(0);

        $this->artisan(
            'appointments:expire-unattended'
        )->assertExitCode(0);

        $this->assertSame(
            1,
            DB::table('audit_logs')
                ->where(
                    'entity_id',
                    $appointment->id
                )
                ->where(
                    'action',
                    'AUTO_CANCEL_NO_SHOW'
                )
                ->count()
        );
    }
}
