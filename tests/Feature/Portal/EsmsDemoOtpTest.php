<?php

namespace Tests\Feature\Portal;

use App\Models\PatientPasswordResetChallenge;
use App\Models\User;
use App\Services\EsmsDemoPatientOtpSender;
use App\Services\PatientPasswordResetService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EsmsDemoOtpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        config(['portal.esms.enabled' => true, 'portal.esms.sandbox' => false,
            'portal.esms.api_key' => 'test-api-key', 'portal.esms.secret_key' => 'test-secret-key',
            'portal.demo_otp_phone' => '0902000001']);
    }

    public function test_demo_account_can_reset_with_a_mocked_esms_response(): void
    {
        $otp = null;
        Http::fake(function ($request) use (&$otp) {
            preg_match('/^([0-9]{6}) la ma xac minh/', $request['Content'], $matches);
            $otp = $matches[1] ?? null;
            return Http::response(['CodeResult' => '100'], 200);
        });
        $user = User::where('phone', '0902000001')->firstOrFail();
        $this->post(route('patient.password.send'), ['phone' => $user->phone])->assertRedirect(route('patient.password.verify'));
        $this->assertNotNull($otp);
        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && $request->url() === 'https://rest.esms.vn/MainService.svc/json/SendMultipleMessage_V4_post_json/'
            && $request['Brandname'] === 'Baotrixemay' && $request['Sandbox'] === '0');
        $challenge = PatientPasswordResetChallenge::where('user_id', $user->id)->firstOrFail();
        $this->assertNotSame($otp, $challenge->otp_hash);
        $this->assertTrue(Hash::check($otp, $challenge->otp_hash));
        $this->get(route('patient.password.verify'))->assertOk()->assertDontSee($otp);
        $this->post(route('patient.password.verify.store'), ['otp' => $otp])->assertRedirect(route('patient.password.reset'));
        $this->post(route('patient.password.reset.store'), ['password' => 'Moi@12345', 'password_confirmation' => 'Moi@12345'])->assertRedirect(route('patient.login'));
        $this->assertTrue(Hash::check('Moi@12345', $user->fresh()->password));
    }

    public function test_sandbox_never_sends_a_live_otp_or_grants_reset(): void
    {
        config(['portal.esms.sandbox' => true]);
        Http::fake(['*' => Http::response(['CodeResult' => '100'], 200)]);
        $this->from(route('patient.password.request'))->post(route('patient.password.send'), ['phone' => '0902000001'])
            ->assertRedirect(route('patient.password.request'))->assertSessionMissing('patient_reset_user_id');
        Http::assertNothingSent();
        $this->assertDatabaseCount('patient_password_reset_challenges', 0);
    }

    public function test_sandbox_command_tests_configuration_without_granting_access(): void
    {
        $this->app['env'] = 'local';
        Http::fake(['*' => Http::response(['CodeResult' => '100'], 200)]);
        $this->artisan('esms:check-sandbox')->assertSuccessful();
        Http::assertSent(fn ($request) => $request['Sandbox'] === '1' && $request['Brandname'] === 'Baotrixemay');
        $this->assertDatabaseCount('patient_password_reset_challenges', 0);
    }

    public function test_provider_failure_or_timeout_does_not_leave_valid_challenge(): void
    {
        foreach (['rejected', 'timeout'] as $failure) {
            Http::fake(fn () => $failure === 'rejected' ? Http::response(['CodeResult' => '101'], 200) : throw new ConnectionException('test timeout'));
            $user = User::where('phone', '0902000001')->firstOrFail();
            $this->assertFalse(app(PatientPasswordResetService::class)->request($user));
            $challenge = PatientPasswordResetChallenge::where('user_id', $user->id)->firstOrFail();
            $this->assertTrue($challenge->expires_at->isPast());
            $this->assertNotNull($challenge->used_at);
        }
    }

    public function test_other_numbers_and_production_cannot_trigger_esms(): void
    {
        Http::fake(['*' => Http::response(['CodeResult' => '100'], 200)]);
        $sender = app(EsmsDemoPatientOtpSender::class);
        $user = User::where('phone', '0902000001')->firstOrFail();
        config(['portal.demo_otp_phone' => '0909999999']);
        $this->assertFalse($sender->send($user, '123456'));
        config(['portal.demo_otp_phone' => $user->phone]);
        $this->app['env'] = 'production';
        $this->assertFalse($sender->send($user, '123456'));
        Http::assertNothingSent();
    }

    public function test_missing_otp_table_fails_safely_before_sending_sms(): void
    {
        Http::fake(['*' => Http::response(['CodeResult' => '100'], 200)]);
        Schema::drop('patient_password_reset_challenges');
        $this->from(route('patient.password.request'))->post(route('patient.password.send'), ['phone' => '0902000001'])
            ->assertRedirect(route('patient.password.request'))->assertSessionHasErrors('phone');
        Http::assertNothingSent();
    }

    public function test_missing_key_fails_without_contacting_esms(): void
    {
        config(['portal.esms.secret_key' => '']);
        Http::fake(['*' => Http::response(['CodeResult' => '100'], 200)]);
        $user = User::where('phone', '0902000001')->firstOrFail();
        $this->assertFalse(app(PatientPasswordResetService::class)->request($user));
        Http::assertNothingSent();
        $this->assertNotNull(PatientPasswordResetChallenge::where('user_id', $user->id)->firstOrFail()->used_at);
    }
}
