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
use Tests\TestCase;

class DoctorServiceOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
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

    public function test_wrong_doctor_and_closed_visit_cannot_create_orders(): void
    {
        $visit = $this->activeVisit();
        $foreign = MedicalVisit::where('visit_code', 'LK000005')->firstOrFail();
        $service = MedicalService::where('service_code', 'DVXN007')->firstOrFail();
        $this->get(route('doctor.visits.orders.create', $foreign))->assertNotFound();
        $this->post(route('doctor.visits.orders.store', $foreign), ['medical_service_ids' => [$service->id]])->assertNotFound();
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
        $this->get(route('doctor.visits.show', $other))->assertNotFound();
    }
}
