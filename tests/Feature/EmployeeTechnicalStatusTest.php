<?php

namespace Tests\Feature;

use App\Models\Payable;
use App\Models\PaymentStatus;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderItem;
use App\Models\ServiceStatus;
use App\Models\User;
use App\Support\TechnicalServiceStatus;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeTechnicalStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
    }

    public function test_display_uses_exact_payment_status_and_handles_missing_data(): void
    {
        $item = new ServiceOrderItem;
        $item->setRelation('serviceStatus', (new ServiceStatus)->forceFill(['name' => 'PENDING']));
        $cases = [
            ['PAID', 'Đã thanh toán / Chờ thực hiện', 'paid'],
            ['UNPAID', 'Chưa thanh toán / Chờ thực hiện', 'waiting'],
            ['PENDING', 'Chờ xác nhận thanh toán / Chờ thực hiện', 'waiting'],
            ['CANCELLED', 'Đã hủy khoản phải thu / Chờ thực hiện', 'cancelled'],
            ['CREDIT', 'Được ghi nợ / Chờ thực hiện', 'neutral'],
        ];
        foreach ($cases as [$name, $label, $tone]) {
            $payment = (new PaymentStatus)->forceFill(['name' => $name, 'description' => $name === 'CREDIT' ? 'Được ghi nợ' : '', 'allows_service_execution' => true]);
            $item->setRelation('payable', (new Payable)->setRelation('paymentStatus', $payment));
            $this->assertSame(['label' => $label, 'tone' => $tone], TechnicalServiceStatus::for($item));
        }
        $item->setRelation('payable', null);
        $this->assertSame(['label' => 'Chưa có thông tin thanh toán / Chờ thực hiện', 'tone' => 'neutral'], TechnicalServiceStatus::for($item));
        $item->setRelation('payable', (new Payable)->setRelation('paymentStatus', null));
        $this->assertSame(['label' => 'Chưa có thông tin thanh toán / Chờ thực hiện', 'tone' => 'neutral'], TechnicalServiceStatus::for($item));
        foreach (['PROCESSING' => ['Đang thực hiện', 'processing'], 'COMPLETED' => ['Đã hoàn thành', 'completed'], 'CANCELLED' => ['Đã hủy', 'cancelled']] as $name => [$label, $tone]) {
            $item->setRelation('serviceStatus', (new ServiceStatus)->forceFill(['name' => $name]));
            $this->assertSame(['label' => $label, 'tone' => $tone], TechnicalServiceStatus::for($item));
        }
    }

    public function test_lab_list_and_detail_update_after_payment_and_start_without_changing_filter(): void
    {
        $item = ServiceOrder::where('service_order_code', 'CD000004')->firstOrFail()->items()->firstOrFail();
        $user = User::where('business_code', 'NV000002')->firstOrFail();
        $this->actingAs($user)->get(route('employee.laboratory.index', ['status' => 'PENDING']))->assertOk()->assertSee('Chưa thanh toán / Chờ thực hiện')->assertSee('tech-status--waiting');
        $item->payable->update(['payment_status_id' => PaymentStatus::where('name', 'PAID')->value('id')]);
        $this->get(route('employee.laboratory.index', ['status' => 'PENDING']))->assertOk()->assertSee('Đã thanh toán / Chờ thực hiện')->assertSee('tech-status--paid')->assertSee(route('employee.laboratory.index'));
        $this->get(route('employee.laboratory.show', $item->id))->assertOk()->assertSee('Đã thanh toán / Chờ thực hiện')->assertSee('tech-status--paid');
        $this->post(route('employee.laboratory.start', $item->id))->assertSessionHasNoErrors();
        $this->get(route('employee.laboratory.show', $item->id))->assertOk()->assertSee('Đang thực hiện')->assertSee('tech-status--processing');
        $this->get(route('employee.laboratory.index', ['status' => 'PROCESSING']))->assertOk()->assertSee('Đang thực hiện');
        $this->get(route('employee.laboratory.index', ['status' => 'CANCELLED']))->assertOk()->assertDontSee($item->medicalService->name);
        $this->get(route('employee.laboratory.index'))->assertOk()->assertSee($item->medicalService->name);
    }

    public function test_imaging_has_same_status_and_cannot_view_other_groups(): void
    {
        $item = ServiceOrderItem::whereHas('medicalService.serviceGroups', fn ($q) => $q->where('service_group_code', 'CDHA'))->whereHas('serviceStatus', fn ($q) => $q->where('name', 'COMPLETED'))->firstOrFail();
        $this->actingAs(User::where('business_code', 'NV000003')->firstOrFail());
        $this->get(route('employee.imaging.index'))->assertOk()->assertSee('Đã hoàn thành')->assertSee('tech-status--completed');
        $this->get(route('employee.imaging.show', $item->id))->assertOk()->assertSee('Đã hoàn thành')->assertSee('tech-status--completed');
        $labItem = ServiceOrder::where('service_order_code', 'CD000004')->firstOrFail()->items()->firstOrFail();
        $this->get(route('employee.imaging.show', $labItem->id))->assertNotFound();
        $this->get(route('employee.laboratory.index'))->assertForbidden();
    }
}
