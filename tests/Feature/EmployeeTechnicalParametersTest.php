<?php

namespace Tests\Feature;

use App\Models\MedicalService;
use App\Models\PaymentStatus;
use App\Models\ServiceOrder;
use App\Models\ServiceResult;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeeTechnicalParametersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(DatabaseSeeder::class)->setContainer($this->app)->__invoke(['includePracticeData' => true]);
        $this->withoutVite();
    }

    private function saveResult(string $group, $item, array $data)
    {
        $data['result_version'] ??= ServiceResult::editVersion($item->fresh()->result);
        return $this->post(route('employee.'.$group.'.save', $item->id), ['confirm_result' => '1', ...$data]);
    }

    private function pendingItem()
    {
        return ServiceOrder::where('service_order_code', 'CD000004')->firstOrFail()->items()->firstOrFail();
    }

    private function startLaboratory()
    {
        $item = $this->pendingItem();
        $item->payable->update(['payment_status_id' => PaymentStatus::where('allows_service_execution', true)->firstOrFail()->id]);
        $this->actingAs(User::where('business_code', 'NV000002')->firstOrFail())
            ->post(route('employee.laboratory.start', $item->id))->assertSessionHasNoErrors();
        return $item->fresh();
    }

    public static function supportedGroups(): array
    {
        return [['XN', 'DVXN001'], ['CDHA', 'DVCD001'], ['TDCN', 'DVTDCN001'], ['TT', 'DVTT001']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('supportedGroups')]
    public function test_print_preview_shows_saved_details_without_changing_result(string $group, string $code): void
    {
        $item = $this->pendingItem();
        $item->update(['medical_service_id' => MedicalService::where('service_code', $code)->firstOrFail()->id]);
        $item->payable->update(['payment_status_id' => PaymentStatus::where('allows_service_execution', true)->firstOrFail()->id]);
        $this->actingAs(User::where('business_code', 'NV000002')->firstOrFail());
        $this->post(route('employee.services.start', $item->id))->assertSessionHasNoErrors();
        $this->get(route('employee.services.print', $item->id))->assertNotFound();
        $this->get(route('employee.services.show', $item->id))->assertDontSee('Xem và in phiếu kết quả');
        $this->saveResult('services', $item, ['details_present' => '1', 'details' => [['name' => 'Tên <mục>', 'value' => '0']]])->assertSessionHasNoErrors();
        $before = $item->fresh()->result->getAttributes();
        $this->get(route('employee.services.show', $item->id))->assertSee('Xem và in phiếu kết quả')->assertDontSee('Trạng thái xác minh');
        $response = $this->get(route('employee.services.print', $item->id))->assertOk()
            ->assertSee('Mediway')->assertSee('Tên &lt;mục&gt;', false)->assertSee('>0<', false)
            ->assertSee('Mã chỉ định')->assertSee($item->serviceOrder->service_order_code)
            ->assertSee('Mã bệnh nhân')->assertDontSee('Mã lượt khám')->assertDontSee('Số CCCD')
            ->assertSeeInOrder(['Kỹ thuật:', 'Chưa ghi nhận kỹ thuật.', 'class="print-result-details"',
                'Tên &lt;mục&gt;', 'Kết luận:', 'Chưa ghi nhận kết luận.'], false)
            ->assertDontSee('1. Kỹ thuật')->assertDontSee('2. Kết luận')
            ->assertSee('In / Lưu PDF')->assertSee('data-print-document', false)->assertDontSee('data-print-preview', false)
            ->assertDontSee('Trạng thái xác minh')->assertDontSee('Chưa xác minh');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertSame($before, $item->fresh()->result->getAttributes());
        $this->assertSame('PROCESSING', $item->fresh()->serviceStatus->name);

        $item->update(['performed_by_employee_id' => User::where('business_code', 'NV000003')->firstOrFail()->employee->id]);
        $this->get(route('employee.services.print', $item->id))->assertNotFound();
        $this->flushSession();
        $this->actingAs($item->serviceOrder->medicalVisit->patient->user)->get(route('employee.services.print', $item->id))->assertForbidden();
    }

    public function test_print_preview_shows_parameters_legacy_and_ambiguous_group_is_blocked(): void
    {
        $item = $this->startLaboratory();
        $this->saveResult('laboratory', $item, ['result_summary' => "Dòng một\nDòng hai", 'parameters_present' => '1', 'parameters' => [['name' => 'Hồng cầu', 'value' => '0', 'unit' => 'mg/L']]])->assertSessionHasNoErrors();
        $this->get(route('employee.laboratory.print', $item->id))->assertOk()->assertSee('Dòng hai')
            ->assertSeeInOrder(['Kỹ thuật:', 'Hồng cầu', '0 mg/L', 'Kết luận:']);
        $item->fresh()->result->update([
            'result_parameters' => ['Chỉ số cũ' => 'Giá trị cũ'],
            'technical_details' => [['name' => 'Kỹ thuật', 'value' => 'Kỹ thuật đã lưu trước đây']],
        ]);
        $this->get(route('employee.laboratory.print', $item->id))->assertOk()
            ->assertSeeInOrder(['Kỹ thuật:', 'Kỹ thuật đã lưu trước đây', 'Chỉ Số Cũ', 'Giá trị cũ', 'Kết luận:']);
        $item->medicalService->serviceGroups()->attach(\App\Models\ServiceGroup::where('service_group_code', 'TT')->firstOrFail()->id);
        $this->get(route('employee.laboratory.print', $item->id))->assertNotFound();
        $this->get(route('employee.laboratory.show', $item->id))->assertDontSee('Xem và in phiếu kết quả');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('supportedGroups')]
    public function test_extra_details_can_be_saved_seen_and_completed_for_each_group(string $group, string $code): void
    {
        $item = $this->pendingItem();
        $item->update(['medical_service_id' => MedicalService::where('service_code', $code)->firstOrFail()->id]);
        $item->payable->update(['payment_status_id' => PaymentStatus::where('allows_service_execution', true)->firstOrFail()->id]);
        $this->actingAs(User::where('business_code', 'NV000002')->firstOrFail());
        $this->post(route('employee.services.start', $item->id))->assertSessionHasNoErrors();
        $page = $this->get(route('employee.services.show', $item->id))->assertOk();
        $page->assertSee('+ Thêm thông tin')->assertDontSee('name="result_pdf"', false)->assertDontSee('name="result_image"', false);
        if (in_array($group, ['XN', 'TDCN'], true)) $page->assertSee('+ Thêm chỉ số');
        else $page->assertDontSee('+ Thêm chỉ số');

        $details = [['name' => 'Thông tin thử nghiệm', 'value' => '0'], ['name' => 'Kỹ thuật', 'value' => 'Giá trị <mẫu>']];
        $this->saveResult('services', $item, ['details_present' => '1', 'details' => $details])->assertSessionHasNoErrors();
        $this->assertSame($details, $item->fresh()->result->technical_details);
        $this->assertTrue($item->fresh()->result->hasContent());
        $this->get(route('employee.services.show', $item->id))->assertOk()->assertSee('Thông tin thử nghiệm')->assertSee('Giá trị &lt;mẫu&gt;', false);

        $patient = $item->serviceOrder->medicalVisit->patient->user;
        $visit = $item->serviceOrder->medicalVisit;
        $result = $item->fresh()->result;
        $this->flushSession();
        $printUrl = route('patient.results.service-print', $result);
        $this->actingAs($patient)->get($printUrl)->assertNotFound();
        $this->get(route('patient.results.visit', ['visit' => $visit->id, 'tab' => 'dich-vu']))->assertOk()->assertDontSee('Thông tin thử nghiệm');

        $this->flushSession();
        $this->actingAs(User::where('business_code', 'NV000002')->firstOrFail());
        $this->post(route('employee.services.complete', $item->id))->assertSessionHasNoErrors();
        $this->assertSame('COMPLETED', $item->fresh()->serviceStatus->name);

        $this->flushSession();
        $this->actingAs($patient)->get(route('patient.results.visit', ['visit' => $visit->id, 'tab' => 'dich-vu']))->assertOk()->assertSee('Thông tin thử nghiệm')->assertSee('Giá trị &lt;mẫu&gt;', false);
        $this->get($printUrl)->assertOk()->assertSee('Thông tin thử nghiệm')->assertSee('Giá trị &lt;mẫu&gt;', false);
        $other = User::where('business_code', 'BN000001')->firstOrFail();
        if ($other->id === $patient->id) $other = User::where('business_code', 'BN000002')->firstOrFail();
        $this->flushSession();
        $this->actingAs($other)->get(route('patient.results.visit', ['visit' => $visit->id, 'tab' => 'dich-vu']))->assertNotFound();
    }

    public function test_extra_details_validation_and_stale_edits_are_blocked(): void
    {
        $item = $this->startLaboratory();
        $old = ServiceResult::editVersion(null);
        $this->saveResult('laboratory', $item, ['details_present' => '1', 'details' => [['name' => 'Thiếu', 'value' => '']]])->assertSessionHasErrors('details.0');
        $this->saveResult('laboratory', $item, ['details_present' => '1', 'details' => array_fill(0, 31, ['name' => 'a', 'value' => 'b'])])->assertSessionHasErrors('details');
        $this->saveResult('laboratory', $item, ['details_present' => '1', 'details' => [['name' => 'Tên', 'value' => str_repeat('a', 2001)]]])->assertSessionHasErrors('details.0.value');
        $this->saveResult('laboratory', $item, ['details_present' => '1', 'details' => [['name' => 'Tên', 'value' => '0']]])->assertSessionHasNoErrors();
        $this->saveResult('laboratory', $item, ['result_version' => $old, 'details_present' => '1', 'details' => [['name' => 'Tên', 'value' => 'Cũ']]])->assertSessionHasErrors('service');
        $this->assertSame('0', $item->fresh()->result->technical_details[0]['value']);
    }

    public function test_functional_test_accepts_parameters_and_other_groups_reject_them(): void
    {
        $item = $this->pendingItem();
        $item->update(['medical_service_id' => MedicalService::where('service_code', 'DVTDCN001')->firstOrFail()->id]);
        $item->payable->update(['payment_status_id' => PaymentStatus::where('allows_service_execution', true)->firstOrFail()->id]);
        $this->actingAs(User::where('business_code', 'NV000002')->firstOrFail());
        $this->post(route('employee.services.start', $item->id))->assertSessionHasNoErrors();
        $this->saveResult('services', $item, ['parameters_present' => '1', 'parameters' => [['name' => 'Nhịp', 'value' => '0', 'unit' => '']]])->assertSessionHasNoErrors();
        $this->assertTrue($item->fresh()->result->hasContent());
    }

    public function test_unknown_details_are_preserved_and_ambiguous_group_is_not_guessed(): void
    {
        $item = $this->startLaboratory();
        $result = ServiceResult::create(['service_order_item_id' => $item->id, 'technical_details' => ['legacy' => 'Dữ liệu cũ']]);
        $this->assertTrue($result->hasContent());
        $this->saveResult('laboratory', $item, ['result_summary' => 'Nội dung mới'])->assertSessionHasErrors('service');
        $this->saveResult('laboratory', $item, ['details_present' => '1', 'details' => [['name' => 'A', 'value' => 'B']]])->assertSessionHasErrors('service');
        $this->assertSame(['legacy' => 'Dữ liệu cũ'], $result->fresh()->technical_details);

        $extraGroup = \App\Models\ServiceGroup::where('service_group_code', 'TT')->firstOrFail();
        $item->medicalService->serviceGroups()->attach($extraGroup->id);
        $this->get(route('employee.services.show', $item->id))->assertOk()->assertSee('Dữ liệu cũ')->assertDontSee('+ Thêm thông tin');
        $this->saveResult('services', $item, ['details_present' => '1', 'details' => [['name' => 'A', 'value' => 'B']]])->assertSessionHasErrors('details');
    }

    public function test_parameters_only_can_be_saved_seen_and_completed(): void
    {
        $item = $this->startLaboratory();
        $this->get(route('employee.laboratory.show', $item->id))->assertOk()->assertSee('Nhân viên thực hiện')->assertDontSee('Bác sĩ chỉ định');
        $payload = ['parameters_present' => '1', 'parameters' => [['name' => 'Đường huyết', 'value' => '5,4', 'unit' => 'mmol/L']]];
        $this->saveResult('laboratory', $item, $payload)->assertSessionHasNoErrors();
        $result = $item->fresh()->result;
        $this->assertSame($payload['parameters'], $result->result_parameters);
        $this->assertTrue($result->hasContent());
        $this->get(route('employee.laboratory.show', $item->id))->assertOk()->assertSee('<td>Đường huyết</td><td>5,4</td><td>mmol/L</td>', false);
        $patient = $item->serviceOrder->medicalVisit->patient->user;
        $this->flushSession();
        $resultUrl = route('patient.results.visit', ['visit' => $item->serviceOrder->medical_visit_id, 'tab' => 'dich-vu', 'dich-vu' => $item->id]);
        $printUrl = route('patient.results.service-print', $result);
        $this->assertSame('PRIVATE', $result->patient_publication_status);
        $this->actingAs($patient)->get($resultUrl)->assertOk()->assertDontSee('5,4 mmol/L');
        $this->get($printUrl)->assertNotFound();
        $this->flushSession();
        $this->actingAs(User::where('business_code', 'NV000002')->firstOrFail());
        $this->post(route('employee.laboratory.complete', $item->id))->assertSessionHasNoErrors();
        $this->assertSame('COMPLETED', $item->fresh()->serviceStatus->name);
        $this->assertSame('PUBLISHED', $result->fresh()->patient_publication_status);
        $this->post(route('employee.laboratory.complete', $item->id))->assertSessionHasErrors('service');
        $this->saveResult('laboratory', $item, $payload)->assertSessionHasErrors('service');

        $this->flushSession();
        $this->actingAs($patient)->get($resultUrl)->assertOk()->assertSee('Đường huyết')->assertSee('5,4 mmol/L');
        $this->get($printUrl)->assertOk()->assertSee('Đường huyết')->assertSee('5,4');
        $this->get(route('patient.notifications', ['danh-muc' => 'ket-qua']))->assertOk()->assertSee('Có kết quả dịch vụ');
        $other = User::whereKeyNot($patient->id)->whereHas('patient')->firstOrFail();
        $this->flushSession();
        $this->actingAs($other)->get($resultUrl)->assertNotFound();
        $this->get($printUrl)->assertNotFound();
    }

    public function test_validation_rejects_incomplete_rows_and_empty_result(): void
    {
        $item = $this->startLaboratory();
        $this->saveResult('laboratory', $item, ['parameters_present' => '1', 'parameters' => [['name' => 'CRP', 'value' => '', 'unit' => 'mg/L']]])->assertSessionHasErrors('parameters.0');
        $this->saveResult('laboratory', $item, ['parameters_present' => '1'])->assertSessionHasErrors('result_summary');
        $this->assertNull($item->fresh()->result);
    }

    public function test_legacy_parameters_can_be_read_and_are_locked(): void
    {
        $item = $this->startLaboratory();
        $legacy = ['Chỉ số cũ' => 'giá trị cũ'];
        ServiceResult::create(['service_order_item_id' => $item->id, 'result_summary' => 'Nội dung cũ', 'result_parameters' => $legacy]);
        $this->get(route('employee.laboratory.show', $item->id))->assertOk()->assertSee('giá trị cũ')->assertDontSee('Nhập kết quả kỹ thuật');
        $this->saveResult('laboratory', $item, ['result_summary' => 'Nội dung mới'])->assertSessionHasErrors('service');
        $this->saveResult('laboratory', $item, ['result_summary' => 'Thay đổi', 'parameters_present' => '1'])->assertSessionHasErrors('service');
        $this->assertSame($legacy, $item->fresh()->result->result_parameters);
    }

    public function test_verified_and_published_results_are_not_editable(): void
    {
        $item = $this->startLaboratory();
        $result = ServiceResult::create(['service_order_item_id' => $item->id, 'verified_by_user_id' => User::where('business_code', 'BS000001')->value('id')]);
        $this->saveResult('laboratory', $item, ['result_summary' => 'Thay đổi'])->assertSessionHasErrors('service');
        $result->update(['verified_by_user_id' => null]);
        $result->setAttribute('patient_publication_status', 'PUBLISHED'); $result->save();
        $this->saveResult('laboratory', $item, ['result_summary' => 'Thay đổi'])->assertSessionHasErrors('service');
        $this->assertNull($result->fresh()->result_summary);
    }

    public function test_imaging_service_supports_text_and_existing_files_without_lab_parameters(): void
    {
        Storage::fake('local');
        $item = $this->pendingItem();
        $service = MedicalService::whereHas('serviceGroups', fn ($q) => $q->where('service_group_code', 'CDHA'))->firstOrFail();
        $item->update(['medical_service_id' => $service->id]);
        $item->payable->update(['payment_status_id' => PaymentStatus::where('allows_service_execution', true)->firstOrFail()->id]);
        $this->actingAs(User::where('business_code', 'NV000003')->firstOrFail());
        $this->post(route('employee.imaging.start', $item->id))->assertSessionHasNoErrors();
        $this->saveResult('imaging', $item, ['parameters_present' => '1'])->assertSessionHasErrors('parameters');
        $this->saveResult('imaging', $item, ['result_summary' => 'Mô tả hình ảnh'])->assertSessionHasNoErrors();
        $path = UploadedFile::fake()->image('anh.png')->store('service-results/'.$item->id, 'local');
        $item->fresh()->result->update(['result_image_path' => $path]);
        $this->get(route('employee.imaging.document', [$item->id, 'image']))->assertOk();
        $this->get(route('employee.laboratory.index'))->assertOk();
    }

    public function test_ultrasound_technique_and_conclusion_appear_on_the_patient_printout_after_completion(): void
    {
        $item = $this->pendingItem();
        $item->update(['medical_service_id' => MedicalService::where('service_code', 'DVCD003')->firstOrFail()->id]);
        $item->payable->update(['payment_status_id' => PaymentStatus::where('allows_service_execution', true)->firstOrFail()->id]);
        $technician = User::where('business_code', 'NV000003')->firstOrFail();
        $patient = $item->serviceOrder->medicalVisit->patient->user;

        $this->actingAs($technician)->post(route('employee.imaging.start', $item->id))->assertSessionHasNoErrors();
        $this->get(route('employee.imaging.show', $item->id))->assertOk()
            ->assertSeeInOrder(['1. Kỹ thuật', 'Cách thức và vị trí thực hiện', '2. Kết luận', 'Kết luận dịch vụ'])
            ->assertSee('name="technique"', false)->assertSee('name="result_conclusion"', false);
        $this->saveResult('imaging', $item, ['technique' => ['invalid'], 'result_conclusion' => str_repeat('a', 10001)])
            ->assertSessionHasErrors(['technique', 'result_conclusion']);
        $this->assertNull($item->fresh()->result);

        $technique = 'Khảo sát ổ bụng bằng đầu dò cong';
        $conclusion = 'Kết luận siêu âm đã ghi nhận';
        $this->saveResult('imaging', $item, ['technique' => $technique, 'result_conclusion' => $conclusion])->assertSessionHasNoErrors();
        $result = $item->fresh()->result;
        $this->assertSame($conclusion, $result->result_conclusion);
        $this->assertSame([['name' => ServiceResult::TECHNIQUE_DETAIL_NAME, 'value' => $technique]], $result->technical_details);
        $this->assertNull($result->verified_by_user_id);
        $this->get(route('employee.imaging.show', $item->id))->assertOk()->assertSee('Xem và in phiếu kết quả');
        $this->get(route('employee.imaging.print', $item->id))->assertOk()->assertSee('PHIẾU KẾT QUẢ SIÊU ÂM')
            ->assertSeeInOrder(['Kết quả Siêu âm ổ bụng', 'Kỹ thuật:', $technique, 'Kết luận:', $conclusion])
            ->assertDontSee('Kỹ thuật thực hiện:');

        $printUrl = route('patient.results.service-print', $result);
        $this->flushSession();
        $this->actingAs($patient)->get($printUrl)->assertNotFound();
        $this->flushSession();
        $this->actingAs($technician)->post(route('employee.imaging.complete', $item->id))->assertSessionHasNoErrors();
        $this->assertSame('PUBLISHED', $result->fresh()->patient_publication_status);

        $this->flushSession();
        $this->actingAs($patient)->get($printUrl)->assertOk()
            ->assertSeeInOrder(['Kết quả Siêu âm ổ bụng', 'Kỹ thuật:', $technique, 'Kết luận:', $conclusion]);
    }

    public function test_saved_technique_and_result_fields_cannot_be_overwritten(): void
    {
        $item = $this->startLaboratory();
        $detail = ['name' => 'Thông tin mẫu', 'value' => 'Dữ liệu bổ sung'];
        $this->saveResult('laboratory', $item, [
            'technique' => 'Kỹ thuật ban đầu',
            'result_conclusion' => 'Kết luận đã lưu',
            'result_summary' => 'Mô tả đã lưu',
            'details_present' => '1',
            'details' => [$detail],
        ])->assertSessionHasNoErrors();

        $this->saveResult('laboratory', $item, ['technique' => 'Kỹ thuật cập nhật'])->assertSessionHasErrors('service');
        $result = $item->fresh()->result;
        $this->assertSame([
            ['name' => ServiceResult::TECHNIQUE_DETAIL_NAME, 'value' => 'Kỹ thuật ban đầu'],
            $detail,
        ], $result->technical_details);
        $this->assertSame('Kết luận đã lưu', $result->result_conclusion);
        $this->assertSame('Mô tả đã lưu', $result->result_summary);
    }

    public function test_wrong_group_unassigned_other_technician_and_unpaid_are_blocked(): void
    {
        $item = $this->pendingItem();
        $lab = User::where('business_code', 'NV000002')->firstOrFail();
        $imaging = User::where('business_code', 'NV000003')->firstOrFail();
        $this->actingAs($lab)->post(route('employee.laboratory.start', $item->id))->assertSessionHasErrors('service');
        $this->assertSame('PENDING', $item->fresh()->serviceStatus->name);
        $item->update(['performed_by_employee_id' => $imaging->employee->id]);
        $this->get(route('employee.laboratory.show', $item->id))->assertNotFound();
        $this->saveResult('laboratory', $item, ['result_summary' => 'Sai quyền'])->assertNotFound();
        $this->flushSession();
        $this->actingAs($imaging)->get(route('employee.laboratory.index'))->assertOk();
        $this->get(route('employee.imaging.show', $item->id))->assertNotFound();
        $this->get(route('employee.services.show', $item->id))->assertOk();
    }

    public function test_legacy_technician_can_find_and_complete_endoscopy_in_the_shared_workspace(): void
    {
        // Kiểm tra code mới trên database còn danh mục cũ trong lúc nâng cấp.
        $migration = require database_path('migrations/2026_11_07_200000_merge_legacy_technician_types.php');
        $migration->down();
        $item = $this->pendingItem();
        $service = MedicalService::where('service_code', 'DVTDCN002')->firstOrFail();
        $item->update(['medical_service_id' => $service->id]);
        $url = route('employee.services.show', $item->id);
        foreach (['NV000001', 'NV000004'] as $code) {
            $this->flushSession();
            $this->actingAs(User::where('business_code', $code)->firstOrFail())
                ->get(route('employee.services.index'))->assertForbidden();
        }

        $this->flushSession();
        $technician = User::where('business_code', 'NV000002')->firstOrFail();
        $technician->employee->detail()->update(['employee_type' => 'LAB_TECHNICIAN']);
        $orders = route('employee.services.orders', ['group' => 'NS', 'service' => $service->service_code]);
        $this->actingAs($technician)
            ->get($orders)->assertOk()->assertSee($url);
        $this->get(route('employee.services.orders', ['group' => 'XN', 'service' => $service->service_code]))->assertNotFound();
        $this->get($url)->assertOk()->assertSee($service->name);
        $this->post(route('employee.services.start', $item->id))->assertSessionHasErrors('service');

        $item->payable->update(['payment_status_id' => PaymentStatus::where('allows_service_execution', true)->firstOrFail()->id]);
        $this->post(route('employee.services.start', $item->id))->assertSessionHasNoErrors();
        $this->assertEquals($technician->employee->id, $item->fresh()->performed_by_employee_id);
        $this->get($url)->assertOk()->assertSeeInOrder(['1. Kỹ thuật', '2. Kết luận', 'Mô tả kết quả'])
            ->assertDontSee('name="parameters_present"', false);
        $this->saveResult('services', $item, ['result_summary' => 'Kết quả nội soi kiểm thử'])->assertSessionHasNoErrors();
        $this->get(route('employee.services.print', $item->id))->assertOk()
            ->assertSee('PHIẾU KẾT QUẢ DỊCH VỤ')->assertSee('Kết quả nội soi kiểm thử');
        $this->post(route('employee.services.complete', $item->id))->assertSessionHasNoErrors();
        $this->assertSame('COMPLETED', $item->fresh()->serviceStatus->name);

        $this->flushSession();
        $other = User::where('business_code', 'NV000003')->firstOrFail();
        $other->employee->detail()->update(['employee_type' => 'IMAGING_TECHNICIAN']);
        $this->actingAs($other)->get($orders)->assertOk()->assertDontSee($url);
        $this->get($url)->assertNotFound();
    }

    public function test_demo_orders_cover_search_assignment_payment_and_completion(): void
    {
        $order = ServiceOrder::where('service_order_code', 'CD-MAUDV-01')->firstOrFail();
        $endoscopy = $order->items()->whereHas('medicalService', fn ($query) => $query->where('service_code', 'DVNS001'))->sole();
        $technician = User::where('business_code', 'NV000002')->firstOrFail();
        $catalogue = $this->actingAs($technician)->get(route('employee.services.index'))->assertOk()
            ->assertSee('Nhóm dịch vụ')->assertSee('Nội soi')->assertSee('Siêu âm')->assertDontSee('CD-MAUDV-01')
            ->assertDontSee('Hoàng Bảo Ngân')->assertDontSee('Danh sách phiếu chỉ định');
        $services = $catalogue->viewData('groups')->firstWhere('code', 'NS')['services']->keyBy('service_code');
        $this->assertSame(1, $services['DVNS001']->current_order_count);
        $this->assertSame(1, $services['DVTDCN002']->current_order_count);
        $this->assertSame(0, $services['DVNS002']->current_order_count);

        $index = $this->post(route('employee.services.orders.filter', ['group' => 'NS', 'service' => 'DVNS001']),
            ['q' => 'BN000006', 'status' => 'READY'])->assertRedirect()->headers->get('Location');
        parse_str(parse_url($index, PHP_URL_QUERY), $filters);
        $this->assertArrayNotHasKey('q', $filters);
        $detail = ['item' => $endoscopy->id, 'group' => 'NS', ...$filters];
        $this->get($index)->assertOk()->assertSee(route('employee.services.show', $detail))->assertSee('Nội soi dạ dày')
            ->assertDontSee('name="assignee"', false)
            ->assertDontSee('Siêu âm ổ bụng')->assertDontSee('Tổng phân tích nước tiểu');
        $this->get(route('employee.services.orders', ['group' => 'NS', 'service' => 'DVNS001', ...$filters, 'assignee' => 'mine']))
            ->assertOk()->assertSee($order->service_order_code)->assertSee(route('employee.services.show', $detail))
            ->assertDontSee('assignee=');
        $this->followingRedirects()->post(route('employee.services.orders.filter', ['group' => 'NS', 'service' => 'DVTDCN002']), ['q' => 'CD-MAUDV-02', 'status' => 'PROCESSING'])
            ->assertOk()->assertSee('CD-MAUDV-02')->assertSee('Nội soi Tai Mũi Họng')->assertDontSee('X-quang cột sống thắt lưng');
        $this->followingRedirects()->post(route('employee.services.orders.filter', ['group' => 'XN', 'service' => 'DVXN004']), ['q' => 'CD-MAUDV-01', 'status' => 'WAITING_PAYMENT'])
            ->assertOk()->assertSee('CD-MAUDV-01')->assertSee('Tổng phân tích nước tiểu')->assertDontSee('Nội soi dạ dày');
        $this->get(route('employee.services.orders', ['group' => 'NS', 'service' => 'DVNS002']))
            ->assertOk()->assertSee('Không tìm thấy phiếu chỉ định');

        $this->get(route('employee.services.show', $detail))->assertOk()->assertSee($index)->assertSee('Bắt đầu thực hiện');
        $this->post(route('employee.services.start', $detail))->assertRedirect(route('employee.services.show', $detail));
        $this->get(route('employee.services.show', $detail))->assertOk()->assertSee('Nhập kết quả kỹ thuật')->assertDontSee('>Xác nhận hoàn tất</button>', false);
        $this->saveResult('services', $endoscopy, ['result_summary' => 'Kết quả nội soi của dữ liệu mẫu.'])->assertSessionHasNoErrors();
        $this->get(route('employee.services.show', $detail))->assertOk()->assertSee('Xác nhận hoàn tất');
        $this->post(route('employee.services.complete', $detail))->assertRedirect(route('employee.services.show', $detail));
        $this->assertSame('COMPLETED', $endoscopy->fresh()->serviceStatus->name);
        $after = $this->get(route('employee.services.index', ['group' => 'NS']))->assertOk();
        $this->assertSame('NS', $after->viewData('openGroup'));
        $this->assertSame(0, $after->viewData('groups')->firstWhere('code', 'NS')['services']->firstWhere('service_code', 'DVNS001')->current_order_count);
    }

    public function test_existing_pdf_can_be_opened_and_new_upload_is_rejected(): void
    {
        Storage::fake('local');
        $item = $this->startLaboratory();
        $this->saveResult('laboratory', $item, ['result_summary' => 'Nội dung xét nghiệm'])->assertSessionHasNoErrors();
        $this->assertTrue($item->fresh()->result->hasContent());
        $pdf = UploadedFile::fake()->createWithContent('phieu.pdf', '%PDF-1.4'."\n".'1 0 obj << >> endobj'."\n".'%%EOF');
        $this->saveResult('laboratory', $item, ['result_summary' => '', 'result_pdf' => $pdf])->assertSessionHasErrors('service');
        $path = $pdf->store('service-results/'.$item->id, 'local');
        $item->fresh()->result->update(['result_summary' => null, 'result_pdf_path' => $path]);
        $this->assertTrue($item->fresh()->result->hasContent());
        $this->get(route('employee.laboratory.document', [$item->id, 'pdf']))->assertOk();
        $this->get(route('employee.laboratory.print', $item->id))->assertOk()->assertSee('Nội dung kết quả nằm trong tệp đính kèm đã lưu');
    }

    public function test_admin_can_read_structured_parameters(): void
    {
        $item = $this->startLaboratory();
        $this->saveResult('laboratory', $item, ['parameters_present' => '1', 'parameters' => [['name' => 'CRP', 'value' => '12', 'unit' => 'mg/L']]])->assertSessionHasNoErrors();
        $result = $item->fresh()->result;
        $this->flushSession();
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail())
            ->get(route('admin.service-results.show', $result->id))->assertOk()->assertSee('CRP')->assertSee('mg/L');
    }

    public function test_stale_form_cannot_overwrite_new_result(): void
    {
        $item = $this->startLaboratory();
        $oldVersion = ServiceResult::editVersion(null);
        $this->saveResult('laboratory', $item, ['result_summary' => 'Kết quả lần đầu', 'result_version' => $oldVersion])->assertSessionHasNoErrors();
        $this->saveResult('laboratory', $item, ['result_summary' => 'Dữ liệu biểu mẫu cũ', 'result_version' => $oldVersion])->assertSessionHasErrors('service');
        $this->assertSame('Kết quả lần đầu', $item->fresh()->result->result_summary);
    }

    public function test_zero_numeric_text_and_zero_parameter_are_preserved(): void
    {
        $item = $this->startLaboratory();
        $this->saveResult('laboratory', $item, ['result_summary' => '0', 'parameters_present' => '1', 'parameters' => [['name' => 'Chỉ số A', 'value' => 0, 'unit' => '']]])->assertSessionHasNoErrors();
        $result = $item->fresh()->result;
        $this->assertSame('0', $result->result_summary);
        $this->assertSame('0', $result->result_parameters[0]['value']);
        $this->get(route('employee.laboratory.show', $item->id))->assertOk()->assertSee('Chỉ số A')->assertSee('>0</p>', false);
    }

    public function test_confirmation_is_required_and_only_saved_result_is_shown_after_success(): void
    {
        $item = $this->startLaboratory();
        $showUrl = route('employee.laboratory.show', $item->id);
        $payload = ['result_summary' => 'Kết quả đã kiểm tra', 'result_version' => ServiceResult::editVersion(null)];
        $this->get($showUrl)->assertOk()->assertSee('Nhập kết quả kỹ thuật')
            ->assertSee('name="confirm_result" value="1" required', false)->assertDontSee('<h2>Kết quả đã lưu</h2>', false);
        $this->from($showUrl)->post(route('employee.laboratory.save', $item->id), $payload)
            ->assertRedirect($showUrl)->assertSessionHasErrors('confirm_result');
        $this->assertNull($item->fresh()->result);
        $this->get($showUrl)->assertOk()->assertSee('Kết quả đã kiểm tra')->assertSee('name="confirm_result"', false);

        $this->saveResult('laboratory', $item, $payload)->assertRedirect($showUrl)->assertSessionHasNoErrors();
        $this->get($showUrl)->assertOk()->assertSee('<h2>Kết quả đã lưu</h2>', false)
            ->assertSee('Kết quả đã kiểm tra')->assertSee('Xác nhận hoàn tất')
            ->assertDontSee('Nhập kết quả kỹ thuật')->assertDontSee('name="confirm_result"', false);
    }

}
