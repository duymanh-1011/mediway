<?php

namespace Tests\Feature\Portal;

use App\Models\Appointment;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifications_only_include_current_patients_appointments_and_support_category_filter(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $patient = User::where('business_code', 'BN000001')->firstOrFail();
        $other = User::where('business_code', 'BN000002')->firstOrFail();
        $own = Appointment::where('patient_id', $patient->patient->id)->orderByDesc('updated_at')->firstOrFail();
        $others = Appointment::where('patient_id', $other->patient->id)->orderByDesc('updated_at')->firstOrFail();

        $this->actingAs($patient)->get(route('patient.notifications', ['danh-muc' => 'lich-hen']))
            ->assertOk()->assertSee($own->appointment_code)->assertDontSee($others->appointment_code);
        $this->get(route('patient.notifications', ['danh-muc' => 'he-thong']))
            ->assertOk()->assertDontSee($own->appointment_code);
        $this->actingAs($other)->get(route('patient.notifications', ['danh-muc' => 'lich-hen']))
            ->assertOk()->assertSee($others->appointment_code)->assertDontSee($own->appointment_code);
    }
}
