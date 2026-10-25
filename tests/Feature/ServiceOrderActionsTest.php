<?php

namespace Tests\Feature;

use App\Models\MedicalService;
use App\Models\MedicalVisit;
use App\Models\PaymentStatus;
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
        $this->post(route('admin.payables.payment-transactions.store', $item->payable), [
            'payment_method' => 'CASH', 'confirm_payment' => 1,
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

        $this->post(route('admin.service-order-items.service-results.store', $item), [
            'result_summary' => 'Kết quả thực tế dùng trong kiểm thử', 'confirm_create' => 1,
        ])->assertSessionHasNoErrors()->assertSessionMissing('error');
        $this->assertActionVisible($item, 'complete', true);
        $this->post(route('admin.service-order-items.complete', $item))->assertSessionHasNoErrors()->assertSessionMissing('error');
        $this->assertSame('COMPLETED', $item->fresh()->serviceStatus->name);
        $this->assertActionVisible($item, 'complete', false);
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
        $this->post(route('admin.service-order-items.service-results.store', $item), [
            'result_summary' => 'Kết quả thực tế dùng trong kiểm thử', 'confirm_create' => 1,
        ])->assertSessionHasNoErrors()->assertSessionMissing('error');
        ServiceStatus::where('name', 'PROCESSING')->update(['allows_transition' => false]);
        $this->assertActionVisible($item, 'complete', false);
        $this->post(route('admin.service-order-items.complete', $item))->assertSessionHas('error');
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
