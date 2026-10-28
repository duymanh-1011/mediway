<?php

namespace Tests\Feature;

use App\Models\AdminDisplay;
use App\Models\DiseaseGroup;
use App\Models\Employee;
use App\Models\Patient;
use App\Models\ServiceGroup;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminFilterLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_filters_are_standalone_named_panels_with_shared_grid_and_actions(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail());
        $checked = 0;
        $pages = [];
        foreach (glob(resource_path('views/admin/*/index.blade.php')) as $file) {
            $name = 'admin.'.basename(dirname($file)).'.index';
            if (! Route::has($name)) {
                continue;
            }
            $pages[$name] = route($name);
        }
        $pages['admin.doctor-schedules.calendar'] = route('admin.doctor-schedules.calendar');
        $pages['admin.service-groups.show'] = route('admin.service-groups.show', ServiceGroup::where('service_group_code', 'XN')->firstOrFail());
        $pages['admin.disease-groups.show'] = route('admin.disease-groups.show', DiseaseGroup::where('disease_group_code', 'NBHH')->firstOrFail());
        $pages['admin.dashboard'] = route('admin.dashboard');
        $pages['admin.patients.show'] = route('admin.patients.show', Patient::firstOrFail());
        foreach ($pages as $name => $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $document = new DOMDocument;
            $previous = libxml_use_internal_errors(true);
            $document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET);
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
            $xpath = new DOMXPath($document);
            foreach ($xpath->query('//main//form[translate(@method,"get","GET")="GET"]') as $form) {
                $this->assertStringContainsString('admin-filter-form', $form->getAttribute('class'), $name);
                $panel = $form->parentNode;
                $this->assertSame('section', $panel->nodeName, $name);
                $this->assertStringContainsString('admin-filter-panel', $panel->getAttribute('class'), $name);
                $this->assertCount(1, $xpath->query('./header[contains(@class,"admin-filter-panel-header")]', $panel), $name);
                $heading = $xpath->query('./header/h2', $panel)->item(0);
                $description = $xpath->query('./header/p', $panel)->item(0);
                $this->assertNotNull($heading, $name);
                $this->assertNotNull($description, $name);
                $this->assertStringStartsWith($name === 'admin.dashboard' ? 'Ngày thống kê' : 'Bộ lọc ', trim($heading->textContent), $name);
                $this->assertSame(trim($heading->textContent), $panel->getAttribute('aria-label'), $name);
                $this->assertStringStartsWith($name === 'admin.dashboard' ? 'Chọn ngày ' : 'Lọc theo ', trim($description->textContent), $name);
                $this->assertCount(0, $xpath->query('.//table', $panel), $name);
                for ($parent = $panel->parentNode; $parent instanceof \DOMElement && $parent->tagName !== 'main'; $parent = $parent->parentNode) {
                    $classes = preg_split('/\s+/', trim($parent->getAttribute('class')));
                    foreach ($classes as $class) {
                        $this->assertFalse($class === 'users-panel' || preg_match('/(?:^|-)card$/', $class) === 1, $name.': bộ lọc còn nằm trong khối danh sách');
                    }
                }
                $actions = $xpath->query('./div[contains(@class,"admin-filter-actions")]', $form);
                $this->assertCount(1, $actions, $name);
                $controls = $xpath->query('.//input[not(@type="hidden")]|.//select', $form);
                $fields = $xpath->query('.//div[contains(concat(" ", normalize-space(@class), " "), " admin-filter-field ")]', $form);
                $this->assertSame((string) $fields->length, $form->getAttribute('data-filter-fields'), $name.': số trường bố cục phải khớp số trường thật');
                foreach ($controls as $control) {
                    $this->assertGreaterThan(0, $xpath->query('ancestor::div[contains(@class,"admin-filter-field")]', $control)->length, $name.': '.$control->getAttribute('name'));
                }
                $button = $xpath->query('.//button', $actions->item(0))->item(0);
                $this->assertSame($controls->length > 1 || $name === 'admin.dashboard' ? 'Lọc' : 'Tìm kiếm', trim($button->textContent), $name);
                $this->assertSame('Xóa bộ lọc', trim($xpath->query('.//a', $actions->item(0))->item(0)->textContent));
                $checked++;
            }
        }
        $this->assertSame(38, $checked);
    }

    public function test_filter_values_results_and_reset_link_are_preserved(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail());
        $employee = User::where('business_code', 'NV000004')->firstOrFail();
        $this->get(route('admin.users.index', [
            'search' => $employee->business_code, 'role_id' => $employee->role_id, 'status' => $employee->status,
        ]))->assertOk()
            ->assertViewHas('users', fn ($users): bool => $users->total() === 1 && $users->first()->id === $employee->id)
            ->assertSee('value="NV000004"', false)
            ->assertSee('href="'.route('admin.users.index').'"', false);

        $this->get(route('admin.users.index'))->assertOk()
            ->assertViewHas('users', fn ($users): bool => $users->total() > 1);
    }

    public function test_employee_type_labels_are_vietnamese_without_changing_values(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail());
        $html = $this->get(route('admin.employees.index'))->assertOk()->getContent();
        $text = html_entity_decode(strip_tags(preg_replace('/<script\b[^>]*>[\s\S]*?<\/script>/i', '', $html)));
        $this->assertStringNotContainsString('CASHIER', $text);
        $this->assertStringNotContainsString('LAB_TECHNICIAN', $text);
        $this->assertStringContainsString('Nhân viên thu ngân', $text);
        $this->assertStringContainsString('value="CASHIER"', $html);
    }

    public function test_employee_form_displays_vietnamese_but_persists_the_original_code(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail());
        $employee = Employee::where('employee_code', 'NV000004')->firstOrFail();
        $this->get(route('admin.employees.edit', $employee))->assertOk()->assertSee('value="CASHIER" selected', false)->assertSee('Nhân viên thu ngân');
        $this->put(route('admin.employees.update', $employee), ['full_name' => $employee->full_name, 'status' => $employee->status, 'employee_type' => 'Nhân viên thu ngân'])->assertSessionHasNoErrors();
        $this->assertSame('CASHIER', $employee->detail->fresh()->employee_type);
        $this->assertSame('Loại tùy chỉnh', AdminDisplay::employeeTypeCode('Loại tùy chỉnh'));
    }
}
