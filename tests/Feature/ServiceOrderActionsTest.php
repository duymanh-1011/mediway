<?php

namespace Tests\Feature;

use App\Models\MedicalService;
use App\Models\MedicalVisit;
use App\Models\PaymentStatus;
use App\Models\QueueStatus;
use App\Models\ServiceGroup;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderItem;
use App\Models\ServiceStatus;
use App\Models\User;
use App\Models\VisitStatus;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceOrderActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 15)->setTime(10, 0));
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail());
    }

    private function unpaidItem(): ServiceOrderItem
    {
        return ServiceOrder::where('service_order_code', 'CD000004')->firstOrFail()->items()->firstOrFail();
    }

    public function test_visit_without_orders_has_only_one_create_order_link_in_its_header(): void
    {
        $visit = MedicalVisit::whereDoesntHave('serviceOrders')->firstOrFail();
        $visit->update(['visit_status_id' => VisitStatus::where('name', 'IN_EXAMINATION')->value('id')]);
        $html = $this->get(route('admin.visits.show', $visit))->assertOk()->getContent();
        $document = new \DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $xpath = new \DOMXPath($document);
        $links = $xpath->query('//a[@href="'.route('admin.visits.service-orders.create', $visit).'"]');
        $this->assertCount(1, $links);
        $this->assertCount(0, $xpath->query('//div[contains(@class,"empty-service-orders")]//a'));
    }

    private function pay(ServiceOrderItem $item): void
    {
        $this->travelTo($item->payable->medicalVisit->appointment->appointment_date->copy()->setTime(10, 0));
        $this->post(route('admin.payables.payment-transactions.store', $item->payable), [
            'payment_method' => 'CASH', 'confirm_payment' => 1, 'collected_by_employee_id' => \App\Models\Employee::where('employee_code', 'NV000004')->value('id'),
        ])->assertSessionHasNoErrors()->assertSessionMissing('error');
    }

    private function assertActionVisible(ServiceOrderItem $item, string $action, bool $visible): void
    {
        $response = $this->get(route('admin.service-orders.show', $item->service_order_id))->assertOk();
        $this->assertSame($visible, str_contains(
            $response->getContent(), 'action="'.route('admin.service-order-items.'.$action, $item).'"'
        ), 'Hiển thị thao tác '.$action.' cho dịch vụ '.$item->id);
        $response->assertViewHas('order', function (ServiceOrder $order) use ($item): bool {
            $status = $order->items->firstWhere('id', $item->id)->serviceStatus;

            return array_key_exists('allows_transition', $status->getAttributes());
        });
    }

    public function test_new_service_displays_actions_through_payment_execution_result_and_completion(): void
    {
        $this->post(route('admin.medical-services.store'), [
            'name' => 'Dịch vụ mới kiểm thử thao tác', 'price' => 120000, 'unit' => 'lần',
            'status' => 'ACTIVE', 'display_order' => 0,
            'service_group_ids' => [ServiceGroup::where('service_group_code', 'XN')->value('id')],
        ])->assertSessionHasNoErrors()->assertSessionMissing('error');
        $service = MedicalService::where('name', 'Dịch vụ mới kiểm thử thao tác')->firstOrFail();
        $visit = MedicalVisit::whereHas('status', fn ($query) => $query->where('name', 'IN_EXAMINATION'))->firstOrFail();
        $this->post(route('admin.visits.service-orders.store', $visit), [
            'medical_service_ids' => [$service->id],
        ])->assertSessionHasNoErrors()->assertSessionMissing('error');
        $item = ServiceOrderItem::where('medical_service_id', $service->id)->sole();

        $this->assertActionVisible($item, 'start', false);
        $this->pay($item);
        $this->assertActionVisible($item, 'start', true);
        $this->post(route('admin.service-order-items.start', $item))->assertSessionHasNoErrors()->assertSessionMissing('error');
        $this->assertSame('PROCESSING', $item->fresh()->serviceStatus->name);
        $this->assertActionVisible($item, 'start', false);
        $this->assertActionVisible($item, 'complete', false);
        $this->get(route('admin.service-orders.show', $item->service_order_id))->assertOk()
            ->assertSee(route('admin.service-order-items.service-results.create', $item));
        $this->get(route('admin.service-order-items.service-results.create', $item))->assertOk()
            ->assertSee('Xác nhận và hoàn tất')
            ->assertDontSee('name="resulted_at"', false);

        $this->freezeSecond(function ($confirmedAt) use ($item): void {
            $this->post(route('admin.service-order-items.service-results.store', $item), [
                'result_summary' => 'Kết quả thực tế dùng trong kiểm thử', 'confirm_create' => 1,
                'resulted_at' => '2000-01-01 08:00:00',
            ])->assertSessionHasNoErrors()->assertSessionMissing('error');
            $item->refresh();
            $this->assertSame('COMPLETED', $item->serviceStatus->name);
            $this->assertEquals($confirmedAt, $item->completed_at);
            $this->assertEquals($confirmedAt, $item->result->resulted_at);
            $this->get(route('admin.service-results.show', $item->result))->assertOk()
                ->assertSee($confirmedAt->format('d/m/Y H:i:s'));
        });
        $this->assertActionVisible($item, 'complete', false);
    }

    public function test_visit_accepts_multiple_orders_and_waits_for_services_across_orders(): void
    {
        $visit = MedicalVisit::whereDoesntHave('serviceOrders')->firstOrFail();
        $visit->update(['visit_status_id' => VisitStatus::where('name', 'IN_EXAMINATION')->value('id')]);
        [$firstService, $secondService] = MedicalService::where('status', 'ACTIVE')->orderBy('id')->limit(2)->pluck('id')->all();

        $this->post(route('admin.visits.service-orders.store', $visit), ['medical_service_ids' => [$firstService]])
            ->assertSessionHasNoErrors()->assertSessionMissing('error');
        $first = $visit->serviceOrders()->sole();
        $this->assertSame('WAITING_FOR_SERVICE', $visit->fresh()->status->name);
        $this->get(route('admin.visits.show', $visit))->assertOk()
            ->assertSee(route('admin.visits.service-orders.create', $visit));
        $this->get(route('admin.visits.service-orders.create', $visit))->assertOk();
        $this->post(route('admin.visits.service-orders.store', $visit), ['medical_service_ids' => [$secondService]])
            ->assertSessionHasNoErrors()->assertSessionMissing('error');
        $second = $visit->serviceOrders()->whereKeyNot($first->id)->sole();
        $items = [$first->items()->sole(), $second->items()->sole()];

        foreach ($items as $index => $item) {
            $this->assertNotNull($item->payable);
            $this->pay($item);
            $this->post(route('admin.service-order-items.start', $item))->assertSessionHasNoErrors()->assertSessionMissing('error');
            $this->post(route('admin.service-order-items.service-results.store', $item), [
                'result_summary' => 'Kết quả kiểm thử', 'confirm_create' => 1,
            ])->assertSessionHasNoErrors()->assertSessionMissing('error');
            $this->assertSame('COMPLETED', $item->serviceOrder()->firstOrFail()->status);
            $this->assertSame($index, $visit->queues()->where('queue_type', 'RETURN_AFTER_SERVICE')->count());
        }

        $returnQueue = $visit->queues()->where('queue_type', 'RETURN_AFTER_SERVICE')->sole();
        $this->assertSame('WAITING', $returnQueue->status->name);
        $this->post(route('admin.visits.service-orders.store', $visit), ['medical_service_ids' => [$firstService]])
            ->assertSessionHasNoErrors()->assertSessionMissing('error');
        $this->assertSame(3, $visit->serviceOrders()->count());
        $this->assertSame('COMPLETED', $returnQueue->fresh()->status->name);
    }

    public function test_confirming_an_existing_result_records_completion_once(): void
    {
        $item = $this->unpaidItem();
        $this->pay($item);
        $this->post(route('admin.service-order-items.start', $item))->assertSessionHasNoErrors()->assertSessionMissing('error');
        $result = $item->result()->create([
            'result_parameters' => [['name' => 'Chỉ số kiểm thử', 'value' => '1']],
            'resulted_at' => now()->subMinute(),
        ]);
        $this->get(route('admin.service-results.edit', $result))->assertOk()
            ->assertSee('Xác nhận và hoàn tất')
            ->assertDontSee('name="resulted_at"', false);
        $confirmedAt = now()->addMinute()->startOfSecond();
        $this->travelTo($confirmedAt);
        $this->patch(route('admin.service-results.update', $result), ['confirm_update' => 1])->assertSessionHasNoErrors();
        $this->assertEquals($confirmedAt, $result->fresh()->resulted_at);
        $this->assertEquals($confirmedAt, $item->fresh()->completed_at);

        $this->travel(1)->minute();
        $this->patch(route('admin.service-results.update', $result), [
            'result_summary' => 'Bổ sung nội dung kiểm thử', 'confirm_update' => 1,
            'resulted_at' => now()->toDateTimeString(),
        ])->assertSessionHasNoErrors();
        $this->assertSame('Bổ sung nội dung kiểm thử', $result->fresh()->result_summary);
        $this->assertEquals($confirmedAt, $result->fresh()->resulted_at);
        $this->assertEquals($confirmedAt, $item->fresh()->completed_at);
    }

    public function test_failed_queue_handoff_rolls_back_result_and_completion(): void
    {
        $item = $this->unpaidItem();
        $item->serviceOrder->items()->whereKeyNot($item->id)->update([
            'service_status_id' => ServiceStatus::where('name', 'CANCELLED')->value('id'),
        ]);
        $this->pay($item);
        $this->post(route('admin.service-order-items.start', $item))->assertSessionHasNoErrors()->assertSessionMissing('error');
        QueueStatus::where('name', 'WAITING')->update(['is_active' => false]);
        $this->post(route('admin.service-order-items.service-results.store', $item), [
            'result_summary' => 'Kết quả kiểm thử', 'confirm_create' => 1,
        ])->assertSessionHasErrors('service_status_id');
        $item->refresh();
        $this->assertSame('PROCESSING', $item->serviceStatus->name);
        $this->assertNull($item->completed_at);
        $this->assertNull($item->result);
    }

    public function test_frozen_pending_status_still_hides_and_blocks_start_action(): void
    {
        $item = $this->unpaidItem();
        $this->pay($item);
        ServiceStatus::where('name', 'PENDING')->update(['allows_transition' => false]);
        $this->assertActionVisible($item, 'start', false);
        $this->post(route('admin.service-order-items.start', $item))->assertSessionHas('error');
        $this->assertSame('PENDING', $item->fresh()->serviceStatus->name);
        $this->assertNull($item->fresh()->started_at);
    }

    public function test_frozen_processing_status_still_hides_and_blocks_complete_action(): void
    {
        $item = $this->unpaidItem();
        $this->pay($item);
        $this->post(route('admin.service-order-items.start', $item))->assertSessionHasNoErrors()->assertSessionMissing('error');
        $result = $item->result()->create(['result_summary' => 'Kết quả kiểm thử']);
        ServiceStatus::where('name', 'PROCESSING')->update(['allows_transition' => false]);
        $this->assertActionVisible($item, 'complete', false);
        $this->post(route('admin.service-order-items.complete', $item))->assertSessionHas('error');
        $this->patch(route('admin.service-results.update', $result), [
            'result_summary' => 'Nội dung thay đổi', 'confirm_update' => 1,
        ])->assertSessionHasErrors('service_status_id');
        $this->assertSame('Kết quả kiểm thử', $result->fresh()->result_summary);
        $this->assertSame('PROCESSING', $item->fresh()->serviceStatus->name);
        $this->assertNull($item->fresh()->completed_at);
    }

    public function test_start_action_uses_payment_permission_instead_of_hardcoded_paid_status(): void
    {
        $item = $this->unpaidItem();
        $status = PaymentStatus::forceCreate([
            'name' => 'APPROVED_CREDIT', 'allows_service_execution' => true,
            'process_order' => 10, 'is_active' => true,
        ]);
        $item->payable->update(['payment_status_id' => $status->id]);

        $this->assertActionVisible($item, 'start', true);
    }
}
