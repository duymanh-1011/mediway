<?php

namespace Tests\Feature;

use App\Models\AdminDisplay;
use App\Models\MedicalVisit;
use App\Models\Payable;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminViewsTest extends TestCase
{
    use RefreshDatabase;

    private const UNTRANSLATED_UI_PATTERN = '/\b(ACTIVE|INACTIVE|PENDING|PROCESSING|COMPLETED|CANCELLED|PAID|UNPAID|WAITING|CALLED|IN_SERVICE|RETURN_AFTER_SERVICE|Dashboard|Administration System|KIOSK|Kiosk|SUCCESS|Slot|slot|Check-in|check-in|IP Address|User Agent|FILE|file)\b/';

    public function test_admin_get_routes_render_without_server_errors(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $this->get(route('admin.login'))->assertOk();
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail());
        $tables = [
            'users' => 'users', 'administrators' => 'administrators', 'roles' => 'roles', 'patients' => 'patients',
            'personal-health-records' => 'personal_health_records', 'doctors' => 'doctors', 'employees' => 'employees',
            'specialties' => 'specialties', 'examination-rooms' => 'examination_rooms', 'service-groups' => 'service_groups',
            'medical-services' => 'medical_services', 'disease-groups' => 'disease_groups', 'diseases' => 'diseases', 'medicines' => 'medicines',
            'appointment-statuses' => 'appointment_statuses', 'doctor-schedules' => 'doctor_schedules', 'appointment-time-slots' => 'appointment_time_slots',
            'appointments' => 'appointments', 'visit-statuses' => 'visit_statuses', 'queue-statuses' => 'queue_statuses',
            'visits' => 'medical_visits', 'queues' => 'examination_room_queues', 'clinical-notes' => 'visit_clinical_notes',
            'diagnoses' => 'visit_diagnoses', 'conclusions' => 'visit_conclusions', 'service-statuses' => 'service_statuses',
            'payment-statuses' => 'payment_statuses', 'service-orders' => 'service_orders', 'service-results' => 'service_results',
            'prescriptions' => 'prescriptions', 'payables' => 'payables', 'payment-transactions' => 'payment_transactions', 'audit-logs' => 'audit_logs',
        ];
        $checked = 0;
        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();
            if (! $name || ! str_starts_with($name, 'admin.') || ! in_array('GET', $route->methods(), true) || str_contains($name, 'document')) {
                continue;
            }
            $module = explode('.', $name)[1];
            $parameters = [];
            foreach ($route->parameterNames() as $parameter) {
                $table = match ($parameter) {
                    'visit', 'medicalVisit' => 'medical_visits',
                    'doctorSchedule' => 'doctor_schedules', 'appointmentTimeSlot' => 'appointment_time_slots',
                    'serviceOrderItem' => 'service_order_items', 'payable' => 'payables',
                    default => $tables[$module] ?? null,
                };
                $this->assertNotNull($table, 'Thiếu fixture cho route '.$name);
                $parameters[$parameter] = DB::table($table)->value('id');
            }
            $response = $this->get(route($name, $parameters));
            if ($response->getStatusCode() >= 500) {
                $this->withoutExceptionHandling()->get(route($name, $parameters));
            }
            $this->assertLessThan(500, $response->getStatusCode(), $name.': '.substr(strip_tags($response->getContent()), 0, 300));
            if ($response->getStatusCode() === 200) {
                $text = $this->interfaceText($response->getContent());
                preg_match_all(self::UNTRANSLATED_UI_PATTERN, $text, $matches);
                $this->assertSame([], array_values(array_unique($matches[0])), 'Nhãn chưa Việt hóa: '.$name);
            }
            $checked++;
        }
        $this->assertGreaterThan(100, $checked);
    }

    public function test_catalog_and_status_pages_display_vietnamese_labels(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail());
        $untranslated = [];
        foreach (['dashboard', 'medical-services.index', 'service-groups.index', 'appointments.index', 'visits.index', 'queues.index', 'service-orders.index', 'payables.index', 'payment-transactions.index', 'appointment-statuses.index', 'visit-statuses.index', 'queue-statuses.index', 'payment-statuses.index', 'service-statuses.index'] as $route) {
            $response = $this->get(route('admin.'.$route))->assertOk();
            $visibleText = $this->interfaceText($response->getContent());
            preg_match_all(self::UNTRANSLATED_UI_PATTERN, $visibleText, $matches);
            if ($matches[0]) {
                $untranslated[$route] = array_values(array_unique($matches[0]));
            }
        }
        $this->assertSame([], $untranslated);
    }

    public function test_localization_covers_the_unpaid_payment_confirmation_form(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail());
        $payable = Payable::where('payable_code', 'PT000004')->firstOrFail();

        $response = $this->get(route('admin.payables.payment-transactions.create', $payable))->assertOk();
        $text = $this->interfaceText($response->getContent());
        $this->assertStringContainsString('giao dịch thành công', preg_replace('/\s+/', ' ', $text));
        $this->assertStringNotContainsString('SUCCESS', $text);
        $this->assertStringNotContainsString('PAID', $text);
        $this->assertSame('UNPAID', $payable->fresh()->paymentStatus->name);
    }

    public function test_localization_covers_employee_validation_messages_and_field_names(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail());

        $this->from(route('admin.employees.create'))->post(route('admin.employees.store'), [
            'full_name' => 'Nhân viên kiểm thử', 'status' => 'ACTIVE', 'department' => str_repeat('a', 121),
        ])->assertRedirect(route('admin.employees.create'))->assertSessionHasErrors([
            'phone' => 'Vui lòng chọn tài khoản nhân viên có sẵn hoặc nhập số điện thoại mới.',
            'department' => 'phòng ban không được quá 120 ký tự.',
        ]);

        $this->withViewErrors(session('errors')->getBag('default')->getMessages())
            ->view('admin.employees.create', ['availableUsers' => collect()])
            ->assertSee('phòng ban không được quá 120 ký tự.')
            ->assertDontSee('tài khoản Employee');
    }

    public function test_localization_translates_reception_sources_and_historical_descriptions_without_rewriting_data(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail());
        $visit = MedicalVisit::where('reception_source', 'KIOSK')->firstOrFail();
        $log = DB::table('audit_logs')->where('description', 'Kiosk xác nhận check-in')->first();
        $this->assertNotNull($log);

        $this->get(route('admin.visits.index'))->assertOk()
            ->assertSee('Máy tự phục vụ')->assertSee('value="KIOSK"', false);
        $this->get(route('admin.visits.show', $visit))->assertOk()->assertSee('Máy tự phục vụ');
        $this->get(route('admin.audit-logs.show', $log->id))->assertOk()
            ->assertSee('Máy tự phục vụ xác nhận tiếp nhận')->assertSee('Địa chỉ IP')->assertSee('Thông tin trình duyệt');

        $this->assertSame('KIOSK', $visit->fresh()->reception_source);
        $this->assertDatabaseHas('audit_logs', ['id' => $log->id, 'description' => 'Kiosk xác nhận check-in']);
        $this->assertSame('Chưa có vai trò Nhân viên.', AdminDisplay::text('Chưa có vai trò Employee.'));
    }

    private function interfaceText(string $html): string
    {
        $html = preg_replace('/<(script|style)\b[^>]*>[\s\S]*?<\/\1>/i', '', $html);
        preg_match_all('/\b(?:placeholder|title|aria-label|alt)\s*=\s*["\']([^"\']*)["\']/u', $html, $attributes);

        return html_entity_decode(strip_tags($html).' '.implode(' ', $attributes[1]));
    }
}
