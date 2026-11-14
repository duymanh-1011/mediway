<?php

namespace Tests\Feature;

use App\Models\MedicalVisit;
use App\Models\QueueStatus;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderItem;
use App\Models\ServiceStatus;
use App\Models\User;
use App\Models\VisitStatus;
use Tests\Fixtures\Seeders\WorkflowFixtureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorVisitCompletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(WorkflowFixtureSeeder::class);
        $this->withoutVite();
        $this->withSession(['doctor_workspace_mode' => 'examination']);
        $this->actingAs(User::where('business_code', 'BS000001')->firstOrFail());
    }

    private function readyVisit(): MedicalVisit
    {
        $source = MedicalVisit::where('visit_code', 'LK000001')->firstOrFail();
        $visit = $source->replicate();
        $visit->visit_code = 'LKDOCTORCOMPLETE';
        $visit->appointment_id = null;
        $visit->visit_status_id = VisitStatus::where('name', 'IN_EXAMINATION')->value('id');
        $visit->examination_started_at = now()->subHour();
        $visit->examination_ended_at = null;
        $visit->reception_queue_number = null;
        $visit->save();
        $visit->diagnoses()->save($source->diagnoses()->firstOrFail()->replicate());
        $visit->conclusion()->save($source->conclusion()->firstOrFail()->replicate());
        return $visit;
    }

    public function test_assigned_doctor_completes_ready_visit_once(): void
    {
        $visit = $this->readyVisit();
        $this->get(route('doctor.visits.show', $visit))->assertOk()->assertSee('Hoàn tất lượt khám');
        $this->post(route('doctor.visits.complete', $visit))->assertRedirect(route('doctor.visits.show', $visit))->assertSessionHasNoErrors();
        $this->assertSame('COMPLETED', $visit->fresh()->status->name);
        $this->assertNotNull($visit->fresh()->examination_ended_at);
        $this->assertDatabaseHas('audit_logs', ['entity_type' => 'medical_visits', 'entity_id' => $visit->id, 'role_name_snapshot' => 'Doctor']);
        $this->get(route('doctor.visits.show', $visit))->assertOk()->assertDontSee('>Hoàn tất lượt khám</button>', false);
        $this->post(route('doctor.visits.complete', $visit))->assertSessionHasErrors('visit');
    }

    public function test_missing_conclusion_and_invalid_status_prevent_completion_for_all_doctors(): void
    {
        $visit = $this->readyVisit();
        $visit->conclusion()->delete();
        $this->post(route('doctor.visits.complete', $visit))->assertSessionHasErrors('visit');
        $this->assertSame('IN_EXAMINATION', $visit->fresh()->status->name);
        $this->flushSession();
        $this->actingAsDoctorWorkspace(User::where('business_code', 'BS000002')->firstOrFail())
            ->post(route('doctor.visits.complete', $visit))->assertSessionHasErrors('visit');
        $visit->update(['visit_status_id' => VisitStatus::where('name', 'WAITING')->value('id')]);
        $this->flushSession();
        $this->actingAsDoctorWorkspace(User::where('business_code', 'BS000001')->firstOrFail())
            ->post(route('doctor.visits.complete', $visit))->assertSessionHasErrors('visit');
        $this->assertSame('WAITING', $visit->fresh()->status->name);
    }

    public function test_pending_service_prevents_completion(): void
    {
        $visit = $this->readyVisit();
        $order = ServiceOrder::firstOrFail()->replicate();
        $order->service_order_code = 'CDDOCTORCOMPLETE';
        $order->medical_visit_id = $visit->id;
        $order->save();
        $item = ServiceOrderItem::firstOrFail()->replicate();
        $item->service_order_id = $order->id;
        $item->service_status_id = ServiceStatus::where('name', 'PENDING')->value('id');
        $item->save();
        $this->get(route('doctor.visits.show', $visit))->assertOk()->assertSee('Còn dịch vụ cần chờ xử lý.')
            ->assertDontSee('>Hoàn tất lượt khám</button>', false);
        $this->post(route('doctor.visits.complete', $visit))->assertSessionHasErrors('visit');
        $this->assertSame('IN_EXAMINATION', $visit->fresh()->status->name);
    }

    public function test_completion_closes_open_queue_and_rolls_back_if_configuration_is_missing(): void
    {
        $visit = $this->readyVisit();
        $queue = $visit->queues()->create([
            'queue_status_id' => QueueStatus::where('name', 'IN_SERVICE')->value('id'),
            'queue_number' => 900, 'queue_type' => 'INITIAL_EXAM', 'queued_at' => now(), 'call_count' => 0,
        ]);
        QueueStatus::where('name', 'COMPLETED')->update(['is_active' => false]);
        $this->post(route('doctor.visits.complete', $visit))->assertSessionHasErrors('visit_status_id');
        $this->assertSame('IN_EXAMINATION', $visit->fresh()->status->name);
        $this->assertSame('IN_SERVICE', $queue->fresh()->status->name);
        $this->assertNull($visit->fresh()->examination_ended_at);

        QueueStatus::where('name', 'COMPLETED')->update(['is_active' => true]);
        $this->post(route('doctor.visits.complete', $visit))->assertSessionHasNoErrors();
        $this->assertSame('COMPLETED', $queue->fresh()->status->name);
        $this->assertNotNull($queue->fresh()->completed_at);
    }

    public function test_missing_start_is_recovered_from_initial_exam_queue(): void
    {
        $visit = $this->readyVisit();
        $visit->update(['examination_started_at' => null]);
        $started = now()->subMinutes(30)->startOfSecond();
        $visit->queues()->create([
            'queue_status_id' => QueueStatus::where('name', 'IN_SERVICE')->value('id'),
            'queue_number' => 901, 'queue_type' => 'INITIAL_EXAM', 'queued_at' => $started->copy()->subMinutes(10),
            'service_started_at' => $started, 'call_count' => 0,
        ]);
        $this->post(route('doctor.visits.complete', $visit))->assertSessionHasNoErrors();
        $this->assertSame($started->toDateTimeString(), $visit->fresh()->examination_started_at->toDateTimeString());
        $this->assertSame('COMPLETED', $visit->fresh()->status->name);
    }

    public function test_completion_rejects_missing_or_future_start_without_changing_data(): void
    {
        $visit = $this->readyVisit();
        $visit->update(['examination_started_at' => null]);
        $this->post(route('doctor.visits.complete', $visit))->assertSessionHasErrors('visit');
        $this->assertSame('IN_EXAMINATION', $visit->fresh()->status->name);
        $this->assertNull($visit->fresh()->examination_ended_at);
        $visit->update(['examination_started_at' => now()->addDay()]);
        $this->post(route('doctor.visits.complete', $visit))->assertSessionHasErrors('visit');
        $this->assertSame('IN_EXAMINATION', $visit->fresh()->status->name);
        $this->assertNull($visit->fresh()->examination_ended_at);
    }
}
