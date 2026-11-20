<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\MedicalVisit;
use App\Models\Payable;
use App\Models\Patient;
use App\Models\User;
use App\Models\VisitStatus;
use App\Support\EmployeeAccess;
use Tests\Fixtures\Seeders\WorkflowFixtureSeeder;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EmployeeClassificationPatientHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(WorkflowFixtureSeeder::class);
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

    private function employeeData(): array
    {
        return ['full_name' => 'Nhân viên kiểm thử', 'phone' => '0987654321', 'password' => 'TemporaryTest123',
            'password_confirmation' => 'TemporaryTest123', 'status' => 'ACTIVE'];
    }

    private function addVisit(Patient $patient, string $code, string $date): MedicalVisit
    {
        $visit = MedicalVisit::firstOrFail()->replicate();
        $visit->fill(['appointment_id' => null, 'patient_id' => $patient->id, 'visit_code' => $code,
            'checked_in_at' => $date.' 09:00:00', 'examination_started_at' => null, 'examination_ended_at' => null]);
        $visit->save();

        return $visit;
    }

    public function test_employee_profile_no_longer_has_department_or_employee_type_fields(): void
    {
        $employee = Employee::firstOrFail();
        $this->assertFalse(Schema::hasTable('employee_types'));
        $this->assertFalse(Schema::hasColumn('employee_details', 'department'));
        $this->assertFalse(Schema::hasColumn('employee_details', 'employee_type'));

        foreach ([route('admin.employees.index'), route('admin.employees.create'), route('admin.employees.edit', $employee)] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $xpath = $this->xpath($html);
            $this->assertCount(0, $xpath->query('//*[@name="employee_type" or @name="department"]'));
            $this->assertStringNotContainsString('Loại nhân viên', strip_tags($html));
        }
    }

    public function test_removed_employee_classification_is_rejected_but_workspaces_remain_available(): void
    {
        $count = User::count();
        $this->post(route('admin.employees.store'), [...$this->employeeData(), 'employee_type' => 'RECEPTIONIST', 'department' => 'Lễ tân'])
            ->assertSessionHasErrors(['employee_type', 'department']);
        $this->assertSame($count, User::count());

        $employee = Employee::where('employee_code', 'NV000001')->firstOrFail();
        $this->put(route('admin.employees.update', $employee), [
            'full_name' => $employee->full_name,
            'status' => 'ACTIVE',
            'employee_type' => 'CASHIER',
            'department' => 'Thu ngân',
        ])->assertSessionHasErrors(['employee_type', 'department']);

        $this->assertTrue(app(EmployeeAccess::class)->allows($employee->user, 'reception.check_in', 'reception'));
        $this->assertTrue(app(EmployeeAccess::class)->allows($employee->user, 'payment.collect', 'cashier'));
    }
    public function test_patient_history_is_scoped_to_patient_for_every_filter(): void
    {
        $patient = Patient::firstOrFail();
        $other = Patient::where('id', '!=', $patient->id)->firstOrFail();
        $own = $this->addVisit($patient, 'HISTORY-OWN', '2026-10-23');
        $foreign = $this->addVisit($other, 'HISTORY-FOREIGN', '2026-10-23');
        foreach ([[], ['history_q' => $own->doctor->full_name], ['history_q' => 'HISTORY'], ['visit_status_id' => $own->visit_status_id], ['date_from' => '2026-10-23', 'date_to' => '2026-10-23']] as $filters) {
            $response = $this->get(route('admin.patients.show', [$patient, ...$filters]))->assertOk();
            $this->assertTrue($response->viewData('visits')->contains('id', $own->id));
            $this->assertFalse($response->viewData('visits')->contains('id', $foreign->id));
            $response->assertDontSee('HISTORY-FOREIGN');
        }
        $response = $this->get(route('admin.patients.show', [$patient, 'history_q' => 'HISTORY-FOREIGN']))->assertOk();
        $this->assertSame(0, $response->viewData('visits')->total());
    }

    public function test_history_keeps_only_key_columns_with_shared_table_components(): void
    {
        $visit = MedicalVisit::whereHas('conclusion')->whereHas('prescription')->firstOrFail();
        $response = $this->get(route('admin.patients.show', $visit->patient))->assertOk()
            ->assertSee('Lịch sử khám')->assertSee('Lịch sử chỉ số sức khỏe')
            ->assertDontSee($visit->conclusion->professional_conclusion)
            ->assertSee(route('admin.visits.show', $visit))
            ->assertDontSee('Xem đơn thuốc');
        $xpath = $this->xpath($response->getContent());
        $this->assertSame('STT', trim($xpath->query('//table[contains(@class,"pd-visit-history-table")]/thead/tr/th')->item(0)->textContent));
        $headers = $xpath->query('//table[contains(@class,"pd-visit-history-table")]/thead/tr/th');
        $this->assertSame(['STT', 'Ngày khám', 'Mã lượt khám', 'Bác sĩ', 'Chuyên khoa', 'Phòng khám', 'Trạng thái', 'Thao tác'],
            array_map(fn ($node) => trim($node->textContent), iterator_to_array($headers)));
        $this->assertGreaterThan(0, $xpath->query('//table[contains(@class,"pd-visit-history-table")]//span[contains(@class,"admin-record-code")]')->length);
        $this->assertGreaterThan(0, $xpath->query('//table[contains(@class,"pd-visit-history-table")]//span[contains(@class,"admin-badge")]')->length);
        $this->assertGreaterThan(0, $xpath->query('//table[contains(@class,"pd-visit-history-table")]//a[contains(@class,"admin-btn-detail")]')->length);
    }

    public function test_history_filters_search_clinical_data_and_include_end_date(): void
    {
        $visit = MedicalVisit::whereHas('diagnoses')->whereHas('conclusion')->firstOrFail();
        foreach ([$visit->visit_code, $visit->diagnoses->first()->disease->disease_code, $visit->specialty->name, $visit->conclusion->professional_conclusion] as $term) {
            $response = $this->get(route('admin.patients.show', [$visit->patient, 'history_q' => $term]))->assertOk();
            $this->assertTrue($response->viewData('visits')->contains('id', $visit->id), $term);
        }
        $this->addVisit($visit->patient, 'HISTORY-LATE', '2026-10-24')->update(['checked_in_at' => '2026-10-24 23:59:59']);
        $response = $this->get(route('admin.patients.show', [$visit->patient, 'date_to' => '2026-10-24']))->assertOk();
        $this->assertTrue($response->viewData('visits')->contains('visit_code', 'HISTORY-LATE'));
        $response = $this->get(route('admin.patients.show', [$visit->patient, 'visit_status_id' => VisitStatus::where('name', 'COMPLETED')->value('id')]))->assertOk();
        $this->assertTrue($response->viewData('visits')->every(fn ($row) => $row->status->name === 'COMPLETED'));
    }

    public function test_history_pagination_keeps_filters_anchor_order_and_continuous_numbers(): void
    {
        $patient = Patient::firstOrFail();
        for ($number = 1; $number <= 12; $number++) {
            $this->addVisit($patient, sprintf('PAGE-%02d', $number), sprintf('2026-10-%02d', $number));
        }
        $response = $this->get(route('admin.patients.show', [$patient, 'history_q' => 'PAGE-', 'visits_page' => 2]))->assertOk();
        $visits = $response->viewData('visits');
        $this->assertSame(['PAGE-02', 'PAGE-01'], $visits->pluck('visit_code')->all());
        $this->assertSame(12, $visits->total());
        $this->assertStringContainsString('history_q=PAGE-', $visits->previousPageUrl());
        $this->assertStringEndsWith('#history', $visits->previousPageUrl());
        $xpath = $this->xpath($response->getContent());
        $this->assertSame('11', trim($xpath->query('//table[contains(@class,"pd-visit-history-table")]/tbody/tr/td')->item(0)->textContent));
        $this->assertTrue($response->viewData('historyActive'));
    }

    public function test_empty_history_invalid_filters_and_escaped_content(): void
    {
        $patient = Patient::firstOrFail()->replicate();
        $patient->fill(['user_id' => null, 'patient_code' => 'BN-HISTORY-EMPTY']);
        $patient->save();
        $this->get(route('admin.patients.show', $patient))->assertOk()->assertSee('Bệnh nhân chưa có lượt khám nào.');
        foreach ([['history_q' => ['bad']], ['visits_page' => -1], ['visit_status_id' => 999999], ['date_from' => '2026-10-24', 'date_to' => '2026-10-23']] as $filters) {
            $this->getJson(route('admin.patients.show', [$patient, ...$filters]))->assertUnprocessable();
        }
        $visit = $this->addVisit($patient, '<script>history-xss</script>', '2026-10-24');
        $this->get(route('admin.patients.show', $patient))->assertOk()->assertSee($visit->visit_code)->assertDontSee($visit->visit_code, false);
    }

    public function test_patient_accounts_still_cannot_enter_admin_history_or_employee_forms(): void
    {
        $this->actingAs(User::whereHas('role', fn ($q) => $q->where('name', 'Patient'))->firstOrFail());
        $this->get(route('admin.patients.show', Patient::firstOrFail()))->assertRedirect(route('admin.login'));
        $this->get(route('admin.patient-medical-history.index'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.employees.create'))->assertRedirect(route('admin.login'));
    }

    public function test_employee_list_omits_repeated_position_without_losing_profile_data(): void
    {
        $employee = Employee::whereHas('detail')->firstOrFail();
        $employee->detail->update(['position' => 'Chức vụ chỉ xem trong hồ sơ']);
        $html = $this->get(route('admin.employees.index'))->assertOk()
            ->assertDontSee('Chức vụ chỉ xem trong hồ sơ')->getContent();
        $headers = $this->xpath($html)->query('//main//table/thead/tr/th');
        $this->assertSame(['STT', 'Mã nhân viên', 'Họ và tên', 'Số điện thoại', 'Trạng thái', 'Thao tác'],
            array_map(fn ($node) => trim($node->textContent), iterator_to_array($headers)));
        $this->get(route('admin.employees.show', $employee))->assertOk()->assertSee('Chức vụ chỉ xem trong hồ sơ');
        $this->get(route('admin.employees.edit', $employee))->assertOk()->assertSee('Chức vụ chỉ xem trong hồ sơ');
        $this->assertSame('Chức vụ chỉ xem trong hồ sơ', $employee->detail->fresh()->position);
    }

    public function test_standalone_history_lists_all_patients_and_uses_shared_controls(): void
    {
        $response = $this->get(route('admin.patient-medical-history.index'))->assertOk();
        $this->assertSame(MedicalVisit::count(), $response->viewData('visits')->total());
        $xpath = $this->xpath($response->getContent());
        $headers = $xpath->query('//main//table/thead/tr/th');
        $this->assertSame(['STT', 'Ngày khám', 'Mã lượt khám', 'Bệnh nhân', 'Bác sĩ', 'Chuyên khoa', 'Phòng khám', 'Trạng thái', 'Thao tác'],
            array_map(fn ($node) => trim($node->textContent), iterator_to_array($headers)));
        $this->assertCount(1, $xpath->query('//main//form[@data-filter-fields="4" and contains(@class,"admin-filter-form")]'));
        $this->assertCount(1, $xpath->query('//main//h2[normalize-space(.)="Danh sách lượt khám"]'));
        foreach ($response->viewData('visits') as $visit) {
            foreach (['patient', 'doctor', 'specialty', 'examinationRoom', 'status'] as $relation) {
                $this->assertTrue($visit->relationLoaded($relation));
            }
            $response->assertSee($visit->examinationRoom->name)
                ->assertSee(route('admin.visits.show', [$visit, 'from_medical_history' => '1']))
                ->assertSee(route('admin.patients.show', $visit->patient).'#history', false);
        }
        $visit = $response->viewData('visits')->first();
        $this->get(route('admin.patients.show', $visit->patient))->assertOk()->assertSee($visit->examinationRoom->name);
        $this->get(route('admin.visits.show', [$visit, 'from_medical_history' => '1']))->assertOk()
            ->assertSee('href="'.route('admin.patient-medical-history.index').'"', false);
    }

    public function test_standalone_history_sidebar_opens_and_highlights_only_its_own_child(): void
    {
        $html = $this->get(route('admin.patient-medical-history.index'))->assertOk()->getContent();
        $xpath = $this->xpath($html);
        $items = $xpath->query('//details[contains(@class,"sidebar-submenu") and @open]//a[contains(@class,"active")]');
        $this->assertCount(1, $items);
        $this->assertSame('Lịch sử khám', trim($items->item(0)->textContent));
        foreach (['admin.patients.index', 'admin.personal-health-records.index', 'admin.patient-medical-history.index'] as $name) {
            $response = $this->get(route($name))->assertOk();
            $response->assertSee('href="'.route('admin.patient-medical-history.index').'"', false);
        }
    }

    public function test_global_and_patient_histories_share_search_rules_without_leaking_other_patients(): void
    {
        $visit = MedicalVisit::whereHas('diagnoses')->whereHas('conclusion')->firstOrFail();
        foreach ([$visit->patient->patient_code, $visit->patient->full_name, $visit->visit_code, $visit->doctor->doctor_code,
            $visit->specialty->name, $visit->examinationRoom->room_code, $visit->examinationRoom->name,
            $visit->diagnoses->first()->disease->disease_code, $visit->conclusion->professional_conclusion] as $term) {
            $response = $this->get(route('admin.patient-medical-history.index', ['history_q' => $term]))->assertOk();
            $this->assertTrue($response->viewData('visits')->contains('id', $visit->id), $term);
            $response = $this->get(route('admin.patients.show', [$visit->patient, 'history_q' => $term]))->assertOk();
            $this->assertTrue($response->viewData('visits')->every(fn ($row) => $row->patient_id === $visit->patient_id), $term);
        }
        $other = Patient::where('id', '!=', $visit->patient_id)->firstOrFail();
        $this->get(route('admin.patients.show', [$other, 'history_q' => $visit->patient->patient_code]))->assertOk()
            ->assertViewHas('visits', fn ($rows) => $rows->total() === 0);
    }

    public function test_standalone_history_pagination_dates_status_and_invalid_input(): void
    {
        $patient = Patient::firstOrFail();
        for ($number = 1; $number <= 12; $number++) {
            $this->addVisit($patient, sprintf('GLOBAL-%02d', $number), sprintf('2026-10-%02d', $number));
        }
        $response = $this->get(route('admin.patient-medical-history.index', ['history_q' => 'GLOBAL-', 'page' => 2]))->assertOk();
        $visits = $response->viewData('visits');
        $this->assertSame(['GLOBAL-02', 'GLOBAL-01'], $visits->pluck('visit_code')->all());
        $this->assertStringContainsString('history_q=GLOBAL-', $visits->previousPageUrl());
        $this->assertSame('11', trim($this->xpath($response->getContent())->query('//main//table/tbody/tr/td')->item(0)->textContent));
        $visit = $this->addVisit($patient, 'GLOBAL-END-DATE', '2026-10-24');
        $visit->update(['checked_in_at' => '2026-10-24 23:59:59']);
        $this->get(route('admin.patient-medical-history.index', [
            'history_q' => 'GLOBAL-', 'date_from' => '2026-10-24', 'date_to' => '2026-10-24', 'visit_status_id' => $visit->visit_status_id,
        ]))->assertOk()->assertViewHas('visits', fn ($rows) => $rows->total() === 1 && $rows->first()->id === $visit->id);
        $this->get(route('admin.patient-medical-history.index', ['history_q' => 'does-not-exist']))->assertOk()
            ->assertSee('Không tìm thấy lượt khám phù hợp.')->assertSee('colspan="9"', false);
        foreach ([['history_q' => ['bad']], ['history_q' => str_repeat('a', 151)], ['page' => -1], ['visit_status_id' => 999999],
            ['date_from' => '2026-10-24', 'date_to' => '2026-10-23'], ['date_to' => 'not-a-date']] as $filters) {
            $this->getJson(route('admin.patient-medical-history.index', $filters))->assertUnprocessable();
        }
        $visit->patient->update(['full_name' => '<script>patient-history-xss</script>']);
        $this->get(route('admin.patient-medical-history.index', ['history_q' => $visit->visit_code]))->assertOk()
            ->assertSee('<script>patient-history-xss</script>')->assertDontSee('<script>patient-history-xss</script>', false);
    }

    public function test_visit_history_detail_links_only_its_own_results_prescription_and_payables(): void
    {
        $visit = MedicalVisit::whereHas('prescription')->whereHas('payables')->whereHas('serviceOrders.items.result')->firstOrFail();
        $response = $this->get(route('admin.visits.show', [$visit, 'from_patient_history' => '1']))->assertOk();
        $response->assertSee(route('admin.patients.show', $visit->patient).'#history', false)
            ->assertSee(route('admin.prescriptions.show', $visit->prescription))
            ->assertSee('Danh sách khoản phải thanh toán');
        foreach ($visit->payables as $payable) {
            $response->assertSee($payable->payable_code)->assertSee(route('admin.payables.show', $payable));
        }
        foreach ($visit->serviceOrders as $order) {
            foreach ($order->items as $item) {
                if ($item->result) {
                    $response->assertSee(route('admin.service-results.show', $item->result));
                }
            }
        }
        foreach (Payable::where('medical_visit_id', '!=', $visit->id)->get() as $foreignPayable) {
            $response->assertDontSee($foreignPayable->payable_code);
        }
        $this->get(route('admin.visits.show', $visit))->assertOk()->assertSee('href="'.route('admin.visits.index').'"', false);
    }
}
