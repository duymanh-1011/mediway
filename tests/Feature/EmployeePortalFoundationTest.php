<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\EmployeeAccess;
use Tests\Fixtures\Seeders\WorkflowFixtureSeeder;
use Database\Seeders\AdditionalEmployeeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmployeePortalFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(WorkflowFixtureSeeder::class);
        $this->withoutVite();
    }

    public function test_active_employees_use_common_login_and_choose_a_workspace_after_authentication(): void
    {
        $employee = User::where('business_code', 'NV000002')->firstOrFail();
        $this->post(route('patient.login.store'), ['phone' => $employee->phone, 'password' => 'Mediway@123'])->assertRedirect(route('employee.home'));
        $this->get(route('employee.home'))->assertOk()->assertSee('Nhân viên tiếp đón')->assertSee('Nhân viên thu ngân');
        $this->post(route('employee.logout'))->assertRedirect(route('public.home'));
        $nurse = User::where('business_code', 'NV000001')->firstOrFail();
        $this->post(route('patient.login.store'), ['phone' => $nurse->phone, 'password' => 'Mediway@123'])->assertRedirect(route('employee.home'));
        $this->get(route('employee.home'))->assertOk()->assertSee('Nhân viên tiếp đón')->assertSee('Nhân viên thu ngân');
    }

    public function test_employee_profiles_no_longer_depend_on_departments_or_types(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasTable('employee_types'));
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('employee_details', 'department'));
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('employee_details', 'employee_type'));
    }

    public function test_expanded_demo_accounts_can_log_in_and_reseeding_preserves_existing_records(): void
    {
        $this->seed(AdditionalEmployeeSeeder::class);
        $accounts = ['NV000005', 'NV000006', 'NV000007', 'NV000008'];
        foreach ($accounts as $code) {
            $user = User::where('business_code', $code)->firstOrFail();
            $this->post(route('patient.login.store'), ['phone' => $user->phone, 'password' => 'Mediway@123'])
                ->assertRedirect(route('employee.home'));
            $this->post(route('employee.logout'))->assertRedirect(route('public.home'));
        }
        $user->update(['password' => 'MatKhauMauDaDoi@123']);
        $address = $user->employee->detail->current_address;

        $this->seed(AdditionalEmployeeSeeder::class);

        $this->assertSame(8, \App\Models\Employee::count());
        $this->assertSame($address, $user->employee->detail->fresh()->current_address);
        $this->assertTrue(Hash::check('MatKhauMauDaDoi@123', $user->fresh()->password));
    }

    public function test_doctor_login_is_redirected_to_the_doctor_portal(): void
    {
        $doctor = User::whereHas('role', fn ($query) => $query->where('name', 'Doctor'))->firstOrFail();
        $this->post(route('patient.login.store'), ['phone' => $doctor->phone, 'password' => 'Mediway@123'])
            ->assertRedirect(route('doctor.home'));
        $this->assertAuthenticatedAs($doctor);
    }

    public function test_inactive_doctor_cannot_use_the_registered_doctor_portal(): void
    {
        $doctor = User::whereHas('role', fn ($query) => $query->where('name', 'Doctor'))->firstOrFail();
        $doctor->doctor->update(['status' => 'INACTIVE']);
        $this->post(route('patient.login.store'), ['phone' => $doctor->phone, 'password' => 'Mediway@123'])
            ->assertSessionHasErrors('phone');
        $this->assertGuest();
    }

    public function test_employee_profile_has_separate_read_and_edit_pages(): void
    {
        $user = User::where('business_code', 'NV000002')->firstOrFail();
        $this->actingAsEmployeeWorkspace($user, 'reception')->get(route('employee.profile'))->assertOk()
            ->assertSee(route('employee.profile.edit'))->assertDontSee('name="current_address"', false);
        $this->get(route('employee.profile.edit'))->assertOk()->assertSee('name="current_address"', false);
    }

    public function test_permission_changes_apply_on_next_request_and_queue_call_is_never_granted(): void
    {
        $user = User::where('business_code', 'NV000002')->firstOrFail();
        $access = app(EmployeeAccess::class);
        $this->assertTrue($access->allows($user, 'reception.check_in', 'reception'));
        $this->assertTrue($access->allows($user, 'payment.collect', 'cashier'));
        $this->assertFalse($access->allows($user, 'payment.collect', 'reception'));
        $this->assertFalse($access->allows($user, 'queue.call', 'reception'));
        $this->actingAs($user)->get(route('employee.home'))->assertOk()->assertSee('Nhân viên tiếp đón')->assertSee('Nhân viên thu ngân');
        $this->post(route('employee.workspace.select'), ['workspace' => 'reception'])->assertRedirect(route('employee.home'));
        $this->get(route('employee.home'))->assertOk()->assertSee(route('employee.reception.index'));
        $this->post(route('employee.workspace.select'), ['workspace' => 'cashier'])
            ->assertRedirect(route('employee.home'))->assertSessionHas('employee_workspace_mode', 'cashier');
    }

    public function test_employees_can_choose_both_workspaces_independently_of_profile_data(): void
    {
        $user = User::where('business_code', 'NV000002')->firstOrFail();
        $detail = $user->employee->detail;
        $access = app(EmployeeAccess::class);
        $this->assertTrue($access->allows($user, 'reception.check_in', 'reception'));
        $this->assertTrue($access->allows($user, 'payment.collect', 'cashier'));
        $this->assertFalse($access->allows($user, 'queue.call', 'reception'));
        $this->actingAs($user)->get(route('employee.home'))->assertOk()
            ->assertSee('Nhân viên tiếp đón')->assertSee('Nhân viên thu ngân');
    }

    public function test_all_employee_accounts_can_log_in_and_switch_to_cashier_workspace(): void
    {
        foreach (['NV000001', 'NV000002', 'NV000003', 'NV000004'] as $code) {
            $user = User::where('business_code', $code)->firstOrFail();
            $this->post(route('patient.login.store'), ['phone' => $user->phone, 'password' => 'Mediway@123'])
                ->assertRedirect(route('employee.home'));
            $this->get(route('employee.home'))->assertOk()->assertSee('Nhân viên tiếp đón')->assertSee('Nhân viên thu ngân');
            $this->post(route('employee.workspace.select'), ['workspace' => 'cashier'])->assertRedirect(route('employee.home'));
            $this->get(route('employee.home'))->assertOk()->assertSee(route('employee.payments.index'));
            $this->post(route('employee.logout'))->assertRedirect(route('public.home'));
            $this->assertGuest();
        }
    }

    public function test_employee_profile_blocks_privileged_fields_and_updates_only_owned_contact(): void
    {
        $user = User::where('business_code', 'NV000002')->firstOrFail();
        $this->actingAsEmployeeWorkspace($user, 'reception')->put(route('employee.profile.update'), ['employee_type' => 'CASHIER', 'email' => 'test@example.com'])->assertSessionHasErrors('employee_type');
        $this->put(route('employee.profile.update'), ['email' => 'test@example.com', 'current_address' => 'Địa chỉ kiểm thử'])->assertRedirect(route('employee.profile'));
        $this->assertSame('test@example.com', $user->employee->detail->fresh()->email);
    }

    public function test_password_change_requires_current_password_and_never_stores_plaintext(): void
    {
        $user = User::where('business_code', 'NV000002')->firstOrFail();
        $this->actingAsEmployeeWorkspace($user, 'reception')->put(route('employee.password.update'), ['current_password' => 'wrong', 'password' => 'MoiAnToan@123', 'password_confirmation' => 'MoiAnToan@123'])->assertSessionHasErrors('current_password');
        $this->put(route('employee.password.update'), ['current_password' => 'Mediway@123', 'password' => 'MoiAnToan@123', 'password_confirmation' => 'MoiAnToan@123'])->assertRedirect(route('employee.profile'));
        $this->assertTrue(Hash::check('MoiAnToan@123', $user->fresh()->password));
        $this->get(route('employee.profile'))->assertOk();
    }

    public function test_employee_session_is_revoked_after_password_changes_elsewhere(): void
    {
        $user = User::where('business_code', 'NV000002')->firstOrFail();
        $this->post(route('patient.login.store'), ['phone' => $user->phone, 'password' => 'Mediway@123'])->assertRedirect(route('employee.home'));
        $this->get(route('employee.home'))->assertOk();
        $user->update(['password' => 'KhacAnToan@123']);
        \Illuminate\Support\Facades\Auth::forgetGuards();
        $this->get(route('employee.home'))->assertRedirect(route('patient.login'));
        $this->assertGuest();
    }

    public function test_guest_patient_and_admin_cannot_open_employee_portal(): void
    {
        $this->get(route('employee.home'))->assertRedirect(route('patient.login'));
        foreach (['BN000001', 'QT000001'] as $code) {
            $this->flushSession();
            $this->actingAs(User::where('business_code', $code)->firstOrFail())->get(route('employee.home'))->assertForbidden();
        }
    }

    public function test_already_authenticated_users_opening_common_login_reach_their_own_home(): void
    {
        foreach (['NV000002' => 'employee.home', 'NV000001' => 'employee.home', 'NV000004' => 'employee.home',
            'BN000001' => 'patient.home', 'QT000001' => 'admin.dashboard'] as $code => $destination) {
            $this->flushSession();
            $user = User::where('business_code', $code)->firstOrFail();
            $this->actingAs($user)->get(route('patient.login'))->assertRedirect(route($destination));
        }
    }

    public function test_employee_chooser_offers_only_the_two_workspaces(): void
    {
        $user = User::where('business_code', 'NV000002')->firstOrFail();
        $this->actingAs($user)->get(route('employee.home'))->assertOk()
            ->assertSee('Nhân viên tiếp đón')->assertSee('Nhân viên thu ngân')
            ->assertDontSee(route('employee.services.index'));
        $this->get(route('employee.home'))->assertOk()->assertSee('Nhân viên tiếp đón')->assertSee('Nhân viên thu ngân');
    }

    public function test_revoked_employee_cannot_view_portal_but_can_logout(): void
    {
        $user = User::where('business_code', 'NV000002')->firstOrFail();
        $this->actingAs($user)->get(route('employee.home'))->assertOk();
        $user->employee->update(['status' => 'INACTIVE']);
        $this->get(route('employee.home'))->assertForbidden()->assertSee('Không có quyền truy cập');
        $this->post(route('employee.logout'))->assertRedirect(route('public.home'));
        $this->assertGuest();
        $this->get(route('employee.home'))->assertRedirect(route('patient.login'));
    }

    public function test_employee_account_requires_an_active_role_and_employee_record(): void
    {
        $user = User::where('business_code', 'NV000002')->firstOrFail();
        $access = app(EmployeeAccess::class);
        $this->assertNotNull($access->employee($user));
        $user->role->update(['status' => 'INACTIVE']);
        $this->assertNull($access->employee($user));
        $user->role->update(['status' => 'ACTIVE']);
        $user->update(['status' => 'LOCKED']);
        $this->assertNull($access->employee($user));
        $user->update(['status' => 'ACTIVE']);
        $user->employee->update(['status' => 'INACTIVE']);
        $this->assertNull($access->employee($user));
    }

    public function test_capability_middleware_uses_the_selected_workspace_not_employee_classification(): void
    {
        \Illuminate\Support\Facades\Route::middleware(['web', \App\Http\Middleware\EmployeeMiddleware::class.':reception.check_in'])
            ->post('/employee-test-capability', fn () => response('allowed'));
        $user = User::where('business_code', 'NV000002')->firstOrFail();
        $this->actingAsEmployeeWorkspace($user, 'reception')->post('/employee-test-capability')->assertOk();
        $this->post('/employee-test-capability')->assertOk();
        $this->post(route('employee.workspace.select'), ['workspace' => 'cashier'])->assertRedirect(route('employee.home'));
        $this->post('/employee-test-capability')->assertForbidden();
    }

}
