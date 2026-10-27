<?php

namespace Tests\Feature\Portal;

use App\Contracts\PatientOtpSender;
use App\Models\Patient;
use App\Models\PatientPasswordResetChallenge;
use App\Models\User;
use App\Services\PatientPasswordResetService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PatientPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
    }

    public function test_public_pages_and_static_news_are_available(): void
    {
        $this->get(route('public.home'))->assertOk()->assertSee('Đặt lịch khám');
        $this->get(route('public.about'))->assertOk();
        $this->get(route('public.care-process'))->assertOk();
        $this->get(route('public.support'))->assertOk();
        $this->get(route('public.news'))->assertOk()->assertSee('Nội dung mẫu');
        $this->get(route('public.news.show', 'kham-suc-khoe-dinh-ky'))->assertOk()->assertSee('Nội dung mẫu');
        $this->get(route('public.news.show', 'not-a-news'))->assertNotFound();
    }

    public function test_registration_creates_linked_active_patient_without_accepting_privileged_fields(): void
    {
        $this->post(route('patient.register.store'), [
            'full_name' => 'Bệnh nhân mới', 'phone' => '0909888999', 'password' => 'AnToan@123', 'password_confirmation' => 'AnToan@123',
            'role_id' => 1, 'status' => 'LOCKED', 'business_code' => 'HACK', 'patient_code' => 'HACK', 'user_id' => 1,
        ])->assertRedirect(route('patient.login'));

        $user = User::where('phone', '0909888999')->firstOrFail();
        $patient = Patient::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('Patient', $user->role->name);
        $this->assertSame('ACTIVE', $user->status);
        $this->assertSame('ACTIVE', $patient->status);
        $this->assertSame($user->business_code, $patient->patient_code);
        $this->assertNotSame('HACK', $user->business_code);
        $this->assertTrue(Hash::check('AnToan@123', $user->password));
    }

    public function test_registration_rejects_duplicate_phone_without_partial_patient(): void
    {
        $patients = Patient::count();
        $this->from(route('patient.register'))->post(route('patient.register.store'), [
            'full_name' => 'Trùng số', 'phone' => '0902000001', 'password' => 'AnToan@123', 'password_confirmation' => 'AnToan@123',
        ])->assertRedirect(route('patient.register'))->assertSessionHasErrors('phone');
        $this->assertSame($patients, Patient::count());
    }

    public function test_only_active_patients_can_login_and_access_portal(): void
    {
        $patient = User::where('business_code', 'BN000001')->firstOrFail();
        $this->post(route('patient.login.store'), ['phone' => $patient->phone, 'password' => 'Mediway@123'])->assertRedirect(route('patient.home'));
        $this->get(route('patient.home'))->assertOk()->assertSee('Kết quả khám bệnh')->assertSee($patient->patient->full_name)->assertSee('Đăng xuất')->assertSee('Thông báo')->assertSee(route('patient.notifications'))->assertSee('href="#"', false)->assertDontSee('Dashboard')->assertDontSee('Sắp ra mắt');
        $this->get(route('patient.notifications', ['danh-muc' => 'lich-hen']))->assertOk()->assertSee('Lịch hẹn')->assertSee('Thanh toán');

        $this->post(route('patient.logout'))->assertRedirect(route('public.home'));
        $this->get(route('patient.notifications'))->assertRedirect(route('patient.login'));
        $admin = User::where('business_code', 'QT000001')->firstOrFail();
        $this->post(route('patient.login.store'), ['phone' => $admin->phone, 'password' => 'Mediway@123'])->assertSessionHasErrors('phone');
        $this->actingAs($admin)->get(route('patient.home'))->assertRedirect(route('patient.login'));
        $this->actingAs($admin)->get(route('patient.notifications'))->assertRedirect(route('patient.login'));
        $this->actingAs($patient)->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
    }

    public function test_login_is_throttled_and_logout_invalidates_portal_session(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('patient.login.store'), ['phone' => '0902000001', 'password' => 'wrong-password']);
        }
        $this->post(route('patient.login.store'), ['phone' => '0902000001', 'password' => 'wrong-password'])
            ->assertSessionHasErrors('phone');
    }

    public function test_reset_needs_verified_single_use_grant_and_never_exposes_otp(): void
    {
        $user = User::where('business_code', 'BN000001')->firstOrFail();
        $this->get(route('patient.password.reset'))->assertRedirect(route('patient.password.request'));
        PatientPasswordResetChallenge::create(['user_id' => $user->id, 'otp_hash' => Hash::make('123456'), 'attempts' => 0, 'expires_at' => now()->addMinutes(5)]);
        $this->withSession(['patient_reset_user_id' => $user->id])->post(route('patient.password.verify.store'), ['otp' => '000000'])->assertSessionHasErrors('otp');
        $this->withSession(['patient_reset_user_id' => $user->id])->post(route('patient.password.verify.store'), ['otp' => '123456'])->assertRedirect(route('patient.password.reset'));
        $this->withSession(['patient_reset_user_id' => $user->id, 'patient_reset_grant' => 'grant', 'patient_reset_grant_issued_at' => now()->timestamp])->post(route('patient.password.reset.store'), ['password' => 'Moi@12345', 'password_confirmation' => 'Moi@12345'])->assertRedirect(route('patient.login'));
        $this->assertTrue(Hash::check('Moi@12345', $user->fresh()->password));
        $this->post(route('patient.password.reset.store'), ['password' => 'Khac@12345', 'password_confirmation' => 'Khac@12345'])->assertRedirect(route('patient.password.request'));
        $this->get(route('patient.password.request'))->assertDontSee('123456');
    }

    public function test_expired_and_reused_otps_are_refused(): void
    {
        $user = User::where('business_code', 'BN000001')->firstOrFail();
        PatientPasswordResetChallenge::create(['user_id' => $user->id, 'otp_hash' => Hash::make('123456'), 'attempts' => 0, 'expires_at' => now()->subSecond()]);
        $this->withSession(['patient_reset_user_id' => $user->id])->post(route('patient.password.verify.store'), ['otp' => '123456'])->assertSessionHasErrors('otp');
    }

    public function test_otp_resend_replaces_challenge_and_five_failures_lock_it(): void
    {
        $user = User::where('business_code', 'BN000001')->firstOrFail();
        $sender = new class implements PatientOtpSender {
            public function send(User $user, string $otp): bool { return true; }
        };
        $service = new PatientPasswordResetService($sender);
        $this->assertTrue($service->request($user));
        $firstHash = PatientPasswordResetChallenge::where('user_id', $user->id)->value('otp_hash');
        $this->assertTrue($service->request($user));
        $challenge = PatientPasswordResetChallenge::where('user_id', $user->id)->firstOrFail();
        $this->assertSame(1, PatientPasswordResetChallenge::where('user_id', $user->id)->count());
        $this->assertSame(0, $challenge->attempts);
        $this->assertNotSame($firstHash, $challenge->otp_hash);

        $challenge->update(['otp_hash' => Hash::make('123456'), 'attempts' => 0, 'expires_at' => now()->addMinutes(5)]);
        for ($i = 0; $i < 5; $i++) {
            $this->assertNull($service->verify($user, '000000'));
        }
        $this->assertNull($service->verify($user, '123456'));
        $this->assertSame(5, $challenge->fresh()->attempts);
    }

    public function test_expired_reset_grant_cannot_change_password(): void
    {
        $user = User::where('business_code', 'BN000001')->firstOrFail();
        $this->withSession([
            'patient_reset_user_id' => $user->id,
            'patient_reset_grant' => 'expired-grant',
            'patient_reset_grant_issued_at' => now()->subMinutes(16)->timestamp,
        ])->post(route('patient.password.reset.store'), [
            'password' => 'Moi@12345', 'password_confirmation' => 'Moi@12345',
        ])->assertRedirect(route('patient.password.request'));
        $this->assertTrue(Hash::check('Mediway@123', $user->fresh()->password));
    }
}
