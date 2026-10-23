<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Doctor;
use App\Models\PaymentStatus;
use App\Models\Prescription;
use App\Models\ServiceResult;
use App\Models\ServiceStatus;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminPresentationConsistencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail());
    }

    private function xpath(string $html): DOMXPath
    {
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return new DOMXPath($document);
    }

    public function test_identical_actions_share_names_and_button_variants_across_every_admin_view(): void
    {
        $variants = ['Sửa' => 'secondary', 'Chi tiết' => 'detail', '← Quay lại' => 'secondary',
            'Hủy' => 'secondary', 'Lưu' => 'primary', 'Cập nhật' => 'primary', 'Lọc' => 'primary', 'Xóa bộ lọc' => 'secondary',
            'Trước' => 'secondary', 'Sau' => 'secondary', 'Đăng xuất' => 'secondary'];
        $checked = 0;
        foreach (File::allFiles(resource_path('views/admin')) as $file) {
            $source = file_get_contents($file->getPathname());
            // Breadcrumbs describe navigation hierarchy, not action buttons.
            $source = preg_replace('/<nav\b[^>]*>[\s\S]*?<\/nav>/u', '', $source);
            preg_match_all('/<(a|button|label)\b(?:"[^"]*"|\x27[^\x27]*\x27|[^\x27">])*?>([\s\S]*?)<\/\1>/u', $source, $tags, PREG_SET_ORDER);
            foreach ($tags as $tag) {
                $label = trim(preg_replace('/\s+/u', ' ', strip_tags(preg_replace('/{{[\s\S]*?}}|{{--[\s\S]*?--}}/u', '', $tag[2]))));
                if (! isset($variants[$label])) {
                    continue;
                }
                preg_match('/\bclass="([^"]*)"/', $tag[0], $classes);
                $this->assertStringContainsString('admin-btn admin-btn-'.$variants[$label], $classes[1] ?? '', $file->getRelativePathname().': '.$label);
                $checked++;
            }
            $visible = strip_tags(preg_replace('/{{--[\s\S]*?--}}/u', '', $source));
            preg_match_all('/\b(?:hard-delete|soft-delete|Audit Log|prescription_items|module)\b/i', $visible, $untranslated);
            $this->assertSame([], $untranslated[0], $file->getRelativePathname());
        }
        $this->assertGreaterThan(150, $checked);
        $pagination = view('admin.layouts.partials.pagination', [
            'paginator' => new LengthAwarePaginator(range(11, 20), 30, 10, 2),
            'elements' => [[1 => '/?page=1', 2 => '/?page=2', 3 => '/?page=3']],
        ])->render();
        $xpath = $this->xpath($pagination);
        $this->assertCount(1, $xpath->query('//span[@aria-current="page" and contains(@class,"admin-btn-primary")]'));
        $this->assertCount(2, $xpath->query('//a[@aria-label and contains(@class,"admin-btn-secondary")]'));
    }

    public function test_status_columns_on_all_index_pages_use_shared_badges(): void
    {
        $issues = [];
        foreach (glob(resource_path('views/admin/*/index.blade.php')) as $file) {
            $name = 'admin.'.basename(dirname($file)).'.index';
            if (! Route::has($name)) {
                continue;
            }
            $xpath = $this->xpath($this->get(route($name))->assertOk()->getContent());
            $this->assertCount(1, $xpath->query('//main//h2[starts-with(normalize-space(.),"Danh sách ")]'), $name.': tiêu đề danh sách');
            if (str_contains($name, '-statuses.index')) {
                $labels = [];
                foreach ($xpath->query('//main//table/thead/tr/th') as $header) {
                    $labels[] = trim(preg_replace('/\s+/u', ' ', $header->textContent));
                }
                $this->assertSame(['STT', 'Tên trạng thái', 'Mô tả', 'Thứ tự xử lý',
                    $name === 'admin.payment-statuses.index' ? 'Cho phép thực hiện dịch vụ' : 'Cho phép chuyển trạng thái',
                    'Trạng thái', 'Thao tác'], $labels, $name);
                $this->assertGreaterThan(0, $xpath->query('//main//span[@class="admin-process-order"]')->length, $name);
            }
            foreach ($xpath->query('//main//table') as $table) {
                $headers = $xpath->query('.//thead/tr/th', $table);
                foreach ($headers as $index => $header) {
                    $label = trim(preg_replace('/\s+/u', ' ', $header->textContent));
                    if ($label === 'Hoạt động') {
                        $issues[] = $name.': cột Hoạt động chưa thống nhất';
                    }
                    if (! preg_match('/^(?:Trạng thái|Tên trạng thái)(?:\s|$)/u', $label)) {
                        continue;
                    }
                    foreach ($xpath->query('.//tbody/tr', $table) as $row) {
                        $cells = $xpath->query('./td', $row);
                        if ($cells->length !== $headers->length) {
                            continue;
                        }
                        if (! $xpath->query('.//span[contains(@class,"admin-badge")]', $cells->item($index))->length) {
                            $issues[] = $name.': '.$label.' — '.trim($cells->item($index)->textContent);
                        }
                    }
                }
            }
        }
        $this->assertSame([], array_values(array_unique($issues)));
    }

    public function test_audit_details_are_readable_escaped_and_do_not_rewrite_history(): void
    {
        $result = ServiceResult::query()->firstOrFail();
        $values = ['result_summary' => '<script>test-audit</script>', 'service_order_item_id' => $result->service_order_item_id,
            'payment_status_id' => PaymentStatus::where('name', 'PAID')->value('id'), 'password' => 'audit-secret', 'result_pdf_path' => 'service-results/private-file.pdf'];
        $log = AuditLog::create(['action' => 'CREATE', 'entity_type' => 'service_results', 'entity_id' => $result->id,
            'old_values' => [], 'new_values' => $values, 'description' => 'Kiểm thử hiển thị nhật ký', 'performed_at' => now()]);
        $storedValues = $log->fresh()->new_values;
        $this->get(route('admin.audit-logs.show', $log))->assertOk()->assertSee('Tóm tắt kết quả')
            ->assertSee('Đã thanh toán')->assertSee('Chưa có nội dung được ghi nhận.')
            ->assertSee('<script>test-audit</script>')->assertDontSee('<script>test-audit</script>', false)
            ->assertDontSee('audit-secret')->assertDontSee('private-file.pdf')->assertDontSee('<pre', false);
        $this->assertSame($storedValues, $log->fresh()->new_values);
    }

    public function test_detail_status_values_and_sidebar_navigation_keep_their_shared_presentation(): void
    {
        $tables = [
            'users' => 'users', 'administrators' => 'administrators', 'roles' => 'roles', 'patients' => 'patients',
            'doctors' => 'doctors', 'employees' => 'employees', 'specialties' => 'specialties',
            'examination-rooms' => 'examination_rooms', 'service-groups' => 'service_groups',
            'medical-services' => 'medical_services', 'disease-groups' => 'disease_groups', 'diseases' => 'diseases',
            'medicines' => 'medicines', 'appointment-statuses' => 'appointment_statuses', 'doctor-schedules' => 'doctor_schedules',
            'appointments' => 'appointments', 'visit-statuses' => 'visit_statuses', 'queue-statuses' => 'queue_statuses',
            'visits' => 'medical_visits', 'queues' => 'examination_room_queues', 'service-statuses' => 'service_statuses',
            'payment-statuses' => 'payment_statuses', 'service-orders' => 'service_orders', 'service-results' => 'service_results',
            'prescriptions' => 'prescriptions', 'payables' => 'payables', 'payment-transactions' => 'payment_transactions',
        ];
        $issues = [];
        $checked = 0;
        foreach ($tables as $page => $table) {
            $xpath = $this->xpath($this->get(route('admin.'.$page.'.show', DB::table($table)->value('id')))->assertOk()->getContent());
            $this->assertCount(0, $xpath->query('//aside//a[contains(@class,"admin-btn")]'), $page.': liên kết điều hướng không phải nút thao tác');
            foreach ($xpath->query('//main//*[self::dt or self::span or self::strong]') as $label) {
                $text = trim(preg_replace('/\s+/u', ' ', $label->textContent));
                if (! preg_match('/^(?:Trạng thái(?: dịch vụ| thanh toán| sử dụng)?|Tên trạng thái)$/u', $text)) {
                    continue;
                }
                $value = $xpath->query('following-sibling::*[1]', $label)->item(0);
                if (! $value) {
                    continue;
                }
                if (! str_contains($value->getAttribute('class'), 'admin-badge') && ! $xpath->query('.//span[contains(@class,"admin-badge")]', $value)->length) {
                    $issues[] = $page.': '.$text.' — '.trim($value->textContent);
                }
                $checked++;
            }
        }
        $this->assertGreaterThan(15, $checked);
        $this->assertSame([], $issues);
    }

    public function test_doctor_and_linked_profile_pages_offer_real_navigation_instead_of_future_placeholders(): void
    {
        $doctor = Doctor::where('doctor_code', 'BS000001')->firstOrFail();
        $this->get(route('admin.doctors.show', $doctor))->assertOk()->assertDontSee('sẽ được')
            ->assertSee(route('admin.doctor-schedules.index', ['doctor_id' => $doctor->id]))
            ->assertSee(route('admin.appointments.index', ['doctor_id' => $doctor->id]))
            ->assertSee(route('admin.visits.index', ['doctor_id' => $doctor->id]));
        $response = $this->get(route('admin.users.show', $doctor->user))->assertOk()->assertSee('Xem hồ sơ')->assertSee(route('admin.doctors.show', $doctor));
        $this->assertDoesNotMatchRegularExpression('/\bmodule\b/i', strip_tags($response->getContent()));
    }

    public function test_prescription_items_have_consistent_columns_and_keep_medication_details(): void
    {
        $prescription = Prescription::with('items.medicine')->firstOrFail();
        $response = $this->get(route('admin.prescriptions.show', $prescription))->assertOk();
        $xpath = $this->xpath($response->getContent());
        $rows = $xpath->query('//table[contains(@class,"admin-prescription-table")]/tbody/tr');
        $this->assertCount($prescription->items->count(), $rows);
        foreach ($rows as $row) {
            $this->assertCount(9, $xpath->query('./td', $row));
        }
        foreach ($prescription->items as $item) {
            $response->assertSee($item->medicine->name)->assertSee($item->dose_per_use)->assertSee($item->usage_method);
        }
        $response->assertSee('Hoạt chất:')->assertSee('Hàm lượng:')->assertSee('Đường dùng:');
    }

    public function test_status_configuration_header_reflects_the_actual_active_flag(): void
    {
        $status = ServiceStatus::where('name', 'PENDING')->firstOrFail();
        foreach ([true, false] as $active) {
            $status->update(['is_active' => $active]);
            $xpath = $this->xpath($this->get(route('admin.service-statuses.show', $status))->assertOk()->getContent());
            $code = $active ? 'ACTIVE' : 'INACTIVE';
            $this->assertCount(1, $xpath->query('//div[contains(@class,"status-show-header")]//span[@data-status="'.$code.'"]'));
        }
    }
}
