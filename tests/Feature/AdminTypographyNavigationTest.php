<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminTypographyNavigationTest extends TestCase
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
        $doc = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return new DOMXPath($doc);
    }

    public function test_business_codes_share_one_component_across_lists_and_detail_pages(): void
    {
        $tables = ['users' => ['users', 'business_code'], 'administrators' => ['administrators', null],
            'patients' => ['patients', 'patient_code'], 'doctors' => ['doctors', 'doctor_code'],
            'employees' => ['employees', 'employee_code'], 'specialties' => ['specialties', 'specialty_code'],
            'examination-rooms' => ['examination_rooms', 'room_code'], 'service-groups' => ['service_groups', 'service_group_code'],
            'medical-services' => ['medical_services', 'service_code'], 'disease-groups' => ['disease_groups', 'disease_group_code'],
            'diseases' => ['diseases', 'disease_code'], 'medicines' => ['medicines', 'medicine_code'],
            'doctor-schedules' => ['doctor_schedules', null], 'appointments' => ['appointments', 'appointment_code'],
            'visits' => ['medical_visits', 'visit_code'], 'queues' => ['examination_room_queues', null],
            'service-orders' => ['service_orders', 'service_order_code'], 'service-results' => ['service_results', null],
            'prescriptions' => ['prescriptions', 'prescription_code'], 'payables' => ['payables', 'payable_code'],
            'payment-transactions' => ['payment_transactions', 'transaction_code'], 'audit-logs' => ['audit_logs', null]];
        $codes = [];
        foreach ($tables as [$table, $column]) {
            if ($column) {
                $codes = [...$codes, ...DB::table($table)->pluck($column)->filter()->all()];
            }
        }
        $codes = array_flip($codes);
        $issues = [];
        $checked = 0;
        foreach ($tables as $module => [$table]) {
            foreach (['index', 'show'] as $action) {
                $route = 'admin.'.$module.'.'.$action;
                if (! Route::has($route)) {
                    continue;
                }
                $response = $this->get(route($route, $action === 'show' ? DB::table($table)->value('id') : []))->assertOk();
                $xpath = $this->xpath($response->getContent());
                foreach ($xpath->query('//main//text()[not(ancestor::script) and not(ancestor::option) and not(ancestor::textarea)]') as $text) {
                    if (! isset($codes[trim($text->textContent)])) {
                        continue;
                    }
                    if (! $xpath->query('ancestor::*[contains(concat(" ",normalize-space(@class)," ")," admin-record-code ")]', $text)->length) {
                        $issues[] = $route.': '.trim($text->textContent);
                    }
                    $checked++;
                }
                $this->assertCount(0, $xpath->query('//option//span | //textarea//span'), $route.': không chèn định dạng vào ô nhập/chọn');
                $this->assertCount(0, $xpath->query('//*[@href and contains(@href,"<span")] | //input[contains(@value,"<span")]'), $route);
            }
        }
        $this->assertGreaterThan(100, $checked);
        $this->assertSame([], array_values(array_unique($issues)));
    }

    public function test_sidebar_controls_are_shared_accessible_and_do_not_change_navigation(): void
    {
        foreach (['admin.dashboard', 'admin.payment-transactions.index', 'admin.service-orders.index', 'admin.doctors.create'] as $route) {
            $xpath = $this->xpath($this->get(route($route))->assertOk()->getContent());
            $this->assertCount(1, $xpath->query('//aside[@id="admin-sidebar" and @aria-label]'));
            $this->assertCount(1, $xpath->query('//header//button[@data-sidebar-toggle and @type="button" and @aria-controls="admin-sidebar" and @aria-expanded]'));
            $this->assertCount(1, $xpath->query('//aside//button[@data-sidebar-close and @aria-label]'));
            $this->assertCount(1, $xpath->query('//button[contains(@class,"admin-sidebar-backdrop") and @hidden and @data-sidebar-close]'));
            $this->assertGreaterThan(20, $xpath->query('//aside//a[@href]')->length);
        }
    }

    public function test_shared_code_component_escapes_untrusted_values(): void
    {
        $html = view('admin.layouts.partials.record-code', ['code' => '<script>alert(1)</script>'])->render();
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }
}
