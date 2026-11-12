<?php

namespace Tests\Feature\Doctor;

use App\Models\AuditLog;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\ExaminationRoom;
use App\Models\ExaminationRoomQueue;
use App\Models\MedicalVisit;
use App\Models\PaymentStatus;
use App\Models\QueueStatus;
use App\Models\ServiceOrder;
use App\Models\ServiceStatus;
use App\Models\User;
use App\Models\VisitStatus;
use App\Services\Doctor\ExaminationQueueWorkflow;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class D01ExaminationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 15)->setTime(10, 0));
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $this->user = User::where('business_code', 'BS000001')->firstOrFail();
    }

    public function test_rooms_list_filters_assigned_rooms_and_keeps_the_cursor_query(): void
    {
        $this->allowFixtureDoctor();
        $schedule = DoctorSchedule::where('doctor_id', $this->user->doctor->id)
            ->whereDate('work_date', today())->where('status', 'ACTIVE')->firstOrFail();

        foreach (range(1, 14) as $number) {
            $room = ExaminationRoom::create([
                'room_code' => sprintf('D01-PAGE-%02d', $number), 'name' => 'Phòng kiểm thử D01',
                'specialty_id' => $schedule->specialty_id, 'status' => 'ACTIVE',
            ]);
            if ($number < 14) {
                $schedule->replicate()->fill(['examination_room_id' => $room->id, 'shift_code' => null])->save();
            }
        }

        $filters = ['q' => 'D01-PAGE-', 'specialty' => $schedule->specialty_id];
        $first = $this->get(route('doctor.examination.rooms.index', $filters))->assertOk()
            ->assertSee('D01-PAGE-01')->assertDontSee('D01-PAGE-13')->viewData('rooms');
        $this->assertCount(12, $first);
        $this->assertNotNull($first->nextPageUrl());
        parse_str(parse_url($first->nextPageUrl(), PHP_URL_QUERY), $query);
        $this->assertSame($filters['q'], $query['q']);
        $this->assertSame((string) $filters['specialty'], $query['specialty']);
        $this->get($first->nextPageUrl())->assertOk()->assertSee('D01-PAGE-13')->assertDontSee('D01-PAGE-14');
    }

    public function test_queue_scopes_room_date_shift_and_doctor_and_never_allocates_a_number_on_get(): void
    {
        $this->allowFixtureDoctor();
        $later = $this->queue(19);
        $earlier = $this->queue(17);
        $room = $earlier->medicalVisit->examinationRoom;
        $foreign = $this->queue(18, ['doctor_id' => Doctor::whereKeyNot($this->user->doctor->id)->value('id')]);
        $otherRoom = ExaminationRoom::whereKeyNot($room->id)->firstOrFail();
        $outsideRoom = $this->queue(16, ['examination_room_id' => $otherRoom->id]);
        $yesterday = $this->queue(15, ['checked_in_at' => now()->subDay()]);

        $scheduledVisit = MedicalVisit::where('visit_code', 'LK000001')->firstOrFail();
        $scheduledVisit->appointment->appointmentTimeSlot->doctorSchedule->update(['shift_code' => 'MORNING']);
        $path = route('doctor.examination.rooms.queue', $room);
        $before = [MedicalVisit::count(), ExaminationRoomQueue::count()];
        $this->get($path)->assertOk()->assertSeeInOrder([$earlier->medicalVisit->visit_code, $later->medicalVisit->visit_code])
            ->assertDontSee($foreign->medicalVisit->visit_code)->assertDontSee($outsideRoom->medicalVisit->visit_code)
            ->assertDontSee($yesterday->medicalVisit->visit_code);
        $this->get($path.'?q='.$later->medicalVisit->visit_code)->assertOk()
            ->assertSee($later->medicalVisit->visit_code)->assertDontSee($earlier->medicalVisit->visit_code);
        $this->get($path.'?shift=MORNING')->assertOk()->assertSee($scheduledVisit->visit_code)
            ->assertDontSee($earlier->medicalVisit->visit_code);
        $this->get($path.'?shift=AFTERNOON')->assertOk()->assertDontSee($scheduledVisit->visit_code);
        $this->get($path.'?date='.now()->subDay()->toDateString())->assertNotFound();
        $this->get(route('doctor.examination.rooms.queue', $otherRoom))->assertNotFound();
        $this->assertSame($before, [MedicalVisit::count(), ExaminationRoomQueue::count()]);
    }

    public function test_doctor_without_gate_and_employee_cannot_call_via_doctor_route(): void
    {
        $queue = $this->queue(17);
        $url = route('doctor.examination.queue.call', [$queue->medicalVisit->examination_room_id, $queue]);
        $this->actingAs($this->user)->postJson($url, $this->snapshot($queue))->assertForbidden();
        $this->flushSession();
        $this->actingAs(User::whereHas('role', fn ($role) => $role->where('name', 'Employee'))->firstOrFail())
            ->postJson($url, $this->snapshot($queue))->assertForbidden();
        $this->assertSame(0, $queue->fresh()->call_count);
    }

    public function test_call_recall_and_skip_reject_stale_requests_and_unpaid_fees(): void
    {
        $this->allowFixtureDoctor();
        $queue = $this->queue(17);
        $visit = $queue->medicalVisit;
        $parameters = [$visit->examination_room_id, $queue];
        $call = route('doctor.examination.queue.call', $parameters);
        $recall = route('doctor.examination.queue.recall', $parameters);
        $skip = route('doctor.examination.queue.skip', $parameters);
        $waiting = $this->snapshot($queue);
        $fee = $visit->payables()->create([
            'payable_code' => 'PT-D01-TEST', 'payment_type' => 'EXAMINATION_FEE',
            'description' => 'Phí khám kiểm thử', 'amount' => 300000, 'generated_at' => now(),
            'payment_status_id' => PaymentStatus::where('name', 'UNPAID')->value('id'),
        ]);

        $this->postJson($call, $waiting)->assertUnprocessable()->assertJsonValidationErrors('visit');
        $this->assertSame(0, $queue->fresh()->call_count);
        $fee->update(['payment_status_id' => PaymentStatus::where('name', 'PAID')->value('id')]);
        $this->post($call, $waiting)->assertSessionHasNoErrors()->assertRedirect();
        $this->postJson($call, $waiting)->assertUnprocessable();
        $called = $this->snapshot($queue->fresh());
        $this->post($recall, $called)->assertSessionHasNoErrors()->assertRedirect();
        $this->postJson($recall, $called)->assertUnprocessable();
        $calledAgain = $this->snapshot($queue->fresh());
        $this->post($skip, $calledAgain)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('SKIPPED', $queue->fresh()->status->name);
        $this->assertSame(2, $queue->fresh()->call_count);
        $this->assertSame('WAITING', $visit->fresh()->status->name);
        $this->assertSame(3, AuditLog::where('entity_type', 'examination_room_queues')->where('entity_id', $queue->id)->count());
    }

    public function test_start_waits_for_d02_and_then_redirects_with_the_visit_id_once(): void
    {
        $this->allowFixtureDoctor();
        $queue = $this->queue(17, queueStatus: 'CALLED');
        $visit = $queue->medicalVisit;
        $url = route('doctor.examination.visits.start', [$visit->examination_room_id, $queue]);
        $payload = $this->snapshot($queue);
        $this->get(route('doctor.examination.rooms.queue', $visit->examination_room_id))->assertOk()
            ->assertDontSee('commandfor="start-'.$queue->id.'"', false);
        $this->postJson($url, $payload)->assertConflict();
        $this->assertNull($visit->fresh()->examination_started_at);

        $this->registerD02FixtureDestination();
        $before = MedicalVisit::count();
        $this->post($url, $payload)->assertRedirect('/bac-si/kham-benh/luot-kham/'.$visit->id);
        $this->postJson($url, $payload)->assertUnprocessable();
        $this->assertSame('IN_EXAMINATION', $visit->fresh()->status->name);
        $this->assertSame('IN_SERVICE', $queue->fresh()->status->name);
        $this->assertSame(17, $visit->fresh()->issuedQueueNumber());
        $this->assertSame($before, MedicalVisit::count());
        $this->assertSame(1, $visit->queues()->count());
    }

    public function test_service_return_rejects_the_old_phase_and_waits_until_service_is_resolved(): void
    {
        $this->allowFixtureDoctor();
        $queue = $this->queue(17);
        $visit = $queue->medicalVisit;
        $previousPhase = $this->snapshot($queue);
        $startedAt = now()->subMinutes(10);
        $visit->update([
            'examination_started_at' => $startedAt,
            'visit_status_id' => VisitStatus::where('name', 'WAITING_FOR_SERVICE')->value('id'),
        ]);
        $queue->update(['queue_type' => 'RETURN_AFTER_SERVICE']);
        $source = ServiceOrder::whereHas('items')->firstOrFail();
        $order = $source->replicate();
        $order->fill(['service_order_code' => 'CD-D01-TEST', 'medical_visit_id' => $visit->id, 'doctor_id' => $visit->doctor_id, 'status' => 'ACTIVE'])->save();
        $item = $source->items()->firstOrFail()->replicate();
        $item->fill(['service_status_id' => ServiceStatus::where('name', 'PENDING')->value('id'), 'started_at' => null, 'completed_at' => null]);
        $order->items()->save($item);

        $parameters = [$visit->examination_room_id, $queue];
        $call = route('doctor.examination.queue.call', $parameters);
        $this->postJson($call, $previousPhase)->assertUnprocessable()->assertJsonValidationErrors('queue');
        $this->postJson($call, $this->snapshot($queue->fresh()))->assertUnprocessable()->assertJsonValidationErrors('queue');
        $this->assertSame(0, $queue->fresh()->call_count);
        $item->update(['service_status_id' => ServiceStatus::where('name', 'CANCELLED')->value('id')]);
        $this->post($call, $this->snapshot($queue->fresh()))->assertSessionHasNoErrors()->assertRedirect();
        $this->registerD02FixtureDestination();
        $this->post(route('doctor.examination.visits.start', $parameters), $this->snapshot($queue->fresh()))
            ->assertRedirect('/bac-si/kham-benh/luot-kham/'.$visit->id);
        $this->assertSame('RETURNING', $visit->fresh()->status->name);
        $this->assertSame($startedAt->toDateTimeString(), $visit->fresh()->examination_started_at->toDateTimeString());
        $this->assertSame('IN_SERVICE', $queue->fresh()->status->name);
        $this->assertSame(1, $visit->queues()->count());
    }

    public function test_completion_stays_blocked_without_the_huy_contract_and_writes_nothing(): void
    {
        $this->allowFixtureDoctor();
        $queue = $this->queue(17, [
            'examination_started_at' => now()->subMinute(),
            'visit_status_id' => VisitStatus::where('name', 'IN_EXAMINATION')->value('id'),
        ], 'IN_SERVICE');
        $visit = $queue->medicalVisit;
        $this->get(route('doctor.examination.rooms.queue', $visit->examination_room_id))->assertOk()
            ->assertDontSee('commandfor="complete-'.$queue->id.'"', false);
        $before = AuditLog::count();
        $this->postJson(route('doctor.examination.visits.complete', [$visit->examination_room_id, $visit]), ['confirm' => 1])->assertConflict();
        $this->assertSame('IN_EXAMINATION', $visit->fresh()->status->name);
        $this->assertNull($visit->fresh()->examination_ended_at);
        $this->assertSame('IN_SERVICE', $queue->fresh()->status->name);
        $this->assertSame($before, AuditLog::count());
    }

    private function allowFixtureDoctor(): void
    {
        Gate::define('doctor.examination', fn (User $user, Doctor $doctor) =>
            $user->is($this->user) && (int) $doctor->user_id === (int) $user->id);
        $this->actingAs($this->user);
    }

    private function registerD02FixtureDestination(): void
    {
        // Replace the earlier action only in this test to exercise the D01 handoff.
        Route::get('/bac-si/kham-benh/luot-kham/{visit}', fn (int $visit) => (string) $visit)->name('doctor.examination.visits.show');
        Route::getRoutes()->refreshNameLookups();
    }

    private function queue(int $number, array $attributes = [], string $queueStatus = 'WAITING'): ExaminationRoomQueue
    {
        $visit = MedicalVisit::where('visit_code', 'LK000001')->firstOrFail()->replicate();
        $visit->fill(array_replace([
            'visit_code' => 'LK-D01-TEST-'.$number, 'appointment_id' => null, 'doctor_id' => $this->user->doctor->id,
            'checked_in_at' => now(), 'reception_source' => 'WALK_IN', 'reception_queue_number' => $number,
            'visit_status_id' => VisitStatus::where('name', 'WAITING')->value('id'),
            'examination_started_at' => null, 'examination_ended_at' => null,
        ], $attributes))->save();

        return $visit->queues()->create([
            'queue_number' => $number, 'queue_type' => 'INITIAL_EXAM', 'queued_at' => now(),
            'queue_status_id' => QueueStatus::where('name', $queueStatus)->value('id'),
            'call_count' => $queueStatus === 'WAITING' ? 0 : 1,
        ]);
    }

    private function snapshot(ExaminationRoomQueue $queue): array
    {
        return ['confirm' => 1, 'expected_status' => $queue->status->name, 'expected_calls' => $queue->call_count,
            'expected_phase' => app(ExaminationQueueWorkflow::class)->phaseToken($queue)];
    }
}
