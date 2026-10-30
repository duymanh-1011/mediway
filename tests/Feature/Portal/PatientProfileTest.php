<?php

namespace Tests\Feature\Portal;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PatientProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
    }

    public function test_update_only_own_allowed_fields_and_preserves_omitted_fields(): void
    {
        $a = User::where('business_code', 'BN000001')->firstOrFail();
        $b = User::where('business_code', 'BN000002')->firstOrFail();
        $oldHometown = $a->patient->detail->hometown;
        $oldCode = $a->patient->patient_code;
        $oldRole = $a->role_id;
        $this->actingAs($a)->get(route('patient.home'))->assertOk()->assertSee(route('patient.profile.show'));
        $this->get(route('patient.profile.show'))->assertOk()->assertSee(route('patient.profile.edit'));
        $this->actingAs($a)->get(route('patient.profile.edit'))->assertOk()->assertSee('Liên hệ khẩn cấp');
        $this->actingAs($a)->put(route('patient.profile.update'), [
            'full_name' => 'Tên đã cập nhật', 'email' => 'new@example.com',
            'patient_id' => $b->patient->id, 'user_id' => $b->id, 'patient_code' => 'FORGED',
            'role_id' => 1, 'status' => 'LOCKED', 'notes' => 'forged note',
        ])->assertRedirect(route('patient.profile.show'));
        $this->assertSame('Tên đã cập nhật', $a->patient->fresh()->full_name);
        $this->assertSame('new@example.com', $a->patient->fresh()->detail->email);
        $this->assertSame($oldHometown, $a->patient->fresh()->detail->hometown);
        $this->assertSame($oldCode, $a->patient->fresh()->patient_code);
        $this->assertSame($a->id, $a->patient->fresh()->user_id);
        $this->assertSame('ACTIVE', $a->patient->fresh()->status);
        $this->assertSame($oldRole, $a->fresh()->role_id);
        $this->assertNotSame('Tên đã cập nhật', $b->patient->fresh()->full_name);
        $this->assertNotSame('forged note', $a->patient->fresh()->detail->notes);
    }

    public function test_validation_and_missing_detail(): void
    {
        $user = User::where('business_code', 'BN000001')->firstOrFail();
        $this->actingAs($user)->from(route('patient.profile.edit'))->put(route('patient.profile.update'), [
            'full_name' => '  ', 'email' => 'invalid', 'emergency_contact_phone' => '123', 'date_of_birth' => '2099-01-01',
        ])->assertSessionHasErrors(['full_name', 'email', 'emergency_contact_phone', 'date_of_birth']);
        DB::table('patient_details')->where('patient_id', $user->patient->id)->delete();
        $this->actingAs($user->fresh())->put(route('patient.profile.update'), ['email' => 'hello@example.com'])
            ->assertRedirect(route('patient.profile.show'));
        $this->assertSame('hello@example.com', $user->patient->fresh()->detail->email);
    }

    public function test_unauthorized_user_cannot_edit_or_update(): void
    {
        $this->get(route('patient.profile.edit'))->assertRedirect(route('patient.login'));
        $this->put(route('patient.profile.update'), ['full_name' => 'X'])->assertRedirect(route('patient.login'));
        $admin = User::where('business_code', 'QT000001')->firstOrFail();
        $this->actingAs($admin)->put(route('patient.profile.update'), ['full_name' => 'X'])->assertRedirect(route('patient.login'));
    }

    public function test_initial_identity_fields_can_be_filled_once_and_id_is_masked(): void
    {
        $user = User::where('business_code', 'BN000001')->firstOrFail();
        DB::table('patients')->where('id', $user->patient->id)->update(['date_of_birth' => null, 'gender' => null]);
        DB::table('patient_details')->where('patient_id', $user->patient->id)->update(['citizen_id_number' => null]);
        $user = $user->fresh();
        $this->actingAs($user)->get(route('patient.profile.edit'))->assertOk()->assertSee('Ngày sinh (chỉ nhập một lần)')->assertSee('Số CCCD (chỉ nhập một lần)');
        $this->put(route('patient.profile.update'), ['date_of_birth' => '2099-01-01', 'gender' => 'invalid', 'citizen_id_number' => '123'])->assertSessionHasErrors(['date_of_birth', 'gender', 'citizen_id_number']);
        $this->put(route('patient.profile.update'), ['date_of_birth' => '1990-02-03', 'gender' => 'Nữ', 'citizen_id_number' => '999999991234'])->assertRedirect(route('patient.profile.show'));
        $this->assertSame('1990-02-03', $user->patient->fresh()->date_of_birth->format('Y-m-d'));
        $this->assertSame('Nữ', $user->patient->fresh()->gender);
        $this->actingAs($user->fresh())->get(route('patient.profile.show'))->assertSee('••••••••1234')->assertDontSee('999999991234');
        $this->put(route('patient.profile.update'), ['date_of_birth' => '2000-01-01', 'gender' => 'Nam', 'citizen_id_number' => '888888885678'])->assertSessionHasErrors(['date_of_birth', 'gender', 'citizen_id_number']);
    }

    public function test_phone_change_information_requires_patient_authentication(): void
    {
        $this->get(route('patient.profile.phone'))->assertRedirect(route('patient.login'));
        $user = User::where('business_code', 'BN000001')->firstOrFail();
        $this->actingAs($user)->get(route('patient.profile.phone'))->assertOk()->assertSee('chưa khả dụng')->assertSee($user->phone);
    }

    public function test_validation_error_preserves_the_entered_values(): void
    {
        $user = User::where('business_code', 'BN000001')->firstOrFail();
        $response = $this->actingAs($user)->followingRedirects()->from(route('patient.profile.edit'))->put(route('patient.profile.update'), [
            'full_name' => 'Tên nhập thử', 'email' => 'email-khong-hop-le',
        ]);
        $response->assertOk()->assertSee('Tên nhập thử')->assertSee('email-khong-hop-le')->assertSee('Email không đúng định dạng.')->assertSee('aria-invalid="true"', false);
    }

    public function test_only_own_profile_is_shown_without_private_notes_or_paths(): void
    {
        $a = User::where('business_code', 'BN000001')->firstOrFail();
        $b = User::where('business_code', 'BN000002')->firstOrFail();
        DB::table('patient_details')->where('patient_id', $a->patient->id)->update(['notes' => 'secret-internal-note', 'profile_pdf_path' => 'private-document-file.pdf']);
        $this->actingAs($a)->get(route('patient.profile.show', ['patient_id' => $b->patient->id]))
            ->assertOk()->assertSee($a->patient->full_name)->assertSee($a->patient->patient_code)
            ->assertDontSee($b->patient->full_name)->assertDontSee('secret-internal-note')->assertDontSee('private-document-file.pdf')
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_missing_detail_and_invalid_users_are_safe(): void
    {
        $this->get(route('patient.profile.show'))->assertRedirect(route('patient.login'));
        foreach (['Administrator', 'Doctor', 'Employee'] as $role) {
            $user = User::whereHas('role', fn ($q) => $q->where('name', $role))->firstOrFail();
            $this->actingAs($user)->get(route('patient.profile.show'))->assertRedirect(route('patient.login'));
        }
        $user = User::where('business_code', 'BN000001')->firstOrFail();
        DB::table('patient_details')->where('patient_id', $user->patient->id)->delete();
        $this->actingAs($user)->get(route('patient.profile.show'))->assertOk()->assertSee('Chưa cập nhật');
        DB::table('patients')->where('id', $user->patient->id)->update(['status' => 'INACTIVE']);
        $this->actingAs($user->fresh())->get(route('patient.profile.show'))->assertRedirect(route('patient.login'));
    }

    public function test_avatar_endpoint_is_scoped_and_rejects_other_patient_paths(): void
    {
        Storage::fake('local');
        $a = User::where('business_code', 'BN000001')->firstOrFail();
        $b = User::where('business_code', 'BN000002')->firstOrFail();
        $otherPath = 'patient-documents/'.$b->patient->id.'/other.png';
        Storage::disk('local')->put($otherPath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/a5kAAAAASUVORK5CYII='));
        DB::table('patient_details')->where('patient_id', $a->patient->id)->update(['avatar_path' => $otherPath]);
        $this->actingAs($a)->get(route('patient.profile.avatar', ['patient_id' => $b->patient->id]))->assertNotFound();
        $ownPath = 'patient-documents/'.$a->patient->id.'/own.png';
        Storage::disk('local')->put($ownPath, Storage::disk('local')->get($otherPath));
        DB::table('patient_details')->where('patient_id', $a->patient->id)->update(['avatar_path' => $ownPath]);
        $this->actingAs($a->fresh())->get(route('patient.profile.avatar'))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
    }
}
