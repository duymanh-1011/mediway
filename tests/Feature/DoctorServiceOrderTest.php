<?php

namespace Tests\Feature;

use App\Models\MedicalService;
use App\Models\MedicalVisit;
use App\Models\PaymentStatus;
use App\Models\ServiceResult;
use App\Models\ServiceStatus;
use App\Models\User;
use App\Models\VisitStatus;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DoctorServiceOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $this->withSession(['doctor_workspace_mode' => 'examination']);
        $this->actingAs(User::where('business_code', 'BS000001')->firstOrFail());
    }

    private function activeVisit(): MedicalVisit
    {
        $visit = MedicalVisit::where('visit_code', 'LK000004')->firstOrFail();
        $visit->update(['visit_status_id' => VisitStatus::where('name', 'IN_EXAMINATION')->value('id')]);
        return $visit->fresh();
    }

    public function test_doctor_orders_real_services_with_shared_payment_and_status_workflow(): void
    {
        $visit = $this->activeVisit();
        $service = MedicalService::where('service_code', 'DVXN007')->firstOrFail();
        $this->get(route('doctor.visits.orders.create', $visit))->assertOk()->assertSee($service->name);
        $this->post(route('doctor.visits.orders.store', $visit), [
            'medical_service_ids' => [$service->id], 'reason' => 'Kiểm thử chỉ định',
        ])->assertRedirect(route('doctor.visits.show', $visit))->assertSessionHasNoErrors();
        $order = $visit->serviceOrders()->where('reason', 'Kiểm thử chỉ định')->sole();
        $item = $order->items()->sole();
        $this->assertSame($visit->doctor_id, $order->ordered_by_doctor_id);
        $this->assertSame($service->id, $item->medical_service_id);
        $this->assertSame('PENDING', $item->serviceStatus->name);
        $this->assertSame('UNPAID', $item->payable->paymentStatus->name);
        $this->assertSame('WAITING_FOR_SERVICE', $visit->fresh()->status->name);
        $this->get(route('doctor.visits.show', $visit))->assertOk()->assertSee($service->name)->assertSee('Chưa có kết quả');

        // Gửi lại không tạo thêm phiếu hoặc khoản thu cho dịch vụ đang chờ.
        $before = $visit->serviceOrders()->count();
        $this->post(route('doctor.visits.orders.store', $visit), ['medical_service_ids' => [$service->id]])->assertSessionHasErrors('medical_service_ids');
        $this->assertSame($before, $visit->serviceOrders()->count());
        $this->assertSame(1, $item->payable()->count());
    }

    public function test_order_form_shows_catalog_and_keeps_selected_values_after_validation(): void
    {
        $visit = $this->activeVisit();
        $service = MedicalService::where('service_code', 'DVXN007')->firstOrFail();
        // Lựa chọn và thông tin nhập phải còn sau khi máy chủ yêu cầu sửa lỗi.
        $response = $this->withSession(['_old_input' => [
            'medical_service_ids' => [(string) $service->id], 'reason' => 'Lý do còn giữ', 'instructions' => 'Hướng dẫn còn giữ',
        ]])->get(route('doctor.visits.orders.create', $visit))->assertOk()
            ->assertSee('Lý do còn giữ')->assertSee('Hướng dẫn còn giữ')
            ->assertSee('data-service-order-picker', false)->assertSee('data-service-search-field hidden', false)
            ->assertSee($service->service_code)->assertSee($service->name)->assertSee('Tìm dịch vụ');
        $this->assertMatchesRegularExpression('/name="medical_service_ids\[\]" value="'.preg_quote((string) $service->id, '/').'"\s+checked/', $response->getContent());
    }

    public function test_multiple_services_can_be_submitted_without_javascript(): void
    {
        $visit = $this->activeVisit();
        $services = MedicalService::whereIn('service_code', ['DVXN007', 'DVCD005'])->get();
        $this->assertCount(2, $services);

        // Máy chủ vẫn nhận nhiều ô chọn thông thường khi trình duyệt tắt JavaScript.
        $this->post(route('doctor.visits.orders.store', $visit), [
            'medical_service_ids' => $services->modelKeys(),
            'reason' => 'Kiểm thử nhiều dịch vụ',
        ])->assertSessionHasNoErrors()->assertRedirect(route('doctor.visits.show', $visit));

        $order = $visit->serviceOrders()->where('reason', 'Kiểm thử nhiều dịch vụ')->sole();
        $this->assertCount(2, $order->items);
        foreach ($order->items as $item) {
            $this->assertSame('UNPAID', $item->payable->paymentStatus->name);
        }
    }

    public function test_empty_catalog_explains_why_no_service_can_be_selected(): void
    {
        $visit = $this->activeVisit();
        MedicalService::query()->update(['status' => 'INACTIVE']);

        $this->get(route('doctor.visits.orders.create', $visit))->assertOk()
            ->assertSee('Chưa có dịch vụ đang hoạt động trong danh mục.');
    }

    public function test_doctor_can_order_for_other_visit_but_not_closed_visit(): void
    {
        $visit = $this->activeVisit();
        $foreign = MedicalVisit::where('visit_code', 'LK000005')->firstOrFail();
        $service = MedicalService::where('service_code', 'DVXN007')->firstOrFail();
        $foreign->update(['appointment_id' => null, 'visit_status_id' => VisitStatus::where('name', 'IN_EXAMINATION')->value('id')]);
        $this->get(route('doctor.visits.orders.create', $foreign))->assertOk();
        $this->post(route('doctor.visits.orders.store', $foreign), ['medical_service_ids' => [$service->id]])->assertSessionHasNoErrors();
        $this->assertSame(User::where('business_code', 'BS000001')->firstOrFail()->doctor->id, $foreign->serviceOrders()->latest('id')->firstOrFail()->ordered_by_doctor_id);
        $visit->update(['visit_status_id' => VisitStatus::where('name', 'COMPLETED')->value('id')]);
        $before = $visit->serviceOrders()->count();
        $this->post(route('doctor.visits.orders.store', $visit), ['medical_service_ids' => [$service->id]])->assertSessionHasErrors('visit');
        $this->assertSame($before, $visit->serviceOrders()->count());
    }

    public function test_invalid_or_missing_payment_configuration_does_not_create_partial_orders(): void
    {
        $visit = $this->activeVisit();
        $service = MedicalService::where('service_code', 'DVXN007')->firstOrFail();
        $before = $visit->serviceOrders()->count();
        $this->post(route('doctor.visits.orders.store', $visit), ['medical_service_ids' => [$service->id, 999999]])->assertSessionHasErrors();
        PaymentStatus::where('name', 'UNPAID')->update(['is_active' => false]);
        $this->post(route('doctor.visits.orders.store', $visit), ['medical_service_ids' => [$service->id]])->assertSessionHasErrors('medical_service_ids');
        $this->assertSame($before, $visit->serviceOrders()->count());
        $this->assertSame('IN_EXAMINATION', $visit->fresh()->status->name);
    }

    public function test_doctor_can_read_only_results_from_assigned_visit(): void
    {
        $visit = MedicalVisit::where('visit_code', 'LK000001')->firstOrFail();
        $result = ServiceResult::whereHas('serviceOrderItem.serviceOrder', fn ($query) => $query->where('medical_visit_id', $visit->id))->firstOrFail();
        $result->update([
            'result_summary' => 'Nội dung kết quả kiểm thử',
            'result_parameters' => [['name' => 'Chỉ số kiểm thử', 'value' => '5.4', 'unit' => 'mg/L']],
            'technical_details' => [['name' => 'Thông tin bổ sung', 'value' => 'Giá trị kiểm thử']],
        ]);
        $this->get(route('doctor.visits.show', $visit))->assertOk()
            ->assertSee('Nội dung kết quả kiểm thử')->assertSee('Chỉ số kiểm thử')->assertSee('5.4 mg/L')
            ->assertSee('Thông tin bổ sung')->assertSee('Giá trị kiểm thử');
        $other = MedicalVisit::where('visit_code', 'LK000002')->firstOrFail();
        $this->get(route('doctor.visits.show', $other))->assertOk();
    }

    public function test_doctor_can_open_only_existing_safe_files_from_an_assigned_visit(): void
    {
        Storage::fake('local');
        $visit = MedicalVisit::where('visit_code', 'LK000001')->firstOrFail();
        $otherVisit = MedicalVisit::where('visit_code', 'LK000002')->firstOrFail();
        $result = ServiceResult::whereHas('serviceOrderItem.serviceOrder', fn ($query) => $query->where('medical_visit_id', $visit->id))->firstOrFail();
        $directory = 'service-results/'.$result->service_order_item_id;
        Storage::disk('local')->put($directory.'/result.pdf', "%PDF-1.4\n%%EOF");
        Storage::disk('local')->put($directory.'/result.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a3p8AAAAASUVORK5CYII='));
        $result->update(['result_summary' => null, 'result_conclusion' => null, 'result_parameters' => null,
            'technical_details' => null, 'result_pdf_path' => $directory.'/result.pdf', 'result_image_path' => $directory.'/result.png']);
        $pdf = route('doctor.visits.results.document', [$visit->id, $result->id, 'pdf']);
        $image = route('doctor.visits.results.document', [$visit->id, $result->id, 'image']);
        $this->get(route('doctor.visits.show', $visit))->assertOk()->assertSee('Xem tệp PDF')->assertSee('Xem ảnh kết quả')->assertSee($pdf, false);
        $response = $this->get($pdf)->assertOk();
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->get($image)->assertOk();
        $this->get(route('doctor.visits.results.document', [$otherVisit->id, $result->id, 'pdf']))->assertNotFound();
        $this->flushSession();
        $this->actingAsDoctorWorkspace(User::where('business_code', 'BS000002')->firstOrFail())->get($pdf)->assertOk();
        $this->flushSession()->actingAs(User::where('business_code', 'BN000001')->firstOrFail())->get($pdf)->assertForbidden();

        $this->flushSession()->actingAsDoctorWorkspace(User::where('business_code', 'BS000001')->firstOrFail());
        $result->update(['result_pdf_path' => $directory.'/../result.pdf', 'result_image_path' => $directory.'/missing.png']);
        $this->get($pdf)->assertNotFound();
        $this->get($image)->assertNotFound();
        $this->get(route('doctor.visits.show', $visit))->assertOk()->assertDontSee('Xem tệp PDF')->assertDontSee('Xem ảnh kết quả');
    }
}
