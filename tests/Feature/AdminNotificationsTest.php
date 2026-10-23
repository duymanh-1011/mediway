<?php

namespace Tests\Feature;

use App\Http\Requests\Admin\StorePersonalHealthRecordRequest;
use App\Models\MedicalService;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderProgress;
use App\Models\ServiceStatus;
use App\Models\User;
use App\Models\VisitStatus;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class AdminNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail());
        app()->setLocale('vi');
    }

    public function test_invalid_date_ranges_have_a_clear_shared_message_on_admin_pages(): void
    {
        $message = 'Vui lòng chọn “Đến ngày” trùng hoặc sau “Từ ngày”.';
        foreach (['audit-logs', 'appointments', 'visits', 'clinical-notes', 'diagnoses', 'conclusions',
            'service-orders', 'service-results', 'prescriptions', 'payables', 'payment-transactions', 'personal-health-records',
        ] as $module) {
            $this->getJson(route('admin.'.$module.'.index', ['date_from' => '2026-10-22', 'date_to' => '2026-10-01']))
                ->assertUnprocessable()->assertJsonPath('errors.date_to.0', $message);
        }
        $url = route('admin.audit-logs.index');
        $this->get($url.'?date_from=2026-10-22&date_to=2026-10-01')->assertRedirect($url)
            ->assertSessionHasErrors(['date_to' => $message]);
        $response = $this->withCookie(config('session.cookie'), session()->getId())->get($url)->assertOk();
        $this->assertStringContainsString($message, html_entity_decode(strip_tags($response->getContent())));
        $response->assertDontSee('đến ngày phải từ từ ngày trở đi.');
    }

    public function test_equal_dates_and_zero_prices_are_still_allowed(): void
    {
        $this->getJson(route('admin.audit-logs.index', ['date_from' => '2026-10-22', 'date_to' => '2026-10-22']))->assertOk();
        $this->getJson(route('admin.medical-services.index', ['min_price' => '0', 'max_price' => '0']))->assertOk();
        $this->getJson(route('admin.medical-services.index', ['min_price' => '2000', 'max_price' => '1000']))
            ->assertUnprocessable()->assertJsonPath('errors.max_price.0', '“Giá đến” không được thấp hơn “Giá từ”. Vui lòng kiểm tra lại hai mức giá.');
    }

    public function test_validation_messages_do_not_expose_placeholders_field_codes_or_raw_date_formats(): void
    {
        foreach ([
            [['date_to' => 'invalid'], ['date_to' => 'date_format:Y-m-d']],
            [['month' => '2026-99'], ['month' => 'date_format:Y-m']],
            [['recorded_at' => 'invalid'], ['recorded_at' => 'date_format:Y-m-d\TH:i']],
            [['date_of_birth' => now()->addDay()->toDateString()], ['date_of_birth' => 'before_or_equal:today']],
            [['recorded_at' => now()->addHour()->format('Y-m-d H:i:s')], ['recorded_at' => 'before_or_equal:now']],
            [['confirm_update' => '0'], ['confirm_update' => 'accepted']],
            [['follow_up_date' => '', 'has_follow_up' => '1'], ['follow_up_date' => 'required_if:has_follow_up,1']],
        ] as [$data, $rules]) {
            $validator = Validator::make($data, $rules);
            $this->assertTrue($validator->fails());
            $message = implode(' ', $validator->errors()->all());
            $this->assertDoesNotMatchRegularExpression('/:(?:attribute|date|value|format)|date_to|date_of_birth|recorded_at|has_follow_up|confirm_update|Y-m-d|\btoday\b|\bnow\b|xác nhận xác nhận/u', $message);
            $this->assertStringContainsString('Vui lòng', $message);
        }
    }

    public function test_invalid_filter_values_tell_the_user_which_field_to_check(): void
    {
        $this->getJson(route('admin.visits.index', ['doctor_id' => ['invalid']]))->assertUnprocessable()
            ->assertJsonPath('errors.doctor_id.0', 'Giá trị ở mục “bác sĩ” chưa hợp lệ. Vui lòng chọn lại hoặc nhập số nguyên.');
        $this->getJson(route('admin.users.index', ['page' => 'invalid']))->assertUnprocessable()
            ->assertJsonPath('errors.page.0', 'Số trang chưa hợp lệ. Vui lòng dùng các nút phân trang bên dưới danh sách.');
    }

    public function test_health_measurement_messages_state_the_allowed_bounds_without_changing_them(): void
    {
        $request = new StorePersonalHealthRecordRequest;
        foreach ([
            'height_cm' => [20, 250, 'cm'],
            'weight_kg' => [0.5, 500, 'kg'],
            'systolic_blood_pressure' => [40, 300, 'mmHg'],
            'diastolic_blood_pressure' => [20, 200, 'mmHg'],
            'heart_rate' => [20, 250, 'lần/phút'],
            'body_temperature' => [30, 45, '°C'],
            'respiratory_rate' => [5, 100, 'lần/phút'],
            'blood_glucose' => [0, 1000, 'đơn vị ghi trên biểu mẫu'],
        ] as $field => [$minimum, $maximum, $unit]) {
            $rules = [$field => $request->rules()[$field]];
            foreach ([$minimum - 1, $maximum + 1] as $value) {
                $validator = Validator::make([$field => $value], $rules, $request->messages());
                $this->assertTrue($validator->fails());
                $message = $validator->errors()->first($field);
                $this->assertStringContainsString('từ '.$minimum.' đến '.$maximum, $message);
                $this->assertStringContainsString($unit, $message);
                $this->assertStringContainsString('kiểm tra lại', $message);
            }
            foreach ([$minimum, $maximum] as $value) {
                $this->assertTrue(Validator::make([$field => $value], $rules, $request->messages())->passes());
            }
        }
    }

    public function test_catalog_messages_and_confirmation_name_the_affected_record(): void
    {
        $service = MedicalService::where('service_code', 'DVXN002')->firstOrFail();
        $this->from(route('admin.medical-services.index'))->patch(route('admin.catalog.status', ['catalog' => 'medical-services', 'id' => $service->id]), ['status' => 'INACTIVE'])
            ->assertSessionHas('success', 'Đã ngừng hoạt động dịch vụ y tế “'.$service->name.'”.');
        $this->assertSame('INACTIVE', $service->fresh()->status);
        $this->get(route('admin.medical-services.index'))->assertOk()
            ->assertSee('Kích hoạt lại mục “'.$service->name.'” để tiếp tục sử dụng?');
        $this->patch(route('admin.catalog.status', ['catalog' => 'medical-services', 'id' => $service->id]), ['status' => 'ACTIVE'])
            ->assertSessionHas('success', 'Đã kích hoạt lại dịch vụ y tế “'.$service->name.'”.');
    }

    public function test_cancellation_notifications_distinguish_pending_services_from_queued_return(): void
    {
        $order = ServiceOrder::where('service_order_code', 'CD000004')->firstOrFail();
        $order->medicalVisit->update(['visit_status_id' => VisitStatus::where('name', 'WAITING_FOR_SERVICE')->value('id')]);
        $items = $order->items()->with('payable')->get();
        foreach ($items as $index => $item) {
            $response = $this->patch(route('admin.payables.cancel', $item->payable->id), ['confirm_cancel' => 1, 'cancel_reason' => 'Người bệnh không thực hiện dịch vụ']);
            $response->assertSessionHasNoErrors()->assertSessionMissing('error');
            $notice = session('success');
            if ($index < $items->count() - 1) {
                $this->assertStringContainsString('dịch vụ cần hoàn tất', $notice);
                $this->assertStringContainsString('chưa được xếp hàng', $notice);
            } else {
                $this->assertStringContainsString('Bệnh nhân đã được xếp vào hàng đợi quay lại bác sĩ.', $notice);
                $this->assertTrue($order->medicalVisit->queues()->where('queue_type', 'RETURN_AFTER_SERVICE')->exists());
            }
            $this->assertStringNotContainsString('Nếu không còn', $notice);
        }
        $this->assertSame('WAITING_FOR_SERVICE', $order->medicalVisit->fresh()->status->name);
    }

    public function test_completion_and_payment_notifications_are_plain_language_and_reflect_real_progress(): void
    {
        $order = ServiceOrder::where('service_order_code', 'CD000004')->firstOrFail();
        $item = $order->items()->firstOrFail();
        $this->post(route('admin.payables.payment-transactions.store', $item->payable), ['payment_method' => 'CASH', 'confirm_payment' => 1])
            ->assertSessionHas('success', 'Đã ghi nhận thanh toán thành công cho khoản thu này.');
        $this->post(route('admin.service-order-items.start', $item))->assertSessionHasNoErrors()->assertSessionMissing('error');
        $this->post(route('admin.service-order-items.service-results.store', $item), ['result_summary' => 'Kết quả thực tế đã nhập', 'confirm_create' => 1])
            ->assertSessionHasNoErrors();
        $this->get(route('admin.service-orders.show', $order))->assertOk()->assertDontSee('chuyển sang COMPLETED');
        $this->post(route('admin.service-order-items.complete', $item))->assertSessionHasNoErrors()->assertSessionMissing('error');
        $this->assertStringContainsString('dịch vụ cần hoàn tất', session('success'));
        $this->assertStringNotContainsString('Nếu không còn', session('success'));
    }

    public function test_missing_status_configuration_gives_a_readable_next_step_without_starting_service(): void
    {
        $order = ServiceOrder::where('service_order_code', 'CD000004')->firstOrFail();
        $item = $order->items()->firstOrFail();
        $this->post(route('admin.payables.payment-transactions.store', $item->payable), ['payment_method' => 'CASH', 'confirm_payment' => 1])->assertSessionHasNoErrors();
        ServiceStatus::where('name', 'PROCESSING')->update(['is_active' => false]);
        $this->post(route('admin.service-order-items.start', $item))
            ->assertSessionHas('error', 'Chưa có trạng thái “Đang thực hiện” đang được sử dụng. Vui lòng kiểm tra mục Trạng thái dịch vụ.');
        $this->assertSame('PENDING', $item->fresh()->serviceStatus->name);
    }

    public function test_progress_notification_is_read_only(): void
    {
        $order = ServiceOrder::where('service_order_code', 'CD000004')->firstOrFail();
        $queues = $order->medicalVisit->queues()->count();
        $status = $order->status;
        $message = app(ServiceOrderProgress::class)->returnQueueMessage($order->medical_visit_id);
        $this->assertStringContainsString('dịch vụ cần hoàn tất', $message);
        $this->assertSame($queues, $order->medicalVisit->queues()->count());
        $this->assertSame($status, $order->fresh()->status);
    }

    public function test_displayed_message_literals_do_not_mix_backend_status_codes_with_vietnamese(): void
    {
        foreach (array_merge(glob(app_path('Http/Controllers/Admin/*.php')), glob(app_path('Http/Requests/Admin/*.php'))) as $file) {
            foreach (token_get_all(file_get_contents($file)) as $token) {
                if (! is_array($token) || $token[0] !== T_CONSTANT_ENCAPSED_STRING || ! preg_match('/[\x{00C0}-\x{024F}\x{1E00}-\x{1EFF}]/u', $token[1])) {
                    continue;
                }
                $this->assertDoesNotMatchRegularExpression('/\b(?:ACTIVE|INACTIVE|PENDING|PROCESSING|COMPLETED|CANCELLED|PAID|UNPAID|WAITING|CALLED|IN_SERVICE|RETURN_AFTER_SERVICE|module)\b/', $token[1], basename($file).':'.$token[2]);
            }
        }
    }
}
