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
        return $this->post(route('employee.'.$group.'.save', $item->id), $data);
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

    public function test_parameters_only_can_be_saved_updated_seen_and_completed(): void
    {
        $item = $this->startLaboratory();
        $this->get(route('employee.laboratory.show', $item->id))->assertOk()->assertSee('Nhân viên thực hiện')->assertDontSee('Bác sĩ chỉ định');
        $payload = ['parameters_present' => '1', 'parameters' => [['name' => 'Đường huyết', 'value' => '5,4', 'unit' => 'mmol/L']]];
        $this->saveResult('laboratory', $item, $payload)->assertSessionHasNoErrors();
        $result = $item->fresh()->result;
        $this->assertSame($payload['parameters'], $result->result_parameters);
        $this->assertTrue($result->hasContent());
        $this->get(route('employee.laboratory.show', $item->id))->assertOk()->assertSee('Đường huyết')->assertSee('5,4 mmol/L');
        $patient = $item->serviceOrder->medicalVisit->patient->user;
        $this->flushSession();
        $resultUrl = route('patient.results.visit', ['visit' => $item->serviceOrder->medical_visit_id, 'tab' => 'dich-vu', 'dich-vu' => $item->id]);
        $this->actingAs($patient)->get($resultUrl)->assertOk()->assertSee('Đường huyết')->assertSee('5,4 mmol/L');
        $this->flushSession();
        $other = User::where('business_code', 'BN000001')->firstOrFail();
        if ($other->id === $patient->id) $other = User::where('business_code', 'BN000002')->firstOrFail();
        $this->actingAs($other)->get($resultUrl)->assertNotFound();
        $this->flushSession();
        $this->actingAs(User::where('business_code', 'NV000002')->firstOrFail());
        $payload['parameters'][0]['value'] = '6,1';
        $this->saveResult('laboratory', $item, $payload)->assertSessionHasNoErrors();
        $this->assertSame('6,1', $item->fresh()->result->result_parameters[0]['value']);
        $this->post(route('employee.laboratory.complete', $item->id))->assertSessionHasNoErrors();
        $this->assertSame('COMPLETED', $item->fresh()->serviceStatus->name);
        $this->post(route('employee.laboratory.complete', $item->id))->assertSessionHasErrors('service');
        $this->saveResult('laboratory', $item, $payload)->assertSessionHasErrors('service');
    }

    public function test_validation_rejects_incomplete_rows_and_empty_result(): void
    {
        $item = $this->startLaboratory();
        $this->saveResult('laboratory', $item, ['parameters_present' => '1', 'parameters' => [['name' => 'CRP', 'value' => '', 'unit' => 'mg/L']]])->assertSessionHasErrors('parameters.0');
        $this->saveResult('laboratory', $item, ['parameters_present' => '1'])->assertSessionHasErrors('result_summary');
        $this->assertNull($item->fresh()->result);
    }

    public function test_legacy_parameters_are_preserved_when_saving_text(): void
    {
        $item = $this->startLaboratory();
        $legacy = ['Chỉ số cũ' => 'giá trị cũ'];
        ServiceResult::create(['service_order_item_id' => $item->id, 'result_summary' => 'Nội dung cũ', 'result_parameters' => $legacy]);
        $this->get(route('employee.laboratory.show', $item->id))->assertOk()->assertSee('giá trị cũ')->assertSee('được giữ nguyên');
        $this->saveResult('laboratory', $item, ['result_summary' => 'Nội dung mới'])->assertSessionHasNoErrors();
        $this->assertSame($legacy, $item->fresh()->result->result_parameters);
        $this->saveResult('laboratory', $item, ['result_summary' => 'Thay đổi', 'parameters_present' => '1'])->assertSessionHasErrors('parameters');
        $this->assertSame($legacy, $item->fresh()->result->result_parameters);
    }

    public function test_verified_and_published_results_are_not_editable(): void
    {
        $item = $this->startLaboratory();
        $result = ServiceResult::create(['service_order_item_id' => $item->id, 'result_summary' => 'Ban đầu', 'verified_by_user_id' => User::where('business_code', 'BS000001')->value('id')]);
        $this->saveResult('laboratory', $item, ['result_summary' => 'Thay đổi'])->assertSessionHasErrors('service');
        $result->update(['verified_by_user_id' => null]);
        $result->setAttribute('patient_publication_status', 'PUBLISHED'); $result->save();
        $this->saveResult('laboratory', $item, ['result_summary' => 'Thay đổi'])->assertSessionHasErrors('service');
        $this->assertSame('Ban đầu', $result->fresh()->result_summary);
    }

    public function test_imaging_service_supports_text_and_files_without_lab_parameters(): void
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
        $this->saveResult('imaging', $item, ['result_summary' => 'Mô tả hình ảnh', 'result_image' => UploadedFile::fake()->image('anh.png')])->assertSessionHasNoErrors();
        $this->get(route('employee.imaging.document', [$item->id, 'image']))->assertOk();
        $this->get(route('employee.laboratory.index'))->assertOk();
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
        $this->get($url)->assertOk()->assertSee('Nội dung kết quả')->assertDontSee('name="parameters_present"', false);
        $this->saveResult('services', $item, ['result_summary' => 'Kết quả nội soi kiểm thử'])->assertSessionHasNoErrors();
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
        $filters = ['q' => 'BN000006', 'status' => 'READY', 'assignee' => 'unassigned'];
        $index = route('employee.services.orders', ['group' => 'NS', 'service' => 'DVNS001', ...$filters]);
        $detail = ['item' => $endoscopy->id, 'group' => 'NS', ...$filters];
        $technician = User::where('business_code', 'NV000002')->firstOrFail();
        $catalogue = $this->actingAs($technician)->get(route('employee.services.index'))->assertOk()
            ->assertSee('Nhóm dịch vụ')->assertSee('Nội soi')->assertSee('Siêu âm')->assertDontSee('CD-MAUDV-01')
            ->assertDontSee('Hoàng Bảo Ngân')->assertDontSee('Danh sách phiếu chỉ định');
        $services = $catalogue->viewData('groups')->firstWhere('code', 'NS')['services']->keyBy('service_code');
        $this->assertSame(1, $services['DVNS001']->current_order_count);
        $this->assertSame(1, $services['DVTDCN002']->current_order_count);
        $this->assertSame(0, $services['DVNS002']->current_order_count);

        $this->get($index)->assertOk()->assertSee(route('employee.services.show', $detail))->assertSee('Nội soi dạ dày')
            ->assertDontSee('Siêu âm ổ bụng')->assertDontSee('Tổng phân tích nước tiểu');
        $this->get(route('employee.services.orders', ['group' => 'NS', 'service' => 'DVTDCN002', 'q' => 'CD-MAUDV-02', 'assignee' => 'mine', 'status' => 'PROCESSING']))
            ->assertOk()->assertSee('CD-MAUDV-02')->assertSee('Nội soi Tai Mũi Họng')->assertDontSee('X-quang cột sống thắt lưng');
        $this->get(route('employee.services.orders', ['group' => 'XN', 'service' => 'DVXN004', 'q' => 'CD-MAUDV-01', 'status' => 'WAITING_PAYMENT']))
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

    public function test_text_or_pdf_alone_can_be_saved_and_opened(): void
    {
        Storage::fake('local');
        $item = $this->startLaboratory();
        $this->saveResult('laboratory', $item, ['result_summary' => 'Nội dung xét nghiệm'])->assertSessionHasNoErrors();
        $this->assertTrue($item->fresh()->result->hasContent());
        $pdf = UploadedFile::fake()->createWithContent('phieu.pdf', '%PDF-1.4'."\n".'1 0 obj << >> endobj'."\n".'%%EOF');
        $this->saveResult('laboratory', $item, ['result_summary' => '', 'result_pdf' => $pdf])->assertSessionHasNoErrors();
        $this->assertTrue($item->fresh()->result->hasContent());
        $this->get(route('employee.laboratory.document', [$item->id, 'pdf']))->assertOk();
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
        $this->saveResult('laboratory', $item, ['result_summary' => '', 'parameters_present' => '1', 'parameters' => [['name' => 'Chỉ số A', 'value' => '0', 'unit' => '']]])->assertSessionHasNoErrors();
        $this->assertTrue($item->fresh()->result->hasContent());
    }

    public function test_stale_version_is_not_replaced_after_validation_redirect(): void
    {
        $item = $this->startLaboratory();
        $version = ServiceResult::editVersion(null);
        $this->saveResult('laboratory', $item, ['result_summary' => 'Phiên bản mới', 'result_version' => $version])->assertSessionHasNoErrors();
        $this->saveResult('laboratory', $item, ['result_summary' => 'Dữ liệu cũ', 'result_version' => $version])->assertSessionHasErrors('service');
        $this->get(route('employee.laboratory.show', $item->id))->assertOk()->assertSee('value="'.$version.'"', false)->assertSee('Dữ liệu cũ');
        $this->saveResult('laboratory', $item, ['result_summary' => 'Dữ liệu cũ', 'result_version' => $version])->assertSessionHasErrors('service');
        $this->assertSame('Phiên bản mới', $item->fresh()->result->result_summary);
    }

}
