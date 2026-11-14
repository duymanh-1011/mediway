<?php

namespace Tests\Feature;

use App\Models\MedicalVisit;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderItem;
use App\Models\ServiceStatus;
use App\Services\DoctorVisitReadiness;
use Tests\Fixtures\Seeders\WorkflowFixtureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorVisitReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_missing_clinical_content_without_changing_visit(): void
    {
        $this->seed(WorkflowFixtureSeeder::class);
        $visit = MedicalVisit::where('visit_code', 'LK000001')->firstOrFail()->replicate();
        $visit->visit_code = 'LKREADINESS1';
        $visit->appointment_id = null;
        $visit->save();
        $before = $visit->visit_status_id;

        $result = app(DoctorVisitReadiness::class)->check($visit);

        $this->assertFalse($result['ready']);
        $this->assertArrayHasKey('diagnosis', $result['reasons']);
        $this->assertArrayHasKey('conclusion', $result['reasons']);
        $this->assertSame($before, $visit->fresh()->visit_status_id);
        $this->assertSame(0, $visit->queues()->count());
    }

    public function test_requires_diagnosis_conclusion_and_no_unresolved_service(): void
    {
        $this->seed(WorkflowFixtureSeeder::class);
        $original = MedicalVisit::where('visit_code', 'LK000001')->firstOrFail();
        $visit = $original->replicate();
        $visit->visit_code = 'LKREADINESS2';
        $visit->appointment_id = null;
        $visit->save();
        $diagnosis = $original->diagnoses()->firstOrFail()->replicate();
        $visit->diagnoses()->save($diagnosis);
        $conclusion = $original->conclusion()->firstOrFail()->replicate();
        $visit->conclusion()->save($conclusion);

        $service = app(DoctorVisitReadiness::class);
        $this->assertTrue($service->check($visit)['ready']);

        $order = ServiceOrder::firstOrFail()->replicate();
        $order->service_order_code = 'CDREADINESS1';
        $order->medical_visit_id = $visit->id;
        $order->save();
        $item = ServiceOrderItem::firstOrFail()->replicate();
        $item->service_order_id = $order->id;
        $item->service_status_id = ServiceStatus::where('name', 'PENDING')->value('id');
        $item->save();

        $result = $service->check($visit);
        $this->assertFalse($result['ready']);
        $this->assertArrayHasKey('service', $result['reasons']);
        $item->update(['service_status_id' => ServiceStatus::where('name', 'COMPLETED')->value('id')]);
        $this->assertTrue($service->check($visit)['ready']);
    }
}
