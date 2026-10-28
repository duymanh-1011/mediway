<?php

namespace Tests\Feature;

use App\Models\ExaminationRoomQueue;
use App\Models\ServiceStatus;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminTableNumberingTest extends TestCase
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

    /** @return list<int> */
    private function rowNumbers(string $html): array
    {
        $numbers = [];
        foreach ($this->xpath($html)->query('//main//table/tbody/tr/td[contains(@class,"admin-row-number")]') as $cell) {
            $numbers[] = (int) trim($cell->textContent);
        }

        return $numbers;
    }

    public function test_all_admin_list_and_related_tables_have_a_consistent_sequential_first_column(): void
    {
        $detailTables = [
            'visits' => 'medical_visits', 'doctor-schedules' => 'doctor_schedules',
            'specialties' => 'specialties', 'disease-groups' => 'disease_groups', 'service-groups' => 'service_groups',
            'roles' => 'roles', 'patients' => 'patients', 'medicines' => 'medicines', 'payables' => 'payables',
            'medical-services' => 'medical_services', 'examination-rooms' => 'examination_rooms', 'prescriptions' => 'prescriptions',
        ];
        $checked = 0;
        foreach (glob(resource_path('views/admin/*/*.blade.php')) as $file) {
            $tableCount = substr_count(file_get_contents($file), '<table') + substr_count(file_get_contents($file), "partials.status-list'");
            if (! $tableCount) {
                continue;
            }
            $module = basename(dirname($file));
            $action = basename($file, '.blade.php');
            $name = $module === 'dashboard' ? 'admin.dashboard' : 'admin.'.$module.'.'.$action;
            if (! Route::has($name)) {
                continue;
            }
            if ($name === 'admin.patients.show') {
                $tableCount += substr_count(file_get_contents(resource_path('views/admin/patients/_medical-history.blade.php')), '<table');
            } elseif ($name === 'admin.visits.show') {
                $tableCount += substr_count(file_get_contents(resource_path('views/admin/visits/_related-records.blade.php')), '<table');
            }
            $parameters = $action === 'show' ? [DB::table($detailTables[$module])->value('id')] : [];
            $html = $this->get(route($name, $parameters))->assertOk()->getContent();
            $xpath = $this->xpath($html);
            $this->assertCount($tableCount, $xpath->query('//main//table'), $name.': cần fixture hiển thị đủ các bảng');
            foreach ($xpath->query('//main//table') as $table) {
                $headers = $xpath->query('./thead/tr/th', $table);
                $this->assertGreaterThan(0, $headers->length, $name);
                $this->assertSame('STT', trim($headers->item(0)->textContent), $name);
                $this->assertSame('col', $headers->item(0)->getAttribute('scope'), $name);
                $this->assertCount(1, $xpath->query('./thead/tr/th[normalize-space(.)="STT"]', $table), $name);
                $expectedNumber = 1;
                foreach ($xpath->query('./tbody/tr', $table) as $row) {
                    $cells = $xpath->query('./td', $row);
                    if ($cells->length === 1 && $cells->item(0)->hasAttribute('colspan')) {
                        $this->assertSame($headers->length, (int) $cells->item(0)->getAttribute('colspan'), $name);

                        continue;
                    }
                    $this->assertSame($headers->length, $cells->length, $name);
                    $this->assertStringContainsString('admin-row-number', $cells->item(0)->getAttribute('class'), $name);
                    $this->assertSame((string) $expectedNumber++, trim($cells->item(0)->textContent), $name);
                }
                $checked++;
            }
        }
        $this->assertSame(54, $checked);
    }

    public function test_row_numbers_continue_on_the_second_page_and_restart_for_filtered_results(): void
    {
        $this->assertGreaterThan(10, User::count());
        $this->assertSame(range(1, 10), $this->rowNumbers($this->get(route('admin.users.index'))->assertOk()->getContent()));
        $page = $this->get(route('admin.users.index', ['page' => 2]))->assertOk();
        $users = $page->viewData('users');
        $this->assertSame(range(11, 10 + $users->count()), $this->rowNumbers($page->getContent()));
        $this->assertSame([1], $this->rowNumbers($this->get(route('admin.users.index', ['search' => 'NV000004']))->assertOk()->getContent()));
    }

    public function test_status_sequence_is_not_the_database_id_or_business_process_order(): void
    {
        $status = ServiceStatus::where('name', 'CANCELLED')->firstOrFail();
        $status->update(['process_order' => 80]);
        $response = $this->get(route('admin.service-statuses.index', ['q' => 'CANCELLED']))->assertOk();
        $this->assertSame([1], $this->rowNumbers($response->getContent()));
        $xpath = $this->xpath($response->getContent());
        $this->assertCount(1, $xpath->query('//table/thead/tr/th[normalize-space(.)="Thứ tự xử lý"]'));
        $this->assertSame('80', trim($xpath->query('//table/tbody/tr/td[4]')->item(0)->textContent));
        $this->assertSame(80, $status->fresh()->process_order);
    }

    public function test_queue_number_remains_separate_from_display_sequence(): void
    {
        $queue = ExaminationRoomQueue::firstOrFail();
        $queue->update(['queue_number' => 512]);
        $response = $this->get(route('admin.queues.index', ['search' => '512']))->assertOk();
        $this->assertSame([1], $this->rowNumbers($response->getContent()));
        $xpath = $this->xpath($response->getContent());
        $this->assertCount(1, $xpath->query('//table/thead/tr/th[normalize-space(.)="Số hàng đợi"]'));
        $this->assertSame('512', trim($xpath->query('//table/tbody/tr/td[2]')->item(0)->textContent));
        $this->assertSame(512, $queue->fresh()->queue_number);
    }

    public function test_shared_numbering_supports_collections_paginated_lists_and_empty_pages(): void
    {
        $this->view('admin.layouts.partials.row-number', ['rows' => collect(['row']), 'index' => 2])->assertSee('>3</td>', false);
        $rows = new LengthAwarePaginator(['row'], 31, 10, 3);
        $this->view('admin.layouts.partials.row-number', ['rows' => $rows, 'index' => 0])->assertSee('>21</td>', false);
        $empty = $this->get(route('admin.users.index', ['search' => 'NO_SUCH_RECORD']))->assertOk();
        $xpath = $this->xpath($empty->getContent());
        $this->assertSame($xpath->query('//table/thead/tr/th')->length, (int) $xpath->query('//table/tbody/tr/td')->item(0)->getAttribute('colspan'));
        $this->assertSame([], $this->rowNumbers($this->get(route('admin.users.index', ['page' => 999]))->assertOk()->getContent()));
    }
}
