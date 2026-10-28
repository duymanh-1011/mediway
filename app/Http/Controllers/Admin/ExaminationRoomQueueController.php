<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\ExaminationRoom;
use App\Models\ExaminationRoomQueue;
use App\Models\MedicalVisit;
use App\Models\QueueStatus;
use App\Models\ServiceOrderItem;
use App\Models\VisitStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ExaminationRoomQueueController extends Controller
{
    public function index(Request $request): View
    {
        $query =
            ExaminationRoomQueue::query()
                ->with([
                    'status',
                    'medicalVisit.patient',
                    'medicalVisit.doctor',
                    'medicalVisit.examinationRoom',
                ]);

        /*
        |--------------------------------------------------------------------------
        | Tìm kiếm
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {
            $search =
                trim(
                    (string) $request->input(
                        'search'
                    )
                );

            $query->where(
                function ($q) use ($search) {
                    $q->where(
                        'queue_number',
                        'like',
                        "%{$search}%"
                    )
                        ->orWhereHas(
                            'medicalVisit',
                            function ($visitQuery) use (
                                $search
                            ) {
                                $visitQuery
                                    ->where(
                                        'visit_code',
                                        'like',
                                        "%{$search}%"
                                    )
                                    ->orWhereHas(
                                        'patient',
                                        function ($patientQuery) use (
                                            $search
                                        ) {
                                            $patientQuery
                                                ->where(
                                                    'patient_code',
                                                    'like',
                                                    "%{$search}%"
                                                )
                                                ->orWhere(
                                                    'full_name',
                                                    'like',
                                                    "%{$search}%"
                                                );
                                        }
                                    );
                            }
                        );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Trạng thái hàng đợi
        |--------------------------------------------------------------------------
        */

        if (
            $request->filled(
                'queue_status_id'
            )
        ) {
            $query->where(
                'queue_status_id',
                $request->integer(
                    'queue_status_id'
                )
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Loại hàng đợi
        |--------------------------------------------------------------------------
        */

        if ($request->filled('queue_type')) {
            $query->where(
                'queue_type',
                $request->input('queue_type')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Ngày
        |--------------------------------------------------------------------------
        */

        if ($request->filled('date')) {
            $query->whereDate(
                'queued_at',
                $request->input('date')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Bác sĩ
        |--------------------------------------------------------------------------
        */

        if ($request->filled('doctor_id')) {
            $query->whereHas(
                'medicalVisit',
                function ($visitQuery) use (
                    $request
                ) {
                    $visitQuery->where(
                        'doctor_id',
                        $request->integer(
                            'doctor_id'
                        )
                    );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Phòng khám
        |--------------------------------------------------------------------------
        */

        if (
            $request->filled(
                'examination_room_id'
            )
        ) {
            $query->whereHas(
                'medicalVisit',
                function ($visitQuery) use (
                    $request
                ) {
                    $visitQuery->where(
                        'examination_room_id',
                        $request->integer(
                            'examination_room_id'
                        )
                    );
                }
            );
        }

        $queues =
            $query
                ->orderByDesc('queued_at')
                ->orderBy('queue_number')
                ->paginate(15)
                ->withQueryString();

        $queueStatuses =
            QueueStatus::query()
                ->orderBy('process_order')
                ->orderBy('id')
                ->get();

        $doctors =
            Doctor::query()
                ->orderBy('full_name')
                ->get();

        $examinationRooms =
            ExaminationRoom::query()
                ->orderBy('name')
                ->get();

        return view(
            'admin.queues.index',
            compact(
                'queues',
                'queueStatuses',
                'doctors',
                'examinationRooms'
            )
        );
    }

    public function show(
        ExaminationRoomQueue $queue
    ): View {
        $queue->load([
            'status',
            'medicalVisit.status',
            'medicalVisit.patient',
            'medicalVisit.doctor',
            'medicalVisit.specialty',
            'medicalVisit.examinationRoom',
        ]);

        return view(
            'admin.queues.show',
            compact('queue')
        );
    }

    public function call(
        ExaminationRoomQueue $queue
    ): RedirectResponse {
        $queue->load('status');

        if (
            ! in_array(
                $queue->status?->name,
                [
                    'WAITING',
                    'CALLED',
                    'SKIPPED',
                ],
                true
            )
        ) {
            return back()->with(
                'error',
                'Chưa thể gọi bệnh nhân ở bước này. Vui lòng tải lại danh sách và kiểm tra trạng thái hàng đợi.'
            );
        }

        $calledStatus =
            QueueStatus::query()
                ->where(
                    'name',
                    'CALLED'
                )
                ->where(
                    'is_active',
                    true
                )
                ->first();

        if (! $calledStatus) {
            return back()->with(
                'error',
                'Chưa có trạng thái “Đã gọi” đang được sử dụng. Vui lòng kiểm tra mục Trạng thái hàng đợi.'
            );
        }

        DB::transaction(function () use (
            $queue,
            $calledStatus
        ) {
            $queue = $this->lockQueue($queue, ['WAITING', 'CALLED', 'SKIPPED']);
            $queue->update([
                'queue_status_id' => $calledStatus->id,

                'called_at' => now(),

                'call_count' => ((int) $queue->call_count) + 1,
            ]);
        });

        return back()->with(
            'success',
            'Đã gọi bệnh nhân.'
        );
    }

    public function startService(
        ExaminationRoomQueue $queue
    ): RedirectResponse {
        $queue->load([
            'status',
            'medicalVisit',
        ]);

        if (
            $queue->status?->name
            !==
            'CALLED'
        ) {
            return back()->with(
                'error',
                'Vui lòng gọi bệnh nhân trước khi bắt đầu phục vụ.'
            );
        }

        $inServiceStatus =
            QueueStatus::query()
                ->where(
                    'name',
                    'IN_SERVICE'
                )
                ->where(
                    'is_active',
                    true
                )
                ->first();

        if (! $inServiceStatus) {
            return back()->with(
                'error',
                'Chưa có trạng thái “Đang phục vụ” đang được sử dụng. Vui lòng kiểm tra mục Trạng thái hàng đợi.'
            );
        }

        DB::transaction(function () use (
            $queue,
            $inServiceStatus
        ) {
            $queue = $this->lockQueue($queue, ['CALLED']);
            $queue->medicalVisit->assertCanBeginExamination();
            $expectedVisitStatuses = $queue->queue_type === 'RETURN_AFTER_SERVICE'
                ? ['WAITING_FOR_SERVICE']
                : ['WAITING', 'IN_EXAMINATION'];
            $nextVisitStatus = $queue->queue_type === 'RETURN_AFTER_SERVICE' ? 'RETURNING' : 'IN_EXAMINATION';
            if (! in_array($queue->queue_type, ['INITIAL_EXAM', 'RETURN_AFTER_SERVICE'], true)
                || ! in_array($queue->medicalVisit->status?->name, $expectedVisitStatuses, true)
                || ! VisitStatus::where('name', $nextVisitStatus)->where('is_active', true)->exists()) {
                throw ValidationException::withMessages(['queue' => 'Chưa thể bắt đầu phục vụ ở bước hiện tại. Vui lòng kiểm tra trạng thái lượt khám.']);
            }
            $queue->medicalVisit->transitionStatus($nextVisitStatus);
            $queue->medicalVisit->synchronizeAppointment('IN_EXAMINATION');
            if ($queue->queue_type === 'RETURN_AFTER_SERVICE') {
                $items = ServiceOrderItem::whereHas('serviceOrder', fn ($q) => $q->where('medical_visit_id', $queue->medical_visit_id));
                if (! $items->exists() || (clone $items)->unresolved()->exists()) {
                    throw ValidationException::withMessages(['queue' => 'Phải hoàn tất các dịch vụ chưa hủy trước khi quay lại bác sĩ.']);
                }
            }
            $queue->update([
                'queue_status_id' => $inServiceStatus->id,

                'service_started_at' => $queue->service_started_at
                    ?? now(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | Khám ban đầu
            |--------------------------------------------------------------------------
            */

            if (
                $queue->queue_type
                ===
                'INITIAL_EXAM'
            ) {
                $visitStatus =
                    VisitStatus::query()
                        ->where(
                            'name',
                            'IN_EXAMINATION'
                        )
                        ->where(
                            'is_active',
                            true
                        )
                        ->first();

                if ($visitStatus) {
                    $queue->medicalVisit->update([
                        'visit_status_id' => $visitStatus->id,

                        'examination_started_at' => $queue->medicalVisit
                            ->examination_started_at
                            ?? now(),
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Appointment -> IN_EXAMINATION
                |--------------------------------------------------------------------------
                */

            }

            /*
            |--------------------------------------------------------------------------
            | Quay lại sau dịch vụ
            |--------------------------------------------------------------------------
            */

            if (
                $queue->queue_type
                ===
                'RETURN_AFTER_SERVICE'
            ) {
                $visitStatus =
                    VisitStatus::query()
                        ->where(
                            'name',
                            'RETURNING'
                        )
                        ->where(
                            'is_active',
                            true
                        )
                        ->first();

                if ($visitStatus) {
                    $queue->medicalVisit->update([
                        'visit_status_id' => $visitStatus->id,
                    ]);
                }
            }
        });

        return back()->with(
            'success',
            'Đã bắt đầu phục vụ bệnh nhân.'
        );
    }

    public function complete(
        ExaminationRoomQueue $queue
    ): RedirectResponse {
        $queue->load('status');

        if ($queue->status?->name === 'COMPLETED') {
            return back()->with('success', 'Lượt phục vụ trong hàng đợi đã kết thúc.');
        }

        if (
            $queue->status?->name
            !==
            'IN_SERVICE'
        ) {
            return back()->with(
                'error',
                'Chỉ hàng đợi đang phục vụ mới được hoàn tất.'
            );
        }

        $completedStatus =
            QueueStatus::query()
                ->where(
                    'name',
                    'COMPLETED'
                )
                ->where(
                    'is_active',
                    true
                )
                ->first();

        if (! $completedStatus) {
            return back()->with(
                'error',
                'Chưa có trạng thái “Đã hoàn thành” đang được sử dụng. Vui lòng kiểm tra mục Trạng thái hàng đợi.'
            );
        }

        DB::transaction(function () use (
            $queue,
            $completedStatus
        ) {
            $queue = $this->lockQueue($queue, ['IN_SERVICE']);
            $queue->update([
                'queue_status_id' => $completedStatus->id,

                'completed_at' => $queue->completed_at
                    ?? now(),
            ]);
        });

        return back()->with(
            'success',
            'Đã kết thúc lượt phục vụ trong hàng đợi. Trạng thái lượt khám không thay đổi.'
        );
    }

    public function skip(
        ExaminationRoomQueue $queue
    ): RedirectResponse {
        $queue->load('status');

        if (
            ! in_array(
                $queue->status?->name,
                [
                    'WAITING',
                    'CALLED',
                ],
                true
            )
        ) {
            return back()->with(
                'error',
                'Chỉ có thể bỏ qua bệnh nhân đang chờ hoặc đã được gọi. Vui lòng kiểm tra lại hàng đợi.'
            );
        }

        $skippedStatus =
            QueueStatus::query()
                ->where(
                    'name',
                    'SKIPPED'
                )
                ->where(
                    'is_active',
                    true
                )
                ->first();

        if (! $skippedStatus) {
            return back()->with(
                'error',
                'Chưa có trạng thái “Đã bỏ qua” đang được sử dụng. Vui lòng kiểm tra mục Trạng thái hàng đợi.'
            );
        }

        DB::transaction(function () use (
            $queue,
            $skippedStatus
        ) {
            $queue = $this->lockQueue($queue, ['WAITING', 'CALLED']);
            $queue->update([
                'queue_status_id' => $skippedStatus->id,
            ]);
        });

        return back()->with(
            'success',
            'Đã tạm bỏ qua bệnh nhân trong hàng đợi. Bạn có thể gọi lại khi bệnh nhân sẵn sàng.'
        );
    }

    private function lockQueue(ExaminationRoomQueue $queue, array $allowed): ExaminationRoomQueue
    {
        MedicalVisit::whereKey($queue->medical_visit_id)->lockForUpdate()->firstOrFail();
        $queue = ExaminationRoomQueue::with(['status', 'medicalVisit.status'])->whereKey($queue->id)->lockForUpdate()->firstOrFail();
        if ($queue->medicalVisit->status?->name === 'COMPLETED') {
            throw ValidationException::withMessages(['queue' => 'Lượt khám đã hoàn tất, không thể tiếp tục thao tác hàng đợi.']);
        }
        if (! in_array($queue->status?->name, $allowed, true) || ! $queue->status?->allows_transition) {
            throw ValidationException::withMessages(['queue' => 'Hàng đợi đã đổi trạng thái. Vui lòng tải lại trang.']);
        }

        return $queue;
    }
}
