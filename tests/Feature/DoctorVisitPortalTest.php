<?php

namespace Tests\Feature;

use App\Models\MedicalVisit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorVisitPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
    }

    public function test_doctor_uses_common_login_and_sees_only_assigned_visits(): void
    {
        $doctor = User::where('business_code', 'BS000001')->firstOrFail();
        $own = MedicalVisit::where('visit_code', 'LK000001')->firstOrFail();
        $other = MedicalVisit::where('visit_code', 'LK000002')->firstOrFail();
        $this->post(route('patient.login.store'), ['phone' => $doctor->phone, 'password' => 'Mediway@123'])
            ->assertRedirect(route('doctor.home'));
        $this->get(route('doctor.home'))->assertOk()->assertSee($own->visit_code)->assertDontSee($other->visit_code);
        $this->get(route('doctor.visits.show', $own->id))->assertOk()->assertSee($own->patient->full_name);
        $this->get(route('doctor.visits.show', $other->id))->assertNotFound();
    }

    public function test_non_doctors_and_inactive_doctors_cannot_access_visits(): void
    {
        $visit = MedicalVisit::where('visit_code', 'LK000001')->firstOrFail();
        $this->get(route('doctor.home'))->assertRedirect(route('patient.login'));
        foreach (['BN000001', 'NV000002', 'QT000001'] as $code) {
            $this->flushSession();
            $this->actingAs(User::where('business_code', $code)->firstOrFail())
                ->get(route('doctor.visits.show', $visit->id))->assertForbidden();
        }
        $doctor = User::where('business_code', 'BS000001')->firstOrFail();
        $doctor->doctor->update(['status' => 'INACTIVE']);
        $this->flushSession();
        $this->actingAs($doctor)->get(route('doctor.home'))->assertForbidden();
    }
}
