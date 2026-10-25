<?php

namespace Tests\Audit;

use App\Models\AuditLog;
use App\Models\Doctor;
use App\Models\MedicalVisit;
use App\Models\Patient;
use App\Models\Payable;
use App\Models\PaymentStatus;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderItem;
use App\Models\ServiceStatus;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** Explicit audit probes; not part of the default regression suite until repaired. */
class AdminFreezeAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 24)->setTime(10, 0));
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail());
    }

    private function legacyItem(string $status): ServiceOrderItem
    {
        $visit = MedicalVisit::whereHas('status', fn ($q) => $q->where('name', 'COMPLETED'))->firstOrFail();
        $order = ServiceOrder::firstOrFail()->replicate();
        $order->fill(['service_order_code' => 'CD-AUDIT-LEGACY', 'medical_visit_id' => $visit->id, 'status' => 'ACTIVE'])->save();
        $item = ServiceOrderItem::firstOrFail()->replicate();
        $item->fill(['service_order_id' => $order->id, 'service_status_id' => ServiceStatus::where('name', $status)->value('id'),
            'started_at' => $status === 'PROCESSING' ? now()->subHour() : null, 'completed_at' => null])->save();

        return $item;
    }

    public function test_inactive_admin_profile_must_not_enter_admin(): void
    {
        User::where('business_code', 'QT000001')->firstOrFail()->administrator->update(['status' => 'INACTIVE']);
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
    }

    public function test_missing_admin_profile_must_not_enter_admin(): void
    {
        User::where('business_code', 'QT000001')->firstOrFail()->administrator->delete();
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
    }

    public function test_inactive_admin_role_must_not_enter_admin(): void
    {
        User::where('business_code', 'QT000001')->firstOrFail()->role->update(['status' => 'INACTIVE']);
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
    }

    public function test_editing_doctor_name_must_not_unlock_account(): void
    {
        $doctor = Doctor::with('user')->firstOrFail();
        $this->patch(route('admin.users.update-status', $doctor->user), ['status' => 'LOCKED'])->assertSessionHasNoErrors();
        $this->assertSame('LOCKED', $doctor->user->fresh()->status);
        $this->put(route('admin.doctors.update', $doctor), ['full_name' => 'Tên được sửa trong kiểm thử',
            'specialty_id' => $doctor->specialty_id, 'status' => 'ACTIVE'])->assertSessionHasNoErrors();
        $this->assertSame('LOCKED', $doctor->user->fresh()->status);
    }

    public function test_completed_legacy_visit_must_not_accept_direct_result_creation(): void
    {
        $item = $this->legacyItem('PROCESSING');
        $response = $this->postJson(route('admin.service-order-items.service-results.store', $item), [
            'result_summary' => 'Bổ sung lịch sử phải theo quy trình riêng', 'confirm_create' => 1,
        ]);
        $this->assertNull($item->result()->first());
        $response->assertUnprocessable();
    }

    public function test_completed_legacy_visit_must_not_start_new_service_execution(): void
    {
        $item = $this->legacyItem('PENDING');
        $payable = Payable::firstOrFail()->replicate();
        $payable->fill(['payable_code' => 'PT-AUDIT-LEGACY', 'medical_visit_id' => $item->serviceOrder->medical_visit_id,
            'service_order_item_id' => $item->id, 'payment_status_id' => PaymentStatus::where('name', 'PAID')->value('id')])->save();
        $this->post(route('admin.service-order-items.start', $item));
        $this->assertSame('PENDING', $item->fresh()->serviceStatus->name);
    }

    public function test_health_measurement_must_contain_at_least_one_value(): void
    {
        $this->postJson(route('admin.personal-health-records.store'), [
            'patient_id' => Patient::firstOrFail()->id, 'recorded_at' => now()->subMinute()->format('Y-m-d\TH:i'),
        ])->assertUnprocessable();
    }

    public function test_follow_up_date_must_not_precede_visit(): void
    {
        $visit = MedicalVisit::whereDoesntHave('conclusion')->whereHas('status', fn ($q) => $q->where('name', 'IN_EXAMINATION'))->firstOrFail();
        $this->postJson(route('admin.visits.conclusion.store', $visit), [
            'professional_conclusion' => 'Kết luận kiểm thử', 'has_follow_up' => true,
            'follow_up_date' => '2020-01-01', 'follow_up_reason' => 'Ngày cũ không hợp lệ',
        ])->assertUnprocessable();
    }

    public function test_conclusion_accepted_at_creation_must_remain_editable_without_shortening(): void
    {
        $visit = MedicalVisit::whereDoesntHave('conclusion')->whereHas('status', fn ($q) => $q->where('name', 'IN_EXAMINATION'))->firstOrFail();
        $content = str_repeat('a', 6000);
        $this->post(route('admin.visits.conclusion.store', $visit), [
            'professional_conclusion' => $content, 'has_follow_up' => false,
        ])->assertSessionHasNoErrors();
        $conclusion = $visit->conclusion()->firstOrFail();
        $this->put(route('admin.conclusions.update', $conclusion), [
            'doctor_id' => $conclusion->doctor_id, 'professional_conclusion' => $content, 'has_follow_up' => false,
        ])->assertSessionHasNoErrors();
    }

    public function test_result_time_must_not_be_before_execution_start(): void
    {
        $item = ServiceOrderItem::whereDoesntHave('result')->firstOrFail();
        $item->update(['service_status_id' => ServiceStatus::where('name', 'PROCESSING')->value('id'), 'started_at' => now()->subHour()]);
        $this->postJson(route('admin.service-order-items.service-results.store', $item), [
            'result_summary' => 'Kết quả kiểm thử', 'resulted_at' => '2020-01-01 08:00:00', 'confirm_create' => 1,
        ])->assertUnprocessable();
    }

    public function test_password_reset_must_leave_a_non_secret_audit_event(): void
    {
        $user = User::whereHas('role', fn ($q) => $q->where('name', 'Patient'))->firstOrFail();
        $before = AuditLog::count();
        $this->patch(route('admin.users.reset-password', $user), [
            'password' => 'AuditPassword123', 'password_confirmation' => 'AuditPassword123',
        ])->assertSessionHasNoErrors();
        $this->assertGreaterThan($before, AuditLog::count());
    }

    public function test_locked_inactive_doctor_must_not_be_selected_as_result_verifier(): void
    {
        $doctor = Doctor::firstOrFail();
        $doctor->update(['status' => 'INACTIVE']);
        $doctor->user->update(['status' => 'LOCKED']);
        $item = ServiceOrderItem::whereDoesntHave('result')->firstOrFail();
        $item->update(['service_status_id' => ServiceStatus::where('name', 'PROCESSING')->value('id'), 'started_at' => now()->subHour()]);
        $this->postJson(route('admin.service-order-items.service-results.store', $item), [
            'result_summary' => 'Kiểm thử người xác nhận', 'verified_by_user_id' => $doctor->user_id, 'confirm_create' => 1,
        ])->assertUnprocessable();
    }

    public function test_employee_password_must_not_silently_ignore_its_suffix(): void
    {
        $password = str_repeat('a', 72).'SuffixAAA';
        $response = $this->post(route('admin.employees.store'), [
            'full_name' => 'Nhân viên kiểm thử mật khẩu', 'phone' => '0988888888',
            'password' => $password, 'password_confirmation' => $password,
            'status' => 'ACTIVE', 'employee_type' => 'RECEPTIONIST',
        ]);
        $user = User::where('phone', '0988888888')->first();
        if (! $user) {
            $response->assertSessionHasErrors('password');

            return;
        }
        $this->assertFalse(Hash::check(str_repeat('a', 72).'DifferentTail', $user->password),
            'Mật khẩu khác phần đuôi vẫn được chấp nhận vì chỉ 72 byte đầu tham gia bcrypt.');
    }
}
