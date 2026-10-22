<?php

namespace Tests\Feature;

use App\Models\MedicalVisit;
use App\Models\Patient;
use App\Models\PersonalHealthRecord;
use App\Models\Prescription;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderItem;
use App\Models\ServiceStatus;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PreReleaseAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
    }

    private function signIn(): void
    {
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail());
    }

    private function unpaidItem(): ServiceOrderItem
    {
        return ServiceOrder::where('service_order_code', 'CD000004')->firstOrFail()->items()->firstOrFail();
    }

    public function test_repeated_invalid_logins_are_rate_limited(): void
    {
        $statuses = [];
        for ($attempt = 0; $attempt < 30; $attempt++) {
            $statuses[] = $this->post(route('admin.login.submit'), [
                'phone' => '0999999999', 'password' => 'invalid-test-password',
            ])->getStatusCode();
        }
        $this->assertContains(429, $statuses, 'Đăng nhập sai liên tiếp cần bị giới hạn tốc độ.');
    }

    public function test_service_start_respects_frozen_current_status(): void
    {
        $this->signIn();
        $item = $this->unpaidItem();
        $this->post(route('admin.payables.payment-transactions.store', $item->payable), [
            'payment_method' => 'CASH', 'confirm_payment' => 1,
        ])->assertSessionHasNoErrors()->assertSessionMissing('error');
        ServiceStatus::where('name', 'PENDING')->update(['allows_transition' => false]);
        $this->post(route('admin.service-order-items.start', $item));
        $this->assertSame('PENDING', $item->fresh()->serviceStatus->name, 'Không được bỏ qua cấu hình khóa chuyển trạng thái.');
        $this->assertNull($item->fresh()->started_at);
        $this->get(route('admin.service-orders.show', $item->serviceOrder))->assertOk()
            ->assertDontSee('action="'.route('admin.service-order-items.start', $item).'"', false);
    }

    public function test_service_completion_respects_frozen_current_status(): void
    {
        $this->signIn();
        $item = $this->unpaidItem();
        $this->post(route('admin.payables.payment-transactions.store', $item->payable), [
            'payment_method' => 'CASH', 'confirm_payment' => 1,
        ])->assertSessionHasNoErrors()->assertSessionMissing('error');
        $this->post(route('admin.service-order-items.start', $item))->assertSessionMissing('error');
        $this->post(route('admin.service-order-items.service-results.store', $item), [
            'result_summary' => 'Kết quả dùng kiểm thử', 'confirm_create' => 1,
        ])->assertSessionHasNoErrors()->assertSessionMissing('error');
        ServiceStatus::where('name', 'PROCESSING')->update(['allows_transition' => false]);
        $this->post(route('admin.service-order-items.complete', $item));
        $this->assertSame('PROCESSING', $item->fresh()->serviceStatus->name, 'Không được bỏ qua cấu hình khóa chuyển trạng thái.');
        $this->assertNull($item->fresh()->completed_at);
        $this->get(route('admin.service-orders.show', $item->serviceOrder))->assertOk()
            ->assertDontSee('action="'.route('admin.service-order-items.complete', $item).'"', false);
    }

    public function test_payable_cancellation_cannot_bypass_frozen_service_status(): void
    {
        $this->signIn();
        $item = $this->unpaidItem();
        ServiceStatus::where('name', 'PENDING')->update(['allows_transition' => false]);
        $this->assertFalse($item->fresh()->canBeCancelled());
        $this->patch(route('admin.payables.cancel', $item->payable), [
            'confirm_cancel' => 1, 'cancel_reason' => 'Kiểm thử trạng thái đang khóa',
        ])->assertSessionHasErrors('cancel_reason');
        $this->assertSame('PENDING', $item->fresh()->serviceStatus->name);
        $this->assertSame('UNPAID', $item->payable->fresh()->paymentStatus->name);
    }

    public function test_completed_visit_prescription_is_not_silently_rewritten(): void
    {
        $this->signIn();
        $prescription = Prescription::whereHas('medicalVisit.status', fn ($query) => $query->where('name', 'COMPLETED'))->firstOrFail();
        $before = $prescription->general_instructions;
        $this->patch(route('admin.prescriptions.update', $prescription), [
            'prescribed_at' => $prescription->prescribed_at->format('Y-m-d H:i:s'),
            'status' => $prescription->status, 'general_instructions' => 'Thay đổi sau khi hoàn tất khám', 'confirm_update' => 1,
        ]);
        $this->assertSame($before, $prescription->fresh()->general_instructions, 'Hồ sơ đã chốt cần cơ chế đính chính rõ ràng.');
    }

    public function test_array_filter_input_does_not_cause_a_server_error(): void
    {
        $this->signIn();
        $response = $this->get(route('admin.visits.index', ['search' => ['invalid']]));
        $this->assertLessThan(500, $response->getStatusCode(), 'Bộ lọc sai kiểu dữ liệu phải được xử lý có kiểm soát.');
    }

    public function test_non_administrators_and_locked_accounts_cannot_access_admin_data(): void
    {
        $patient = User::whereHas('role', fn ($query) => $query->where('name', 'Patient'))->firstOrFail();
        $this->actingAs($patient)->get(route('admin.patients.index'))->assertRedirect(route('admin.login'));
        $admin = User::where('business_code', 'QT000001')->firstOrFail();
        $admin->update(['status' => 'LOCKED']);
        $this->actingAs($admin)->get(route('admin.patients.index'))->assertRedirect(route('admin.login'));
    }

    public function test_validated_health_values_do_not_overflow_computed_bmi(): void
    {
        $this->signIn();
        $response = $this->post(route('admin.personal-health-records.store'), [
            'patient_id' => Patient::where('patient_code', 'BN000001')->value('id'),
            'height_cm' => 20, 'weight_kg' => 500,
            'recorded_at' => now()->subMinute()->format('Y-m-d\TH:i'),
        ]);
        $response->assertRedirect()->assertSessionHasErrors('weight_kg');
    }

    public function test_private_patient_documents_require_authentication_and_reject_path_traversal(): void
    {
        Storage::fake('local');
        $patient = Patient::where('patient_code', 'BN000001')->firstOrFail();
        $path = 'patient-documents/'.$patient->id.'/audit.pdf';
        Storage::disk('local')->put($path, "%PDF-1.4\n%%EOF");
        $patient->detail->update(['profile_pdf_path' => $path]);
        $url = route('admin.patients.document', [$patient, 'profile-pdf']);
        $this->get($url)->assertRedirect(route('admin.login'));
        $this->signIn();
        $response = $this->get($url)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $patient->detail->update(['profile_pdf_path' => 'patient-documents/'.$patient->id.'/../other.pdf']);
        $this->get($url)->assertNotFound();
    }

    public function test_patient_upload_rejects_executable_file_disguised_as_document(): void
    {
        Storage::fake('local');
        $this->signIn();
        $before = Patient::count();
        $this->post(route('admin.patients.store'), [
            'full_name' => 'Kiểm tra tệp',
            'profile_pdf' => UploadedFile::fake()->create('invalid.php', 1, 'application/x-php'),
        ])->assertSessionHasErrors('profile_pdf');
        $this->assertSame($before, Patient::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_login_limit_displays_vietnamese_errors_expires_and_clears_after_success(): void
    {
        $admin = User::where('business_code', 'QT000001')->firstOrFail();
        $admin->update(['password' => 'Valid-test-password']);
        $invalid = ['phone' => $admin->phone, 'password' => 'wrong-password'];
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('admin.login.submit'), $invalid)->assertRedirect();
        }
        $this->post(route('admin.login.submit'), $invalid)->assertStatus(429)
            ->assertHeader('Retry-After')->assertSee('Bạn đã đăng nhập sai quá nhiều lần.')
            ->assertDontSee('wrong-password');
        $this->postJson(route('admin.login.submit'), $invalid)->assertStatus(429)->assertJsonValidationErrors('phone');
        $this->travel(61)->seconds();
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->post(route('admin.login.submit'), $invalid)->assertRedirect();
        }
        $this->post(route('admin.login.submit'), ['phone' => $admin->phone, 'password' => 'Valid-test-password'])->assertRedirect(route('admin.dashboard'));
        auth()->logout();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('admin.login.submit'), $invalid)->assertRedirect();
        }
        $this->postJson(route('admin.login.submit'), $invalid)->assertStatus(429);
    }

    public function test_ip_login_limit_cannot_be_bypassed_by_rotating_phone_numbers(): void
    {
        for ($attempt = 0; $attempt < 30; $attempt++) {
            $this->post(route('admin.login.submit'), ['phone' => (string) (990000000 + $attempt), 'password' => 'invalid'])->assertRedirect();
        }
        $this->postJson(route('admin.login.submit'), ['phone' => '0999999999', 'password' => 'invalid'])->assertStatus(429);
    }

    public function test_filters_reject_arrays_invalid_ranges_and_dates_without_redirect_loops(): void
    {
        $this->signIn();
        foreach ([
            ['visits.index', ['doctor_id' => ['invalid']], 'doctor_id'],
            ['patients.index', ['search' => ['invalid']], 'search'],
            ['service-orders.index', ['q' => ['invalid']], 'q'],
            ['visits.index', ['date_from' => ['invalid'], 'date_to' => '2026-10-25'], 'date_from'],
            ['visits.index', ['date_from' => '2026-10-25', 'date_to' => '2026-10-01'], 'date_to'],
            ['medical-services.index', ['min_price' => ['invalid'], 'max_price' => '1000'], 'min_price'],
            ['medical-services.index', ['min_price' => '2000', 'max_price' => '1000'], 'max_price'],
            ['doctor-schedules.calendar', ['month' => '2026-99'], 'month'],
            ['visits.index', ['page' => '0'], 'page'],
        ] as [$name, $filters, $field]) {
            $this->getJson(route('admin.'.$name, $filters))->assertUnprocessable()->assertJsonValidationErrors($field);
        }
        $this->get(route('admin.visits.index', ['search' => ['invalid']]))
            ->assertRedirect(route('admin.visits.index'))->assertSessionHasErrors('search');
        $this->get(route('admin.visits.index'))->assertOk();
    }

    public function test_valid_bmi_is_saved_and_overflow_on_update_keeps_existing_measurements(): void
    {
        $this->signIn();
        $patient = Patient::where('patient_code', 'BN000001')->firstOrFail();
        $data = ['patient_id' => $patient->id, 'height_cm' => 170, 'weight_kg' => 65, 'recorded_at' => now()->subMinute()->format('Y-m-d\TH:i')];
        $this->post(route('admin.personal-health-records.store'), $data)->assertSessionHasNoErrors()->assertRedirect();
        $record = PersonalHealthRecord::where('patient_id', $patient->id)->latest('id')->firstOrFail();
        $this->assertSame(22.49, (float) $record->bmi);
        $this->put(route('admin.personal-health-records.update', $record), [...$data, 'height_cm' => 20, 'weight_kg' => 500])
            ->assertSessionHasErrors('weight_kg');
        $this->assertSame(170.0, (float) $record->fresh()->height_cm);
        $this->assertSame(65.0, (float) $record->fresh()->weight_kg);
        $this->assertSame(22.49, (float) $record->fresh()->bmi);
    }

    public function test_only_closed_visit_prescription_is_read_only_in_ui_and_backend(): void
    {
        $this->signIn();
        $closed = Prescription::whereHas('medicalVisit.status', fn ($query) => $query->where('name', 'COMPLETED'))->firstOrFail();
        $this->get(route('admin.prescriptions.edit', $closed))->assertForbidden();
        $this->get(route('admin.prescriptions.show', $closed))->assertOk()
            ->assertDontSee('href="'.route('admin.prescriptions.edit', $closed).'"', false);
        $visit = MedicalVisit::where('visit_code', 'LK000004')->firstOrFail();
        $open = Prescription::create(['prescription_code' => 'DTREGRESSION', 'medical_visit_id' => $visit->id,
            'doctor_id' => $visit->doctor_id, 'prescribed_at' => now(), 'status' => 'ACTIVE']);
        $this->get(route('admin.prescriptions.edit', $open))->assertOk();
        $this->patch(route('admin.prescriptions.update', $open), ['prescribed_at' => now()->format('Y-m-d H:i:s'),
            'status' => 'ACTIVE', 'general_instructions' => 'Hướng dẫn trong khi đang khám', 'confirm_update' => 1])
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('Hướng dẫn trong khi đang khám', $open->fresh()->general_instructions);
    }
}
