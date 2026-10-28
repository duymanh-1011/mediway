<?php

namespace Tests\Feature;

use App\Models\AdminDisplay;
use App\Models\EmployeeType;
use App\Models\MedicalVisit;
use App\Models\Payable;
use App\Models\ServiceResult;
use App\Models\User;
use App\Models\VisitStatus;
use Database\Seeders\DatabaseSeeder;
use DOMDocument;
use DOMXPath;
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
            ->view('admin.employees.create', ['availableUsers' => collect(), 'employeeTypes' => EmployeeType::orderBy('display_order')->get()])
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

    public function test_service_result_actions_use_shared_buttons_and_edit_has_a_header_back_link(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail());
        $result = ServiceResult::query()->firstOrFail();
        // This presentation fixture must remain editable; closed visits are tested separately.
        $result->serviceOrderItem->serviceOrder->medicalVisit->update([
            'visit_status_id' => VisitStatus::where('name', 'IN_EXAMINATION')->value('id'),
        ]);
        $pages = [];
        foreach (['show', 'edit'] as $page) {
            $response = $this->get(route('admin.service-results.'.$page, $result->id))->assertOk();
            $document = new DOMDocument;
            $previous = libxml_use_internal_errors(true);
            $document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent(), LIBXML_NONET);
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
            $pages[$page] = new DOMXPath($document);
        }
        $editLink = $pages['show']->query('//main//a[@href="'.route('admin.service-results.edit', $result->id).'" and contains(@class,"admin-btn-secondary")]');
        $this->assertCount(1, $editLink);
        $this->assertSame('Sửa', trim($editLink->item(0)->textContent));
        $this->assertCount(1, $pages['show']->query('//main//a[@href="'.route('admin.service-results.index').'" and contains(@class,"admin-btn-secondary")]'));
        $backLink = $pages['edit']->query('//main//div[contains(@class,"result-header")]//a[@href="'.route('admin.service-results.show', $result->id).'" and contains(@class,"admin-btn-secondary") and not(ancestor::form)]');
        $this->assertCount(1, $backLink);
        $this->assertSame('← Quay lại', trim($backLink->item(0)->textContent));
        $this->assertCount(1, $pages['edit']->query('//main//form[@action="'.route('admin.service-results.update', $result->id).'"]//button[@type="submit" and contains(@class,"admin-btn-primary")]'));
        $this->assertCount(0, $pages['edit']->query('//nav[contains(@class,"admin-breadcrumb")]//a[contains(@class,"admin-btn")]'));
    }

    private function interfaceText(string $html): string
    {
        $html = preg_replace('/<(script|style)\b[^>]*>[\s\S]*?<\/\1>/i', '', $html);
        preg_match_all('/\b(?:placeholder|title|aria-label|alt)\s*=\s*["\']([^"\']*)["\']/u', $html, $attributes);

        return html_entity_decode(strip_tags($html).' '.implode(' ', $attributes[1]));
    }
}
