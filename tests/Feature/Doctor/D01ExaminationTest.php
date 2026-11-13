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
use Illuminate\Support\Facades\Event;
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
        $this->get($path)->assertOk()->assertViewHas('queues', fn ($queues) => $queues->getCollection()->whereIn('id', [$earlier->id, $later->id])->pluck('id')->all() === [$earlier->id, $later->id])
            ->assertDontSee($foreign->medicalVisit->visit_code)->assertDontSee($outsideRoom->medicalVisit->visit_code)
            ->assertDontSee($yesterday->medicalVisit->visit_code);
        $this->get($path.'?q='.$later->medicalVisit->visit_code)->assertOk()->assertViewHas('queues', fn ($queues) => $queues->isEmpty());
        $this->get($path.'?shift=MORNING')->assertOk()
            ->assertViewHas('queues', fn ($queues) => $queues->pluck('medical_visit_id')->contains($scheduledVisit->id)
                && ! $queues->pluck('medical_visit_id')->contains($earlier->medical_visit_id));
        $this->get($path.'?shift=AFTERNOON')->assertOk()->assertViewHas('queues', fn ($queues) => $queues->isEmpty());
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

    public function test_queue_search_uses_only_patient_name_and_phone_inside_assignment_scope(): void
    {
        $this->allowFixtureDoctor();
        $entry = $this->queue(17);
        $visit = $entry->medicalVisit;
        $patient = $visit->patient;
        $patient->update(['full_name' => 'D01 Patient Search']);
        $patient->user->update(['phone' => '0981234567']);
        $otherRoom = ExaminationRoom::whereKeyNot($visit->examination_room_id)->firstOrFail();
        $outside = $this->queue(18, ['examination_room_id' => $otherRoom->id]);

        foreach (['patient search', '123456'] as $term) {
            $this->get(route('doctor.examination.rooms.queue', ['room' => $visit->examination_room_id, 'q' => $term]))
                ->assertOk()->assertSee('D01 Patient Search')->assertDontSee($visit->visit_code)
                ->assertViewHas('queues', fn ($queues) => $queues->pluck('id')->contains($entry->id)
                    && ! $queues->pluck('id')->contains($outside->id));
        }
        foreach ([$patient->patient_code, $visit->visit_code] as $term) {
            $this->get(route('doctor.examination.rooms.queue', ['room' => $visit->examination_room_id, 'q' => $term]))
                ->assertOk()->assertViewHas('queues', fn ($queues) => $queues->isEmpty());
        }
    }

    public function test_queue_stage_filters_match_the_badges_and_use_visit_completion(): void
    {
        $this->allowFixtureDoctor();
        $started = ['examination_started_at' => now()->subMinutes(30)];
        $status = fn ($name) => VisitStatus::where('name', $name)->value('id');
        $waiting = $this->queue(17);
        $called = $this->queue(18, queueStatus: 'CALLED');
        $examining = $this->queue(19, $started + ['visit_status_id' => $status('IN_EXAMINATION')], 'IN_SERVICE');
        $service = $this->queue(20, $started + ['visit_status_id' => $status('WAITING_FOR_SERVICE')], 'COMPLETED');
        $return = $this->queue(21, $started + ['visit_status_id' => $status('WAITING_FOR_SERVICE')]);
        $return->update(['queue_type' => 'RETURN_AFTER_SERVICE']);
        $completed = $this->queue(22, $started + ['visit_status_id' => $status('COMPLETED'), 'examination_ended_at' => now()], 'COMPLETED');
        $source = ServiceOrder::whereHas('items')->firstOrFail();
        $order = $source->replicate();
        $order->fill(['service_order_code' => 'CD-D01-STAGE', 'medical_visit_id' => $return->medical_visit_id, 'status' => 'ACTIVE'])->save();
        $item = $source->items()->firstOrFail()->replicate();
        $item->fill(['service_status_id' => ServiceStatus::where('name', 'CANCELLED')->value('id'), 'started_at' => null, 'completed_at' => null]);
        $order->items()->save($item);
        $this->assertNull($service->medicalVisit->examination_ended_at);

        $entries = compact('waiting', 'called', 'examining', 'service', 'return', 'completed');
        $stages = array_combine(['WAITING', 'CALLED', 'IN_EXAMINATION', 'WAITING_FOR_SERVICE', 'RETURNING', 'COMPLETED'], $entries);
        $room = $waiting->medicalVisit->examination_room_id;
        foreach ($stages as $stage => $entry) {
            $this->get(route('doctor.examination.rooms.queue', ['room' => $room, 'status' => $stage]))
                ->assertOk()->assertSee('d01-status--'.$stage, false)
                ->assertViewHas('queues', fn ($queues) => $queues->getCollection()->whereIn('id', collect($entries)->pluck('id'))->pluck('id')->all() === [$entry->id]);
        }
        $this->postJson(route('doctor.examination.queue.recall', [$room, $completed]), $this->snapshot($called))
            ->assertUnprocessable();
        $this->get(route('doctor.examination.rooms.queue', ['room' => $room, 'status' => 'COMPLETED']))
            ->assertDontSee('Xem chi tiết');
        $this->registerD02FixtureDestination();
        $this->get(route('doctor.examination.rooms.queue', ['room' => $room, 'status' => 'COMPLETED']))
            ->assertSee('Xem chi tiết')->assertSee(route('doctor.examination.visits.show', $completed->medical_visit_id));
        $this->get(route('doctor.examination.rooms.queue', ['room' => $room, 'status' => 'IN_EXAMINATION']))
            ->assertDontSee('Xem chi tiết');
    }

    public function test_doctor_work_status_uses_each_shift_and_server_time(): void
    {
        $this->allowFixtureDoctor();
        $schedule = DoctorSchedule::where('doctor_id', $this->user->doctor->id)
            ->whereDate('work_date', today())->where('status', 'ACTIVE')->firstOrFail();
        $schedule->update(['start_time' => '07:30:00', 'end_time' => '11:30:00', 'shift_code' => 'MORNING']);
        $schedule->replicate()->fill(['start_time' => '13:30:00', 'end_time' => '17:00:00', 'shift_code' => 'AFTERNOON'])->save();
        $rooms = route('doctor.examination.rooms.index');
        foreach ([[7, 29, 'Ngoài giờ làm việc'], [7, 30, 'Đang hoạt động'], [12, 0, 'Ngoài giờ làm việc'],
            [13, 30, 'Đang hoạt động'], [17, 0, 'Ngoài giờ làm việc']] as [$hour, $minute, $label]) {
            $this->travelTo(now()->setTime($hour, $minute));
            $this->get($rooms)->assertOk()->assertViewHas('workStatus', $label);
        }
        $this->travelTo(now()->subDay());
        $this->get($rooms)->assertOk()->assertViewHas('workStatus', 'Không có lịch làm việc');
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
        $this->post($call, $waiting)->assertSessionHasNoErrors()->assertRedirect()
            ->assertSessionHas('success', 'Đã gọi bệnh nhân thành công');
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

    public function test_call_requires_csrf_and_rejects_a_queue_from_another_room(): void
    {
        $this->allowFixtureDoctor();
        $queue = $this->queue(17);
        $room = $queue->medicalVisit->examination_room_id;
        $url = route('doctor.examination.queue.call', [$room, $queue]);
        $payload = $this->snapshot($queue);
        $token = 'd01-csrf-fixture';
        $this->app->instance('env', 'local');
        try {
            $this->withSession(['_token' => $token])->postJson($url, $payload)->assertStatus(419);
            $otherRoom = ExaminationRoom::whereKeyNot($room)->firstOrFail();
            $this->postJson(route('doctor.examination.queue.call', [$otherRoom, $queue]), $payload + ['_token' => $token])
                ->assertNotFound();
            $this->assertSame(0, $queue->fresh()->call_count);
            $this->post($url, $payload + ['_token' => $token])->assertRedirect()
                ->assertSessionHas('success', 'Đã gọi bệnh nhân thành công');
            $this->assertSame('CALLED', $queue->fresh()->status->name);
        } finally {
            $this->app->instance('env', 'testing');
        }
    }

    public function test_start_waits_for_d02_and_then_redirects_with_the_visit_id_once(): void
    {
        $this->allowFixtureDoctor();
        $queue = $this->queue(17, queueStatus: 'CALLED');
        $visit = $queue->medicalVisit;
        $url = route('doctor.examination.visits.start', [$visit->examination_room_id, $queue]);
        $payload = $this->snapshot($queue);
        $this->get(route('doctor.examination.rooms.queue', $visit->examination_room_id))->assertOk()
            ->assertDontSee('data-d01-dialog="start-'.$queue->id.'"', false);
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
            ->assertDontSee('data-d01-dialog="complete-'.$queue->id.'"', false);
        $before = AuditLog::count();
        $this->postJson(route('doctor.examination.visits.complete', [$visit->examination_room_id, $visit]), ['confirm' => 1])->assertConflict();
        $this->assertSame('IN_EXAMINATION', $visit->fresh()->status->name);
        $this->assertNull($visit->fresh()->examination_ended_at);
        $this->assertSame('IN_SERVICE', $queue->fresh()->status->name);
        $this->assertSame($before, AuditLog::count());
    }

    private function allowFixtureDoctor(): void
    {
        $doctorId = $this->user->doctor()->firstOrFail()->getKey();

        // The test schema has no doctor_type column. Supply it only for this fixture.
        Event::listen('eloquent.retrieved: '.Doctor::class, static function (Doctor $doctor) use ($doctorId): void {
            if ($doctor->getKey() === $doctorId) {
                $doctor->setAttribute('doctor_type', 'EXAMINATION');
            }
        });
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
