<?php

namespace Tests\Feature\Portal;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientSessionCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_pages_are_not_cached_and_logout_blocks_return_access(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $patient = User::where('business_code', 'BN000001')->firstOrFail();

        $this->actingAs($patient)->get(route('patient.home'))
            ->assertOk()->assertHeader('Cache-Control', 'must-revalidate, no-cache, no-store, private')
            ->assertHeader('Pragma', 'no-cache');
        $this->get(route('patient.notifications'))->assertOk()
            ->assertHeader('Cache-Control', 'must-revalidate, no-cache, no-store, private');
        $this->post(route('patient.logout'))->assertRedirect(route('public.home'));
        $this->assertGuest();
        $this->get(route('patient.home'))->assertRedirect(route('patient.login'));
        $this->get(route('patient.notifications'))->assertRedirect(route('patient.login'));
        $this->get(route('public.home'))->assertOk()->assertDontSee($patient->patient->full_name);
    }
}
