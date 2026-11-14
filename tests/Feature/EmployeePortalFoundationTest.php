<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ServiceOrderItem;
use App\Support\EmployeeAccess;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\EmployeeDemoExpansionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmployeePortalFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
    }

    public function test_active_employees_use_common_login_and_choose_a_workspace_after_authentication(): void
    {
        $employee = User::where('business_code', 'NV000002')->firstOrFail();
        $this->post(route('patient.login.store'), ['phone' => $employee->phone, 'password' => 'Mediway@123'])->assertRedirect(route('employee.home'));
        $this->get(route('employee.home'))->assertOk()->assertSee('Nhân viên tiếp đón')->assertSee('Nhân viên thu ngân');
        $this->post(route('employee.logout'))->assertRedirect(route('public.home'));
        $nurse = User::where('business_code', 'NV000001')->firstOrFail();
        $nurse->employee->detail()->update(['employee_type' => 'NURSE']);
        $this->post(route('patient.login.store'), ['phone' => $nurse->phone, 'password' => 'Mediway@123'])->assertRedirect(route('employee.home'));
        $this->get(route('employee.home'))->assertOk()->assertSee('Nhân viên tiếp đón')->assertSee('Nhân viên thu ngân');
    }

    public function test_sample_receptionist_and_cashier_have_separate_departments(): void
    {
        $receptionist = User::where('business_code', 'NV000001')->firstOrFail()->employee->detail;
        $cashier = User::where('business_code', 'NV000004')->firstOrFail()->employee->detail;
        $this->assertSame('RECEPTIONIST', $receptionist->employee_type);
        $this->assertSame('Lễ tân', $receptionist->department);
        $this->assertSame('CASHIER', $cashier->employee_type);
        $this->assertSame('Thu ngân', $cashier->department);
    }

    public function test_expanded_demo_accounts_can_log_in_and_reseeding_preserves_existing_records(): void
    {
        $this->seed(EmployeeDemoExpansionSeeder::class);
        $accounts = [
            'NV000005' => ['RECEPTIONIST', 'employee.home'],
            'NV000006' => ['CASHIER', 'employee.home'],
            'NV000007' => ['SERVICE_TECHNICIAN', 'employee.home'],
            'NV000008' => ['SERVICE_TECHNICIAN', 'employee.home'],
        ];
        foreach ($accounts as $code => [$type, $destination]) {
            $user = User::where('business_code', $code)->firstOrFail();
            $this->assertSame($type, $user->employee->detail->employee_type);
            $this->post(route('patient.login.store'), ['phone' => $user->phone, 'password' => 'Mediway@123'])
                ->assertRedirect(route($destination));
            $this->post(route('employee.logout'))->assertRedirect(route('public.home'));
        }
        $user->update(['password' => 'MatKhauMauDaDoi@123']);
        $user->employee->detail()->update(['department' => 'Bộ phận đã đổi']);

        $this->seed(EmployeeDemoExpansionSeeder::class);

        $this->assertSame(8, \App\Models\Employee::count());
        $this->assertSame('Bộ phận đã đổi', $user->employee->detail()->value('department'));
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
        $this->get(route('employee.home'))->assertRedirect(route('employee.reception.index'));
        $this->post(route('employee.workspace.select'), ['workspace' => 'cashier'])->assertForbidden();
    }

    public function test_every_legacy_employee_classification_can_choose_either_workspace(): void
    {
        $user = User::where('business_code', 'NV000002')->firstOrFail();
        $detail = $user->employee->detail;
        $access = app(EmployeeAccess::class);
        foreach (array_keys(EmployeeAccess::TYPES) as $type) {
            $detail->update(['employee_type' => $type]);
            $this->assertTrue($access->allows($user, 'reception.check_in', 'reception'));
            $this->assertTrue($access->allows($user, 'payment.collect', 'cashier'));
            $this->assertFalse($access->allows($user, 'queue.call', 'reception'));
            $this->flushSession()->actingAs($user)->get(route('employee.home'))->assertOk()
                ->assertSee('Nhân viên tiếp đón')->assertSee('Nhân viên thu ngân');
        }
    }

    public function test_each_supported_employee_type_can_log_in_and_out(): void
    {
        $user = User::where('business_code', 'NV000002')->firstOrFail();
        foreach (array_keys(EmployeeAccess::TYPES) as $type) {
            $user->employee->detail()->update(['employee_type' => $type]);
            $this->post(route('patient.login.store'), ['phone' => $user->phone, 'password' => 'Mediway@123'])
                ->assertRedirect(route('employee.home'));
            $this->get(route('employee.home'))->assertOk()->assertSee('Nhân viên tiếp đón')->assertSee('Nhân viên thu ngân');
            $this->post(route('employee.workspace.select'), ['workspace' => 'cashier'])->assertRedirect(route('employee.home'));
            $this->get(route('employee.home'))->assertRedirect(route('employee.payments.index'));
            $this->post(route('employee.logout'))->assertRedirect(route('public.home'));
            $this->assertGuest();
        }
    }

    public function test_employee_profile_blocks_privileged_fields_and_updates_only_owned_contact(): void
    {
        $user = User::where('business_code', 'NV000002')->firstOrFail();
        $this->actingAsEmployeeWorkspace($user, 'reception')->put(route('employee.profile.update'), ['employee_type' => 'CASHIER', 'email' => 'test@example.com'])->assertSessionHasErrors('employee_type');
        $this->assertSame('SERVICE_TECHNICIAN', $user->employee->detail->fresh()->employee_type);
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
        $user->employee->detail->update(['employee_type' => 'CASHIER']);
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

    public function test_capability_middleware_uses_the_locked_workspace_not_employee_classification(): void
    {
        \Illuminate\Support\Facades\Route::middleware(['web', \App\Http\Middleware\EmployeeMiddleware::class.':reception.check_in'])
            ->post('/employee-test-capability', fn () => response('allowed'));
        $user = User::where('business_code', 'NV000002')->firstOrFail();
        $this->actingAsEmployeeWorkspace($user, 'reception')->post('/employee-test-capability')->assertOk();
        $user->employee->detail->update(['employee_type' => 'CASHIER']);
        $this->post('/employee-test-capability')->assertOk();
        $this->post(route('employee.workspace.select'), ['workspace' => 'cashier'])->assertForbidden();
    }

    public function test_admin_cannot_change_type_with_unfinished_assigned_service(): void
    {
        $admin = User::where('business_code', 'QT000001')->firstOrFail();
        $employee = User::where('business_code', 'NV000002')->firstOrFail()->employee;
        $item = \App\Models\ServiceOrderItem::firstOrFail();
        $pending = \App\Models\ServiceStatus::where('name', 'PENDING')->firstOrFail();
        $item->update(['performed_by_employee_id' => $employee->id, 'service_status_id' => $pending->id]);
        $this->actingAs($admin)->put(route('admin.employees.update', $employee), [
            'full_name' => $employee->full_name, 'status' => 'ACTIVE', 'employee_type' => 'CASHIER', 'expected_employee_type' => 'SERVICE_TECHNICIAN', 'employee_type_change_reason' => 'Điều chuyển công tác',
        ])->assertSessionHasErrors('employee_type');
        $this->assertSame('SERVICE_TECHNICIAN', $employee->detail->fresh()->employee_type);
    }

    public function test_admin_cannot_change_cashier_type_with_a_pending_assigned_payment(): void
    {
        $admin = User::where('business_code', 'QT000001')->firstOrFail();
        $employee = User::where('business_code', 'NV000004')->firstOrFail()->employee;
        $transaction = \App\Models\PaymentTransaction::where('collected_by_employee_id', $employee->id)->firstOrFail();
        $transaction->update(['transaction_result' => 'PENDING']);
        $this->actingAs($admin)->put(route('admin.employees.update', $employee), [
            'full_name' => 'Tên không được lưu', 'status' => 'ACTIVE', 'employee_type' => 'RECEPTIONIST',
            'expected_employee_type' => 'CASHIER',
            'employee_type_change_reason' => 'Điều chuyển công tác',
        ])->assertSessionHasErrors('employee_type');
        $this->assertSame('CASHIER', $employee->detail->fresh()->employee_type);
        $this->assertNotSame('Tên không được lưu', $employee->fresh()->full_name);
    }

    public function test_all_six_admin_type_changes_preserve_identity_and_update_permissions(): void
    {
        $admin = User::where('business_code', 'QT000001')->firstOrFail();
        $employee = User::where('business_code', 'NV000002')->firstOrFail()->employee;
        ServiceOrderItem::where('performed_by_employee_id', $employee->id)->unresolved()->update(['performed_by_employee_id' => null]);
        $userId = $employee->user_id;
        $code = $employee->employee_code;
        $capabilities = ['RECEPTIONIST' => 'reception.check_in', 'SERVICE_TECHNICIAN' => 'services.process', 'CASHIER' => 'payment.collect'];
        foreach ($capabilities as $from => $oldCapability) {
            foreach ($capabilities as $to => $newCapability) {
                if ($from === $to) continue;
                $employee->detail()->update(['employee_type' => $from]);
                $this->actingAs($admin)->put(route('admin.employees.update', $employee), [
                    'full_name' => $employee->full_name, 'status' => 'ACTIVE', 'employee_type' => $to,
                    'expected_employee_type' => $from,
                    'employee_type_change_reason' => 'Điều chuyển công tác',
                ])->assertSessionHasNoErrors();
                $employee->refresh();
                $this->assertSame($to, $employee->detail->employee_type);
                $this->assertSame($userId, $employee->user_id);
                $this->assertSame($code, $employee->employee_code);
                $this->assertFalse(app(EmployeeAccess::class)->allows($employee->user, 'services.process', 'reception'));
                $this->assertFalse(app(EmployeeAccess::class)->allows($employee->user, 'services.process', 'cashier'));
                $this->assertTrue(app(EmployeeAccess::class)->allows($employee->user, 'reception.check_in', 'reception'));
                $this->assertTrue(app(EmployeeAccess::class)->allows($employee->user, 'payment.collect', 'cashier'));
            }
        }
    }

    public function test_stale_admin_form_cannot_overwrite_another_type_change(): void
    {
        $admin = User::where('business_code', 'QT000001')->firstOrFail();
        $employee = User::where('business_code', 'NV000002')->firstOrFail()->employee;
        $employee->detail()->update(['employee_type' => 'CASHIER']);
        $this->actingAs($admin)->put(route('admin.employees.update', $employee), [
            'full_name' => $employee->full_name, 'status' => 'ACTIVE', 'employee_type' => 'RECEPTIONIST',
            'expected_employee_type' => 'SERVICE_TECHNICIAN',
        ])->assertSessionHasErrors('employee_type');
        $this->assertSame('CASHIER', $employee->detail->fresh()->employee_type);
    }

    public function test_type_change_requires_reason_and_records_it_in_audit_log(): void
    {
        $admin = User::where('business_code', 'QT000001')->firstOrFail();
        $employee = User::where('business_code', 'NV000002')->firstOrFail()->employee;
        ServiceOrderItem::where('performed_by_employee_id', $employee->id)->unresolved()->update(['performed_by_employee_id' => null]);
        $data = ['full_name' => $employee->full_name, 'status' => 'ACTIVE', 'employee_type' => 'CASHIER',
            'expected_employee_type' => 'SERVICE_TECHNICIAN'];
        $this->actingAs($admin)->put(route('admin.employees.update', $employee), $data)
            ->assertSessionHasErrors('employee_type_change_reason');
        $this->assertSame('SERVICE_TECHNICIAN', $employee->detail->fresh()->employee_type);
        $this->put(route('admin.employees.update', $employee), $data + ['employee_type_change_reason' => 'Chuyển bộ phận'])
            ->assertSessionHasNoErrors();
        $log = \App\Models\AuditLog::where('entity_type', 'employee_details')->where('entity_id', $employee->detail->id)
            ->where('user_id', $admin->id)->latest('id')->firstOrFail();
        $this->assertSame('SERVICE_TECHNICIAN', $log->old_values['employee_type']);
        $this->assertSame('CASHIER', $log->new_values['employee_type']);
        $this->assertSame('Chuyển bộ phận', $log->new_values['employee_type_change_reason']);
        $this->assertContains(['label' => 'Lý do đổi loại nhân viên', 'value' => 'Chuyển bộ phận'],
            app(\App\Models\AuditValuePresenter::class)->rows($log->new_values));
    }

    public function test_saving_same_type_does_not_require_a_change_reason_or_record_a_type_change(): void
    {
        $admin = User::where('business_code', 'QT000001')->firstOrFail();
        $employee = User::where('business_code', 'NV000002')->firstOrFail()->employee;
        foreach (array_keys(EmployeeAccess::TYPES) as $type) {
            $employee->detail()->update(['employee_type' => $type]);
            $lastId = \App\Models\AuditLog::max('id') ?? 0;
            $this->actingAs($admin)->put(route('admin.employees.update', $employee), [
                'full_name' => $employee->full_name, 'status' => 'ACTIVE', 'employee_type' => $type,
                'expected_employee_type' => $type,
            ])->assertSessionHasNoErrors();
            $logs = \App\Models\AuditLog::where('id', '>', $lastId)->where('entity_type', 'employee_details')->get();
            foreach ($logs as $log) {
                $this->assertArrayNotHasKey('employee_type', $log->new_values ?? []);
            }
            $this->assertSame($type, $employee->detail->fresh()->employee_type);
        }
    }
}
