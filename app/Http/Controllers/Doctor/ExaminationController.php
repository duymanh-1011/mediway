<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\ExaminationRoom;
use App\Models\ExaminationRoomQueue;
use App\Models\MedicalVisit;
use App\Models\QueueStatus;
use App\Models\ServiceOrderItem;
use App\Models\Specialty;
use App\Services\Doctor\ExaminationQueueWorkflow;
use App\Services\Doctor\ExaminationVisitCompletion;
use App\Support\Doctor\DoctorAccess;
use App\Support\SessionSearch;
use Closure;
use Illuminate\Database\ConcurrencyErrorDetector;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\Cursor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ExaminationController extends Controller
{
    private const VISIT_DETAILS_ROUTE = 'doctor.examination.visits.show';

    private const QUEUE_TYPES = ['INITIAL_EXAM', 'RETURN_AFTER_SERVICE'];

    // Sáu trạng thái hiển thị và lọc của D01.
    private const EXAMINATION_STATUS_LABELS = [
        'WAITING' => 'Chờ khám',
        'CALLED' => 'Đã gọi',
        'IN_EXAMINATION' => 'Đang khám',
        'WAITING_FOR_SERVICE' => 'Chờ thực hiện dịch vụ',
        'RETURNING' => 'Chờ đọc kết quả',
        'COMPLETED' => 'Hoàn tất',
    ];

    private const ACTIONS = [
        'call' => [
            'WAITING' => 'CALLED',
        ],
        'recall' => [
            'CALLED' => 'CALLED',
            'SKIPPED' => 'CALLED',
        ],
        'skip' => [
            'WAITING' => 'SKIPPED',
            'CALLED' => 'SKIPPED',
        ],
        'start' => [
            'CALLED' => 'IN_SERVICE',
        ],
    ];

    public function rooms(Request $request): View
    {
        $doctor = $request->attributes->get('current_doctor');

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'specialty' => ['nullable', 'integer', 'exists:specialties,id'],
        ]);

        $cursor = $request->query('cursor');

        if ($cursor !== null) {
            $parameters = is_string($cursor)
                ? json_decode(
                    base64_decode(
                        strtr($cursor, '-_', '+/'),
                        true
                    ) ?: '',
                    true
                )
                : null;

            abort_unless(
                is_string($parameters['room_code'] ?? null)
                    && is_bool($parameters['_pointsToNextItems'] ?? null),
                422,
                'Mốc phân trang không hợp lệ.'
            );

            $cursor = new Cursor(
                ['room_code' => $parameters['room_code']],
                $parameters['_pointsToNextItems']
            );
        }

        $now = now('Asia/Ho_Chi_Minh');
        $date = $now->toDateString();

        $assignedToday = DoctorSchedule::query()
            ->where('doctor_id', $doctor->id)
            ->whereDate('work_date', $date)
            ->where('status', 'ACTIVE');

        $rooms = ExaminationRoom::query()
            ->with('specialty')
            ->where('status', 'ACTIVE')
            ->addSelect([
                'waiting_count' => $this->waitingCount($date),
            ])
            ->when($filters['q'] ?? null, fn ($query, $term) => $query->where(fn ($room) => $room
                ->where('room_code', 'like', '%'.$term.'%')
                ->orWhere('name', 'like', '%'.$term.'%')))
            ->when($filters['specialty'] ?? null, fn ($query, $id) => $query->where('specialty_id', $id))
            ->orderBy('room_code')
            ->cursorPaginate(12, cursor: $cursor)
            ->appends($filters);

        $schedules = DoctorSchedule::whereDate('work_date', $date)->where('status', 'ACTIVE')
            ->with('doctor:id,full_name')
            ->whereHas('doctor')
            ->whereColumn('start_time', '<', 'end_time')
            ->whereIn(
                'examination_room_id',
                $rooms->modelKeys()
            )
            ->orderBy('start_time')
            ->get()
            ->groupBy('examination_room_id');

        $specialties = Specialty::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $workHours = (clone $assignedToday)->whereNotNull('examination_room_id')
            ->whereNotNull('start_time')->whereNotNull('end_time')
            ->whereColumn('start_time', '<', 'end_time');
        $workStatus = (clone $workHours)->whereTime('start_time', '<=', $now->toTimeString())
            ->whereTime('end_time', '>', $now->toTimeString())->exists()
            ? 'Đang hoạt động'
            : ($workHours->exists() ? 'Ngoài giờ làm việc' : 'Không có lịch làm việc');
        return view(
            'doctor.examination.rooms',
            compact(
                'doctor',
                'rooms',
                'specialties',
                'schedules',
                'workStatus',
                'date',
                'filters'
            )
        );
    }
    public function filterQueue(Request $request, ExaminationRoom $room): RedirectResponse
    {
        $data = $request->validate([
            ...$this->queueFilterRules(),
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $search = new SessionSearch($request, 'doctor.examination.rooms.'.$room->id);
        $term = trim($data['q'] ?? '');
        unset($data['q']);

        return redirect()->route('doctor.examination.rooms.queue', [
            'room' => $room,
            ...array_filter($data),
            ...$search->remember($term),
        ]);
    }

    private function queueFilterRules(): array
    {
        return [
            'date' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['nullable', 'string', Rule::in(array_keys(self::EXAMINATION_STATUS_LABELS))],
            'shift' => ['nullable', Rule::in(array_keys(config('doctor_schedules.shifts', [])))],
        ];
    }

    public function queue(Request $request, ExaminationRoom $room): View
    {
        $doctor = $request->attributes->get('current_doctor');
        $shifts = array_keys(config('doctor_schedules.shifts', []));
        $filters = $request->validate([
            ...$this->queueFilterRules(),
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $search = new SessionSearch($request, 'doctor.examination.rooms.'.$room->id);
        $filters['q'] ??= $search->keyword();
        $date = $filters['date'] ?? today()->toDateString();
        app(DoctorAccess::class)->assertRoom($doctor, $room, $date);
        // Hiển thị mọi lượt khám trong phòng và ngày được chọn.
        $visibleVisit = fn ($visit) => $visit
            ->where('examination_room_id', $room->id)
            ->whereDate('checked_in_at', $date)
            ->when(
                $filters['shift'] ?? null,
                fn ($query, $shift) => $query
                    ->whereHas(
                        'appointment.appointmentTimeSlot.doctorSchedule',
                        fn ($schedule) => $schedule
                            ->where('shift_code', $shift)
                    )
            )
            ->when(
                $filters['q'] ?? null,
                fn ($query, $term) => $query->whereHas(
                    'patient',
                    fn ($patient) => $patient->where(fn ($match) => $match
                        ->where('full_name', 'like', '%'.$term.'%')
                        ->orWhereHas('user', fn ($user) => $user->where('phone', 'like', '%'.$term.'%'))
                        ->orWhereHas('detail', fn ($detail) => $detail->where('citizen_id_number', $term)))
                )
            );

        $stage = $this->queueStage();
        $queues = ExaminationRoomQueue::query()
            ->select('examination_room_queues.*')
            ->selectSub($stage, 'examination_stage')
            ->whereIn('queue_type', self::QUEUE_TYPES)
            ->whereHas('medicalVisit', $visibleVisit)
            ->with([
                'status',
                'medicalVisit' => fn ($query) => $visibleVisit($query)
                    ->withServiceProgress()
                    ->with([
                        'status',
                        'patient:id,patient_code,full_name',
                        'doctor:id,full_name',
                        'appointment.appointmentTimeSlot.doctorSchedule',
                    ]),
            ])
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where(
                fn ($stageQuery) => $stageQuery->selectSub($stage, 'stage'),
                '=',
                $status
            ))
            ->orderBy('queue_number')
            ->orderBy('id')
            ->paginate(15)
            ->appends([
                'date' => $filters['date'] ?? null,
                'status' => $filters['status'] ?? null,
                'shift' => $filters['shift'] ?? null,
                ...$search->navigation(),
            ]);

        // Giữ bảo vệ hiện có nếu lượt khám được điều chuyển
        // giữa lúc phân trang và eager loading.
        $visibleQueues = $queues->getCollection()->filter(fn ($queue) => $queue->medicalVisit !== null);
        $queues->setCollection($visibleQueues->values());
        $examinationStatusLabels = self::EXAMINATION_STATUS_LABELS;

        return view(
            'doctor.examination.queue',
            compact(
                'doctor',
                'room',
                'queues',
                'examinationStatusLabels',
                'date',
                'shifts',
                'filters',
                'search'
            )
        );
    }
    public function call(
        Request $request,
        ExaminationRoom $room,
        int $queue,
        ExaminationQueueWorkflow $workflow
    ): RedirectResponse {
        return $this->changeQueue($request, $room, $queue, 'call', $workflow);
    }
    public function recall(
        Request $request,
        ExaminationRoom $room,
        int $queue,
        ExaminationQueueWorkflow $workflow
    ): RedirectResponse {
        return $this->changeQueue($request, $room, $queue, 'recall', $workflow);
    }
    public function skip(
        Request $request,
        ExaminationRoom $room,
        int $queue,
        ExaminationQueueWorkflow $workflow
    ): RedirectResponse {
        return $this->changeQueue($request, $room, $queue, 'skip', $workflow);
    }
    public function start(
        Request $request,
        ExaminationRoom $room,
        int $queue,
        ExaminationQueueWorkflow $workflow
    ): RedirectResponse {
        return $this->changeQueue($request, $room, $queue, 'start', $workflow);
    }
    public function complete(
        Request $request,
        ExaminationRoom $room,
        int $visit,
        ExaminationVisitCompletion $completion
    ): RedirectResponse {
        $request->validate(['confirm' => ['accepted']]);
        $doctor = $request->attributes->get('current_doctor');
        $this->transaction(function () use ($request, $doctor, $room, $visit, $completion) {
            $visit = MedicalVisit::query()
                ->with('status')
                ->where('examination_room_id', $room->id)
                ->whereKey($visit)
                ->lockForUpdate()
                ->firstOrFail();
            $this->assertAssignment($request, $doctor, $room, $visit);
            if (
                ! $visit->examination_started_at
                || $visit->examination_ended_at
                || ! in_array(
                    $visit->status?->name,
                    ['IN_EXAMINATION', 'RETURNING'],
                    true
                )
            ) {
                throw ValidationException::withMessages([
                    'visit' => 'Lượt khám chưa bắt đầu, chưa đến bước hoàn tất hoặc đã hoàn tất.',
                ]);
            }
            $before = $visit->only(['visit_status_id', 'examination_ended_at']);
            $completion->completeLocked($visit);
            $this->audit(
                $request,
                $visit,
                $before,
                $visit->only([
                    'visit_status_id',
                    'examination_ended_at',
                ]),
                'Hoàn tất lượt khám'
            );
        });
        return redirect()
            ->route('doctor.examination.rooms.queue', $room)
            ->with('success', 'Đã hoàn tất lượt khám.');
    }
    private function changeQueue(
        Request $request,
        ExaminationRoom $room,
        int $queueId,
        string $action,
        ExaminationQueueWorkflow $workflow
    ): RedirectResponse {
        $transitions = self::ACTIONS[$action];
        $data = $request->validate([
            'confirm' => ['accepted'],
            'expected_status' => [
                'required',
                Rule::in(array_keys($transitions)),
            ],
            'expected_calls' => ['required', 'integer', 'min:0'],
            'expected_phase' => ['required', 'string', 'regex:/\A[0-9a-f]{64}\z/'],
            'from_visit' => ['sometimes', 'accepted'],
        ]);
        $doctor = $request->attributes->get('current_doctor');
        $queue = $this->roomQueue($doctor, $room)->findOrFail($queueId);
        $destination = $this->transaction(function () use (
            $request,
            $room,
            $doctor,
            $queue,
            $data,
            $transitions,
            $action,
            $workflow
        ) {
            $queue = $workflow->lockQueue($queue, [$data['expected_status']]);
            $visit = $queue->medicalVisit;
            $this->assertAssignment($request, $doctor, $room, $visit);
            if (! hash_equals($workflow->phaseToken($queue), $data['expected_phase'])) {
                throw ValidationException::withMessages([
                    'queue' => 'Hàng đợi đã chuyển giai đoạn. Vui lòng làm mới trang.',
                ]);
            }
            abort_if(
                $action === 'start'
                    && ! $this->hasD02VisitDetails(),
                409,
                'Chưa tích hợp trang khám bệnh'
            );
            if ($queue->call_count !== (int) $data['expected_calls']) {
                throw ValidationException::withMessages([
                    'queue' => 'Hàng đợi đã được gọi. Vui lòng làm mới trang.',
                ]);
            }
            $returning = $queue->queue_type === 'RETURN_AFTER_SERVICE';
            $expected = $returning
                ? 'WAITING_FOR_SERVICE'
                : 'WAITING';

            if (
                ! $queue->status?->is_active
                || $visit->status?->name !== $expected
                || ! $visit->status->allows_transition
                || (
                    ! $returning
                    && $visit->examination_started_at
                )
            ) {
                throw ValidationException::withMessages([
                    'queue' => 'Trạng thái lượt khám hiện tại không cho phép thao tác này.',
                ]);
            }

            $visit->assertCanBeginExamination();

            $status = QueueStatus::query()
                ->where('name', $transitions[$data['expected_status']])
                ->where('is_active', true)
                ->first();

            if (! $status) {
                throw ValidationException::withMessages([
                    'queue' => 'Chưa cấu hình trạng thái hàng đợi cần thiết.',
                ]);
            }
            $before = $queue->only(['queue_status_id', 'call_count', 'called_at', 'service_started_at']);
            $before['visit_status_id'] = $visit->visit_status_id;
            $nextUrl = isset($data['from_visit'])
                ? route(self::VISIT_DETAILS_ROUTE, ['visit' => $visit])
                : null;
            if ($action === 'start') {
                $nextUrl = route(self::VISIT_DETAILS_ROUTE, ['visit' => $visit]);
                $workflow->startLocked($queue, $status);
            } elseif ($action === 'skip') {
                $queue->update(['queue_status_id' => $status->id]);
            } else {
                $workflow->assertSingleOpenQueue($queue);
                $workflow->callLocked($queue, $status);
            }
            $after = $queue->only(['queue_status_id', 'call_count', 'called_at', 'service_started_at']);
            $after['visit_status_id'] = $visit->visit_status_id;
            $this->audit(
                $request,
                $queue,
                $before,
                $after,
                match ($action) {
                    'call' => 'Gọi bệnh nhân',
                    'recall' => 'Gọi lại bệnh nhân',
                    'skip' => 'Bỏ qua lượt chờ',
                    'start' => 'Bắt đầu khám',
                }
            );
            return $nextUrl;
        });
        return ($destination ? redirect()->to($destination) : back())->with(
            'success',
            match ($action) {
                'call' => 'Đã gọi bệnh nhân thành công',
                'recall' => 'Đã gọi lại bệnh nhân.',
                'skip' => 'Đã bỏ qua lượt chờ.',
                'start' => 'Đã bắt đầu khám.',
            }
        );
    }
    private function hasD02VisitDetails(): bool
    {
        $route = Route::getRoutes()->getByName(self::VISIT_DETAILS_ROUTE);

        return $route !== null
            && in_array('GET', $route->methods(), true)
            && $route->parameterNames() === ['visit']
            && $route->getActionName()
            !== ClinicalVisitController::class.'@show';
    }

    private function waitingCount(string $date): Builder
    {
        $stage = $this->queueStage();

        return ExaminationRoomQueue::query()
            ->join('medical_visits as waiting_visits', 'waiting_visits.id', '=', 'examination_room_queues.medical_visit_id')
            ->selectRaw('count(distinct waiting_visits.patient_id)')
            ->whereColumn('waiting_visits.examination_room_id', 'examination_rooms.id')
            ->whereDate('waiting_visits.checked_in_at', $date)
            ->whereIn('examination_room_queues.queue_type', self::QUEUE_TYPES)
            ->whereDoesntHave('medicalVisit.payables', fn ($payables) => $payables
                ->where('payment_type', 'EXAMINATION_FEE')
                ->whereDoesntHave('paymentStatus', fn ($status) => $status->where('name', 'PAID')))
            ->whereHas('status', fn ($status) => $status->whereIn('name', ['WAITING', 'CALLED'])->where('is_active', true))
            ->whereRaw('('.$stage->toSql().') in (?, ?, ?)', [...$stage->getBindings(), 'WAITING', 'CALLED', 'RETURNING']);
    }

    private function queueStage(): Builder
    {
        $items = ServiceOrderItem::query()->selectRaw('1')
            ->join('service_orders', 'service_orders.id', '=', 'service_order_items.service_order_id')
            ->whereColumn('service_orders.medical_visit_id', 'medical_visits.id');
        $unresolved = (clone $items)->unresolved();

        // Use the same classification for the badge and the SQL filter before pagination.
        return MedicalVisit::query()
            ->join('visit_statuses as visit_state', 'visit_state.id', '=', 'medical_visits.visit_status_id')
            ->crossJoin('queue_statuses as queue_state')
            ->whereColumn('queue_state.id', 'examination_room_queues.queue_status_id')
            ->whereColumn('medical_visits.id', 'examination_room_queues.medical_visit_id')
            ->selectRaw("
                CASE
                    WHEN visit_state.name = 'COMPLETED' AND examination_ended_at IS NOT NULL THEN 'COMPLETED'
                    WHEN examination_ended_at IS NOT NULL THEN NULL
                    WHEN visit_state.name IN ('IN_EXAMINATION', 'RETURNING') AND examination_started_at IS NOT NULL THEN 'IN_EXAMINATION'
                    WHEN visit_state.name = 'WAITING_FOR_SERVICE' THEN
                        CASE
                            WHEN examination_room_queues.queue_type = 'RETURN_AFTER_SERVICE'
                                AND queue_state.is_active AND queue_state.allows_transition
                                AND queue_state.name IN ('WAITING', 'CALLED', 'SKIPPED')
                                AND EXISTS ({$items->toSql()}) AND NOT EXISTS ({$unresolved->toSql()})
                            THEN CASE WHEN queue_state.name = 'CALLED' THEN 'CALLED' ELSE 'RETURNING' END
                            ELSE 'WAITING_FOR_SERVICE'
                        END
                    WHEN visit_state.name = 'WAITING' AND examination_started_at IS NULL
                        AND examination_room_queues.queue_type = 'INITIAL_EXAM' THEN
                        CASE
                            WHEN queue_state.name = 'CALLED' AND queue_state.is_active AND queue_state.allows_transition THEN 'CALLED'
                            WHEN queue_state.name IN ('WAITING', 'SKIPPED') THEN 'WAITING'
                            ELSE NULL
                        END
                END
            ", [...$items->getBindings(), ...$unresolved->getBindings()]);
    }

    private function roomQueue(Doctor $doctor, ExaminationRoom $room): Builder
    {
        return ExaminationRoomQueue::query()
            ->whereIn('queue_type', self::QUEUE_TYPES)
            ->whereHas(
                'medicalVisit',
                fn ($visit) => $visit
                    ->where(
                        'examination_room_id',
                        $room->id
                    )
            );
    }

    private function transaction(Closure $action): mixed
    {
        try {
            return DB::transaction($action, 3);
        } catch (QueryException $exception) {
            if (
                ! app(ConcurrencyErrorDetector::class)
                    ->causedByConcurrencyError($exception)
            ) {
                throw $exception;
            }

            throw ValidationException::withMessages([
                'queue' => 'Lượt khám đang được xử lý. Vui lòng làm mới hàng đợi và thử lại.',
            ]);
        }
    }

    private function assertAssignment(
        Request $request,
        Doctor $doctor,
        ExaminationRoom $room,
        MedicalVisit $visit
    ): void {
        $access = app(DoctorAccess::class);

        $user = $request->user()->fresh();

        $current = $user
            ? $access->doctor($user)
            : null;

        abort_unless(
            $current
                && $current->is($doctor)
                && $access->canExamine($user, $current),
            403
        );

        abort_unless(
            (int) $visit->examination_room_id
                === (int) $room->id,
            404
        );

        // Không khóa phòng sau khi đã khóa lượt khám,
        // tránh đảo thứ tự lock với quy trình tiếp nhận.
        if (
            ExaminationRoom::query()
                ->whereKey($room->id)
                ->value('status') !== 'ACTIVE'
        ) {
            throw ValidationException::withMessages([
                'room' => 'Phòng khám không còn hoạt động.',
            ]);
        }

        abort_unless($visit->checked_in_at, 404);

        $access->assertRoom(
            $current,
            $room,
            $visit->checked_in_at->toDateString()
        );

        if ($visit->appointment_id) {
            $appointment = Appointment::query()
                ->whereKey($visit->appointment_id)
                ->lockForUpdate()
                ->firstOrFail();

            $fields = ['patient_id', 'doctor_id', 'examination_room_id', 'specialty_id'];

            if (
                $appointment->only($fields)
                !== $visit->only($fields)
                || ! in_array(
                    $appointment->appointmentStatus?->name,
                    ['CHECKED_IN', 'IN_EXAMINATION'],
                    true
                )
                || ! $appointment->appointmentStatus->is_active
            ) {
                throw ValidationException::withMessages([
                    'visit' => 'Thông tin lịch hẹn không còn khớp với lượt khám đã tiếp nhận.',
                ]);
            }

            $visit->setRelation('appointment', $appointment);
        }
    }

    private function audit(
        Request $request,
        MedicalVisit|ExaminationRoomQueue $record,
        array $before,
        array $after,
        string $description
    ): void {
        $visitCode = $record instanceof MedicalVisit
            ? $record->visit_code
            : $record->medicalVisit->visit_code;

        AuditLog::create([
            'user_id' => $request->user()->id,
            'role_name_snapshot' => 'Doctor',
            'action' => 'STATUS_CHANGE',
            'entity_type' => $record->getTable(),
            'entity_id' => $record->id,
            'entity_business_code' => $visitCode,
            'old_values' => $before,
            'new_values' => $after,
            'description' => $description,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr(
                (string) $request->userAgent(),
                0,
                500
            ),
            'performed_at' => now(),
        ]);
    }
}
