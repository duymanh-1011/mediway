<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentStatus;
use App\Models\AppointmentTimeSlot;
use App\Models\AuditLog;
use App\Models\Disease;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\ExaminationRoom;
use App\Models\ExaminationRoomQueue;
use App\Models\MedicalService;
use App\Models\MedicalVisit;
use App\Models\Patient;
use App\Models\QueueStatus;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderProgress;
use App\Models\ServiceResult;
use App\Models\Specialty;
use App\Models\User;
use App\Models\VisitStatus;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FinalAuditRepairsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 23)->setTime(7, 0));
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail());
    }

    private function appointment(int $days = 0): Appointment
    {
        $schedule = DoctorSchedule::create([
            'doctor_id' => Doctor::where('doctor_code', 'BS000001')->value('id'),
            'specialty_id' => Specialty::where('specialty_code', 'CKTMH')->value('id'),
            'examination_room_id' => ExaminationRoom::where('room_code', 'PKTMH01')->value('id'),
            'work_date' => today()->addDays($days)->toDateString(),
            'start_time' => '09:00:00', 'end_time' => '11:00:00', 'status' => 'ACTIVE',
        ]);
        $slot = $schedule->appointmentTimeSlots()->create([
            'start_time' => '09:00:00', 'end_time' => '09:30:00', 'max_patients' => 10, 'status' => 'ACTIVE',
        ]);
        $this->post(route('admin.appointments.store'), [
            'patient_id' => Patient::where('patient_code', 'BN000005')->value('id'),
            'appointment_time_slot_id' => $slot->id, 'reason_for_visit' => 'Kiểm thử hồi quy',
        ])->assertSessionHasNoErrors()->assertSessionMissing('error');

        return Appointment::where('appointment_time_slot_id', $slot->id)->sole();
    }

    private function receive(): MedicalVisit
    {
        $appointment = $this->appointment();
        $this->post(route('admin.appointments.check-in', $appointment))->assertSessionHasNoErrors()->assertSessionMissing('error');

        return $appointment->medicalVisit()->sole();
    }

    private function begin(MedicalVisit $visit): ExaminationRoomQueue
    {
        $queue = $visit->queues()->whereHas('status', fn ($q) => $q->where('name', 'WAITING'))->orderByDesc('id')->firstOrFail();
        $this->post(route('admin.queues.call', $queue))->assertSessionHasNoErrors()->assertSessionMissing('error');
        $this->post(route('admin.queues.start-service', $queue))->assertSessionHasNoErrors()->assertSessionMissing('error');

        return $queue->fresh();
    }

    private function order(MedicalVisit $visit): ServiceOrder
    {
        $this->post(route('admin.visits.service-orders.store', $visit), [
            'medical_service_ids' => [MedicalService::where('service_code', 'DVXN001')->value('id')],
        ])->assertSessionHasNoErrors()->assertSessionMissing('error');

        return $visit->serviceOrders()->orderByDesc('id')->firstOrFail();
    }

    private function finish(ServiceOrder $order): void
    {
        $item = $order->items()->sole();
        $this->post(route('admin.payables.payment-transactions.store', $item->payable), ['payment_method' => 'CASH', 'confirm_payment' => 1])->assertSessionHasNoErrors()->assertSessionMissing('error');
        $this->post(route('admin.service-order-items.start', $item))->assertSessionHasNoErrors()->assertSessionMissing('error');
        $this->post(route('admin.service-order-items.service-results.store', $item), ['result_summary' => 'Kết quả kiểm thử', 'confirm_create' => 1])->assertSessionHasNoErrors()->assertSessionMissing('error');
        $this->post(route('admin.service-order-items.complete', $item))->assertSessionHasNoErrors()->assertSessionMissing('error');
    }

    public function test_multiple_service_rounds_and_a_cancelled_round_each_get_one_new_return_queue(): void
    {
        $visit = $this->receive();
        $queue = $this->begin($visit);
        for ($round = 1; $round <= 3; $round++) {
            $order = $this->order($visit);
            $this->assertSame('COMPLETED', $queue->fresh()->status->name);
            if ($round === 3) {
                $this->patch(route('admin.payables.cancel', $order->items()->sole()->payable), [
                    'confirm_cancel' => 1, 'cancel_reason' => 'Không cần thực hiện thêm',
                ])->assertSessionHasNoErrors()->assertSessionMissing('error');
            } else {
                $this->finish($order);
            }
            app(ServiceOrderProgress::class)->synchronize($order->id);
            app(ServiceOrderProgress::class)->synchronize($order->id);
            $this->assertSame($round, $visit->queues()->where('queue_type', 'RETURN_AFTER_SERVICE')->count());
            $this->assertSame(1, $visit->queues()->whereHas('status', fn ($q) => $q->where('name', 'WAITING'))->count());
            $queue = $this->begin($visit);
            $this->assertSame('RETURNING', $visit->fresh()->status->name);
        }
    }

    public function test_old_serving_queue_is_not_mistaken_for_a_new_return(): void
    {
        $visit = $this->receive();
        $this->begin($visit);
        $order = $this->order($visit);
        $old = $visit->queues()->create([
            'queue_status_id' => QueueStatus::where('name', 'IN_SERVICE')->value('id'),
            'queue_number' => 99, 'queue_type' => 'RETURN_AFTER_SERVICE', 'queued_at' => now()->subHour(),
            'service_started_at' => now()->subMinutes(30), 'call_count' => 1,
        ]);
        $this->finish($order);
        $this->assertSame('COMPLETED', $old->fresh()->status->name);
        $this->assertSame(1, $visit->queues()->where('queue_type', 'RETURN_AFTER_SERVICE')->whereHas('status', fn ($q) => $q->where('name', 'WAITING'))->count());
    }

    public function test_inactive_reception_dependencies_do_not_create_partial_visits_or_audit_logs(): void
    {
        foreach (['schedule', 'slot', 'doctor', 'patient', 'specialty', 'room'] as $kind) {
            $appointment = $this->appointment();
            $slot = $appointment->appointmentTimeSlot;
            $record = match ($kind) {
                'schedule' => $slot->doctorSchedule, 'slot' => $slot, 'doctor' => $appointment->doctor,
                'patient' => $appointment->patient, 'specialty' => $appointment->specialty, 'room' => $appointment->examinationRoom,
            };
            $record->update(['status' => 'INACTIVE']);
            $logs = AuditLog::count();
            $queues = ExaminationRoomQueue::count();
            $this->post(route('admin.appointments.check-in', $appointment))->assertSessionHasErrors('appointment');
            $this->assertFalse($appointment->medicalVisit()->exists(), $kind);
            $this->assertSame($queues, ExaminationRoomQueue::count());
            $this->assertSame($logs, AuditLog::count());
            $this->assertSame('BOOKED', $appointment->fresh()->appointmentStatus->name);
            $record->update(['status' => 'ACTIVE']);
        }
    }

    public function test_frozen_visit_state_blocks_queue_start_atomically(): void
    {
        $visit = $this->receive();
        $queue = $visit->queues()->sole();
        $this->post(route('admin.queues.call', $queue))->assertSessionHasNoErrors();
        VisitStatus::where('name', 'WAITING')->update(['allows_transition' => false]);
        $logs = AuditLog::count();
        $this->post(route('admin.queues.start-service', $queue))->assertSessionHasErrors('visit_status_id');
        $this->assertSame('WAITING', $visit->fresh()->status->name);
        $this->assertSame('CALLED', $queue->fresh()->status->name);
        $this->assertSame($logs, AuditLog::count());
        $this->assertNull($visit->fresh()->examination_started_at);
    }

    public function test_missing_appointment_target_rolls_back_both_start_paths(): void
    {
        foreach (['direct', 'queue'] as $path) {
            $visit = $this->receive();
            $queue = $visit->queues()->sole();
            $this->post(route('admin.queues.call', $queue))->assertSessionHasNoErrors();
            AppointmentStatus::where('name', 'IN_EXAMINATION')->update(['is_active' => false]);
            $logs = AuditLog::count();
            if ($path === 'direct') {
                $response = $this->patch(route('admin.visits.update-status', $visit), ['visit_status_id' => VisitStatus::where('name', 'IN_EXAMINATION')->value('id')]);
            } else {
                $response = $this->post(route('admin.queues.start-service', $queue));
            }
            $response->assertSessionHasErrors('visit_status_id');
            $this->assertSame('WAITING', $visit->fresh()->status->name);
            $this->assertSame('CALLED', $queue->fresh()->status->name);
            $this->assertSame('CHECKED_IN', $visit->appointment->fresh()->appointmentStatus->name);
            $this->assertSame($logs, AuditLog::count());
            AppointmentStatus::where('name', 'IN_EXAMINATION')->update(['is_active' => true]);
        }
    }

    public function test_direct_start_synchronizes_queue_and_appointment_without_inventing_a_call(): void
    {
        $visit = $this->receive();
        $this->patch(route('admin.visits.update-status', $visit), ['visit_status_id' => VisitStatus::where('name', 'IN_EXAMINATION')->value('id')])
            ->assertSessionHasNoErrors()->assertSessionMissing('error');
        $queue = $visit->queues()->sole();
        $this->assertSame('IN_SERVICE', $queue->status->name);
        $this->assertNotNull($queue->service_started_at);
        $this->assertSame(0, $queue->call_count);
        $this->assertSame('IN_EXAMINATION', $visit->appointment->fresh()->appointmentStatus->name);
    }

    public function test_calendar_starts_at_the_first_day_in_short_months(): void
    {
        foreach ([['2026-10-31', '2026-11'], ['2026-01-31', '2026-02'], ['2026-03-30', '2026-02'], ['2028-01-29', '2028-02']] as [$today, $month]) {
            $this->travelTo(Carbon::parse($today.' 09:00'));
            $this->get(route('admin.doctor-schedules.calendar', ['month' => $month]))->assertOk()
                ->assertViewHas('currentMonth', fn ($date) => $date->format('Y-m-d') === $month.'-01');
        }
    }

    public function test_legacy_status_migration_preserves_records_and_inactive_flags(): void
    {
        $this->assertSame(0, DoctorSchedule::where('status', '!=', 'ACTIVE')->count());
        $this->assertSame(0, AppointmentTimeSlot::where('status', '!=', 'ACTIVE')->count());
        $schedule = DoctorSchedule::firstOrFail();
        $slots = $schedule->appointmentTimeSlots()->get();
        DB::table('doctor_schedules')->where('id', $schedule->id)->update(['status' => 'OPEN']);
        DB::table('appointment_time_slots')->where('id', $slots[0]->id)->update(['status' => 'AVAILABLE']);
        DB::table('appointment_time_slots')->where('id', $slots[1]->id)->update(['status' => 'INACTIVE']);
        $before = $schedule->getAttributes();
        $migration = require database_path('migrations/2026_10_23_160000_normalize_legacy_schedule_statuses.php');
        $migration->up();
        $migration->up();
        $this->assertSame($before, $schedule->fresh()->getAttributes());
        $this->assertSame('ACTIVE', $slots[0]->fresh()->status);
        $this->assertSame('INACTIVE', $slots[1]->fresh()->status);
        $migration->down();
        $this->assertSame('ACTIVE', $slots[0]->fresh()->status);
    }

    public function test_future_legacy_visit_cannot_create_an_order_or_fees(): void
    {
        $a = $this->appointment(1);
        $visit = MedicalVisit::create([
            'visit_code' => 'LKFUTUREAUDIT', 'appointment_id' => $a->id, 'patient_id' => $a->patient_id,
            'doctor_id' => $a->doctor_id, 'specialty_id' => $a->specialty_id, 'examination_room_id' => $a->examination_room_id,
            'visit_status_id' => VisitStatus::where('name', 'IN_EXAMINATION')->value('id'), 'checked_in_at' => now(), 'reception_source' => 'COUNTER',
        ]);
        $before = DB::table('payables')->count();
        $this->post(route('admin.visits.service-orders.store', $visit), ['medical_service_ids' => [MedicalService::where('service_code', 'DVXN001')->value('id')]])->assertSessionHasErrors('visit');
        $this->assertSame(0, $visit->serviceOrders()->count());
        $this->assertSame($before, DB::table('payables')->count());
    }

    public function test_clinical_changes_have_before_after_actor_and_queue_audit_records(): void
    {
        $visit = $this->receive();
        $this->begin($visit);
        $this->post(route('admin.visits.diagnoses.store', $visit), ['disease_id' => Disease::where('disease_code', 'J01.9')->value('id'), 'diagnosis_type' => 'PRIMARY'])->assertSessionHasNoErrors();
        $diagnosis = $visit->diagnoses()->sole();
        $log = AuditLog::where('entity_type', 'visit_diagnoses')->where('entity_id', $diagnosis->id)->where('action', 'CREATE')->sole();
        $this->assertSame(auth()->id(), $log->user_id);
        $this->assertSame('PRIMARY', $log->new_values['diagnosis_type']);
        $this->assertSame($visit->visit_code, $log->entity_business_code);
        $this->assertDatabaseHas('audit_logs', ['entity_type' => 'medical_visits', 'entity_id' => $visit->id, 'action' => 'STATUS_CHANGE']);
        $this->assertDatabaseHas('audit_logs', ['entity_type' => 'examination_room_queues', 'entity_id' => $visit->queues()->sole()->id, 'action' => 'STATUS_CHANGE']);
        $this->get(route('admin.audit-logs.show', $log))->assertOk()->assertSee('Chẩn đoán chính');
    }

    public function test_closed_visit_result_is_read_only_in_ui_backend_and_upload_storage(): void
    {
        Storage::fake('local');
        $result = ServiceResult::whereHas('serviceOrderItem.serviceOrder.medicalVisit.status', fn ($q) => $q->where('name', 'COMPLETED'))->firstOrFail();
        $before = $result->getAttributes();
        $count = AuditLog::count();
        $this->get(route('admin.service-results.show', $result))->assertOk()->assertDontSee(route('admin.service-results.edit', $result));
        $this->get(route('admin.service-results.edit', $result))->assertForbidden();
        $this->patch(route('admin.service-results.update', $result), [
            'result_summary' => 'Không được thay đổi', 'confirm_update' => 1,
            'result_pdf' => UploadedFile::fake()->createWithContent('new.pdf', "%PDF-1.4\n%%EOF"),
        ])->assertSessionHasErrors('result');
        $this->assertSame($before, $result->fresh()->getAttributes());
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertSame($count, AuditLog::count());
    }

    public function test_edit_forms_have_shared_top_back_and_cancel_before_update(): void
    {
        foreach (['clinical-notes' => 'visit_clinical_notes', 'diagnoses' => 'visit_diagnoses', 'conclusions' => 'visit_conclusions', 'visit-statuses' => 'visit_statuses', 'queue-statuses' => 'queue_statuses'] as $module => $table) {
            $id = DB::table($table)->value('id');
            if (in_array($module, ['clinical-notes', 'diagnoses', 'conclusions'], true)) {
                $record = DB::table($table)->where('id', $id)->first();
                DB::table('medical_visits')->where('id', $record->medical_visit_id)->update(['visit_status_id' => VisitStatus::where('name', 'IN_EXAMINATION')->value('id')]);
            }
            $html = $this->get(route('admin.'.$module.'.edit', $id))->assertOk()->getContent();
            $doc = new DOMDocument;
            $previous = libxml_use_internal_errors(true);
            $doc->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET);
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
            $xpath = new DOMXPath($doc);
            $this->assertCount(1, $xpath->query('//main//div[contains(@class,"admin-page-header")]//a[normalize-space(.)="← Quay lại"]'), $module);
            $this->assertCount(1, $xpath->query('//main//form//a[normalize-space(.)="Hủy"]/following-sibling::button[normalize-space(.)="Cập nhật"]'), $module);
        }
        $visit = MedicalVisit::where('visit_code', 'LK000004')->firstOrFail();
        $this->get(route('admin.visits.show', $visit))->assertOk()->assertSee('data-status-context="service"', false)->assertSee('Chờ thực hiện');
    }
}
