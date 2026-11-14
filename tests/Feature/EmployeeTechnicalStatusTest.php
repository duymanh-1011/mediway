<?php

namespace Tests\Feature;

use App\Models\Payable;
use App\Models\PaymentStatus;
use App\Models\MedicalService;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderItem;
use App\Models\ServiceStatus;
use App\Models\User;
use App\Support\TechnicalServiceStatus;
use Tests\Fixtures\Seeders\WorkflowFixtureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeTechnicalStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(WorkflowFixtureSeeder::class);
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
        $user = User::where('business_code', 'BS000001')->firstOrFail();
        $service = ['group' => 'XN', 'service' => $item->medicalService->service_code];
        $pendingUrl = route('doctor.services.orders', $service);
        $this->actingAsDoctorWorkspace($user, 'services')->get($pendingUrl)->assertOk()->assertSee('Chưa thanh toán / Chờ thực hiện')->assertSee('tech-status--waiting');
        $item->payable->update(['payment_status_id' => PaymentStatus::where('name', 'PAID')->value('id')]);
        $this->get($pendingUrl)->assertOk()->assertSee('Đã thanh toán / Chờ thực hiện')->assertSee('tech-status--paid')->assertSee(route('doctor.services.orders', $service));
        $this->get(route('doctor.laboratory.show', $item->id))->assertOk()->assertSee('Đã thanh toán / Chờ thực hiện')->assertSee('tech-status--paid');
        $this->post(route('doctor.laboratory.start', $item->id))->assertSessionHasNoErrors();
        $this->get(route('doctor.laboratory.show', $item->id))->assertOk()->assertSee('Đang thực hiện')->assertSee('tech-status--processing');
        $this->get(route('doctor.services.orders', [...$service, 'status' => 'PROCESSING']))->assertOk()->assertSee('Đang thực hiện');
        $this->get(route('doctor.services.orders', [...$service, 'status' => 'CANCELLED']))->assertOk()->assertDontSee($item->serviceOrder->service_order_code);
        $this->get(route('doctor.services.orders', $service))->assertOk()->assertSee($item->medicalService->name);
    }

    public function test_imaging_has_same_status_and_cannot_view_other_groups(): void
    {
        $item = ServiceOrder::where('service_order_code', 'CD000004')->firstOrFail()->items()->firstOrFail();
        $item->update(['medical_service_id' => MedicalService::where('service_code', 'DVCD001')->value('id')]);
        $item->payable->update(['payment_status_id' => PaymentStatus::where('name', 'PAID')->value('id')]);
        $doctor = User::where('business_code', 'BS000001')->firstOrFail();
        $this->actingAsDoctorWorkspace($doctor, 'services')->post(route('doctor.services.start', $item->id))->assertSessionHasNoErrors();
        $this->get(route('doctor.services.orders', ['group' => 'CDHA', 'service' => $item->medicalService->service_code]))
            ->assertOk()->assertSee('Đang thực hiện')->assertSee('tech-status--processing');
        $this->get(route('doctor.imaging.show', $item->id))->assertOk()->assertSee('Đang thực hiện')->assertSee('tech-status--processing');
        $this->get(route('doctor.laboratory.show', $item->id))->assertNotFound();
        $this->flushSession()->actingAsDoctorWorkspace(User::where('business_code', 'BS000002')->firstOrFail(), 'services')
            ->get(route('doctor.imaging.show', $item->id))->assertNotFound();
    }
    public function test_lab_searches_patient_name_code_or_phone_with_other_filters(): void
    {
        $item = ServiceOrder::where('service_order_code', 'CD000004')->firstOrFail()->items()->firstOrFail();
        $patient = $item->serviceOrder->medicalVisit->patient;
        $doctor = User::where('business_code', 'BS000001')->firstOrFail();
        $this->actingAsDoctorWorkspace($doctor, 'services');
        $service = ['group' => 'XN', 'service' => $item->medicalService->service_code];
        $filter = route('doctor.services.orders.filter', $service);
        $this->followingRedirects()->post($filter, ['q' => $patient->patient_code, 'status' => 'PENDING', 'date' => $item->serviceOrder->ordered_at->format('Y-m-d')])
            ->assertOk()->assertSee($patient->full_name)->assertSee($patient->patient_code);
        $this->followingRedirects()->post($filter, ['q' => $patient->full_name])->assertOk()->assertSee($patient->patient_code);
        $url = $this->post($filter, ['q' => $patient->user->phone, 'status' => 'PENDING',
            'date' => $item->serviceOrder->ordered_at->format('Y-m-d')])->assertRedirect()->headers->get('Location');
        $this->assertStringNotContainsString($patient->user->phone, $url);
        $this->assertStringContainsString('search_ref=', $url);
        $this->get($url)->assertOk()->assertSee($item->serviceOrder->service_order_code);
        $this->get(route('doctor.services.orders', [...$service, 'q' => $patient->user->phone, 'status' => 'WAITING_PAYMENT']))
            ->assertRedirect(route('doctor.services.orders', [...$service, 'status' => 'WAITING_PAYMENT']));
        $this->followingRedirects()->post($filter, ['q' => 'BN-KHONG-TON-TAI'])->assertOk()->assertSee('Không tìm thấy phiếu chỉ định');
        $this->get($url)->assertOk()->assertSee($item->serviceOrder->service_order_code);
        $this->get(route('doctor.services.orders', $service))->assertOk()->assertSee($patient->patient_code);
        $item->payable->update(['payment_status_id' => PaymentStatus::where('name', 'PAID')->value('id')]);
        $this->post(route('doctor.services.start', $item->id))->assertSessionHasNoErrors();
        $this->flushSession()->actingAsEmployeeWorkspace(User::where('business_code', 'NV000002')->firstOrFail(), 'cashier')
            ->get(route('employee.services.index'))->assertForbidden();
    }

    public function test_imaging_search_stays_in_doctor_group_and_assignment(): void
    {
        $item = ServiceOrder::where('service_order_code', 'CD000004')->firstOrFail()->items()->firstOrFail();
        $item->update(['medical_service_id' => MedicalService::where('service_code', 'DVCD001')->value('id')]);
        $item->payable->update(['payment_status_id' => PaymentStatus::where('name', 'PAID')->value('id')]);
        $doctor = User::where('business_code', 'BS000001')->firstOrFail();
        $this->actingAsDoctorWorkspace($doctor, 'services')->post(route('doctor.services.start', $item->id))->assertSessionHasNoErrors();
        $patient = $item->serviceOrder->medicalVisit->patient;
        $service = ['group' => 'CDHA', 'service' => $item->medicalService->service_code];
        $filter = route('doctor.services.orders.filter', $service);
        $url = $this->post($filter, ['q' => $patient->patient_code])->assertRedirect()->headers->get('Location');
        $this->get($url)->assertOk()->assertSee($patient->full_name)->assertSee($patient->patient_code);
        $this->get(route('doctor.services.orders', ['group' => 'XN', 'service' => $item->medicalService->service_code]))->assertNotFound();
        $item->update(['performed_by_doctor_id' => User::where('business_code', 'BS000002')->firstOrFail()->doctor->id]);
        $this->get($url)->assertOk()->assertDontSee($item->serviceOrder->service_order_code);
        $this->followingRedirects()->post($filter, ['q' => $patient->user->phone])
            ->assertOk()->assertDontSee($item->serviceOrder->service_order_code);
        $this->flushSession()->actingAsDoctorWorkspace(User::where('business_code', 'BS000001')->firstOrFail(), 'services')
            ->get(route('doctor.services.orders', $service))->assertOk()->assertDontSee($item->serviceOrder->service_order_code);
    }

}
