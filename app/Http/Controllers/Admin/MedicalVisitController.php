<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateMedicalVisitStatusRequest;
use App\Models\AdminDisplay;
use App\Models\AdminLookup;
use App\Models\AppointmentStatus;
use App\Models\Doctor;
use App\Models\ExaminationRoom;
use App\Models\MedicalVisit;
use App\Models\ServiceOrderItem;
use App\Models\Specialty;
use App\Models\VisitStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MedicalVisitController extends Controller
{
    public function index(Request $request): View
    {
        $query = MedicalVisit::query()
            ->with([
                'appointment',
                'patient',
                'doctor',
                'specialty',
                'examinationRoom',
                'status',
            ]);

        if ($request->filled('search')) {
            $search = trim(
                (string) $request->input('search')
            );

            $query->where(function ($q) use ($search) {
                $q->where(
                    'visit_code',
                    'like',
                    "%{$search}%"
                )
                    ->orWhereHas(
                        'patient',
                        function ($patientQuery) use ($search) {
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
                    )
                    ->orWhereHas(
                        'doctor',
                        function ($doctorQuery) use ($search) {
                            $doctorQuery
                                ->where(
                                    'doctor_code',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'full_name',
                                    'like',
                                    "%{$search}%"
                                );
                        }
                    )
                    ->orWhereHas(
                        'appointment',
                        function ($appointmentQuery) use ($search) {
                            $appointmentQuery->where(
                                'appointment_code',
                                'like',
                                "%{$search}%"
                            );
                        }
                    );
            });
        }

        if ($request->filled('visit_status_id')) {
            $query->where(
                'visit_status_id',
                $request->integer('visit_status_id')
            );
        }

        if ($request->filled('doctor_id')) {
            $query->where(
                'doctor_id',
                $request->integer('doctor_id')
            );
        }

        if ($request->filled('specialty_id')) {
            $query->where(
                'specialty_id',
                $request->integer('specialty_id')
            );
        }

        if ($request->filled('examination_room_id')) {
            $query->where(
                'examination_room_id',
                $request->integer('examination_room_id')
            );
        }

        if ($request->filled('reception_source')) {
            $query->where(
                'reception_source',
                $request->input('reception_source')
            );
        }

        if ($request->filled('date_from')) {
            $query->whereDate(
                'checked_in_at',
                '>=',
                $request->input('date_from')
            );
        }

        if ($request->filled('date_to')) {
            $query->whereDate(
                'checked_in_at',
                '<=',
                $request->input('date_to')
            );
        }

        $medicalVisits = $query
            ->orderByDesc('checked_in_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $visitStatuses = VisitStatus::query()
            ->orderBy('process_order')
            ->get();

        $doctors = AdminLookup::initial(Doctor::query()->orderBy('full_name'), $request->input('doctor_id'));

        $specialties = AdminLookup::initial(Specialty::query()->orderBy('name'), $request->input('specialty_id'));

        $examinationRooms = AdminLookup::initial(ExaminationRoom::query()->orderBy('name'), $request->input('examination_room_id'));

        $receptionSources = MedicalVisit::query()
            ->whereNotNull('reception_source')
            ->where(
                'reception_source',
                '!=',
                ''
            )
            ->distinct()
            ->orderBy('reception_source')
            ->pluck('reception_source');

        return view(
            'admin.visits.index',
            compact(
                'medicalVisits',
                'visitStatuses',
                'doctors',
                'specialties',
                'examinationRooms',
                'receptionSources'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Chi tiết lượt khám
    |--------------------------------------------------------------------------
    |
    | Route resource sinh tham số {visit},
    | vì vậy tham số Controller phải là $visit để Laravel binding đúng.
    |
    */

    public function show(
        MedicalVisit $visit
    ): View {
        $medicalVisit = $visit;

        $medicalVisit->load([
            'appointment',
            'patient.user',
            'patient.detail',
            'doctor.user',
            'doctor.detail',
            'specialty',
            'examinationRoom',
            'status',
            'queues.status',
            'clinicalNote',
            'diagnoses.disease',
            'conclusion.doctor',

            /*
            |--------------------------------------------------------------------------
            | Chỉ định dịch vụ
            |--------------------------------------------------------------------------
            */
            'serviceOrders' => function ($query) {
                $query
                    ->orderByDesc('ordered_at')
                    ->orderByDesc('id');
            },

            'serviceOrders.orderedByDoctor',

            'serviceOrders.items' => function ($query) {
                $query->orderBy('id');
            },

            'serviceOrders.items.medicalService',

            'serviceOrders.items.serviceStatus',
        ]);

        $allowedStatusNames =
            $this->allowedTransitions(
                $medicalVisit->status?->name
            );

        $allowedVisitStatuses =
            VisitStatus::query()
                ->where(
                    'is_active',
                    true
                )
                ->whereIn(
                    'name',
                    $allowedStatusNames
                )
                ->orderBy('process_order')
                ->get();

        return view(
            'admin.visits.show',
            compact(
                'medicalVisit',
                'allowedVisitStatuses'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Cập nhật trạng thái lượt khám
    |--------------------------------------------------------------------------
    |
    | Route riêng của chức năng này dùng {medicalVisit},
    | nên giữ tham số $medicalVisit.
    |
    */

    public function updateStatus(
        UpdateMedicalVisitStatusRequest $request,
        MedicalVisit $medicalVisit
    ): RedirectResponse {
        $medicalVisit->load([
            'status',
            'appointment',
        ]);

        $currentStatus =
            $medicalVisit->status;

        if (! $currentStatus) {
            return back()->with(
                'error',
                'Lượt khám chưa có trạng thái hợp lệ.'
            );
        }

        if (! $currentStatus->allows_transition) {
            return back()->with(
                'error',
                'Trạng thái lượt khám đang bị khóa thay đổi. Vui lòng kiểm tra mục Trạng thái lượt khám.'
            );
        }

        $newStatus =
            VisitStatus::query()
                ->whereKey(
                    $request->integer(
                        'visit_status_id'
                    )
                )
                ->where(
                    'is_active',
                    true
                )
                ->firstOrFail();

        $allowedTransitions =
            $this->allowedTransitions(
                $currentStatus->name
            );

        if (
            ! in_array(
                $newStatus->name,
                $allowedTransitions,
                true
            )
        ) {
            return back()->with(
                'error',
                'Không thể đổi lượt khám từ “'
                .AdminDisplay::label($currentStatus->name)
                .'” sang “'
                .AdminDisplay::label($newStatus->name)
                .'” ở bước này. Vui lòng thực hiện đúng thứ tự các bước khám.'
            );
        }

        DB::transaction(
            function () use (
                $medicalVisit,
                $newStatus
            ) {
                $medicalVisit = MedicalVisit::with('status')->whereKey($medicalVisit->id)->lockForUpdate()->firstOrFail();
                $medicalVisit->assertNotBeforeAppointmentDay();
                if ($newStatus->name === 'IN_EXAMINATION') {
                    $medicalVisit->assertCanBeginExamination();
                }
                if (! $medicalVisit->status?->allows_transition || ! in_array($newStatus->name, $this->allowedTransitions($medicalVisit->status?->name), true)) {
                    throw ValidationException::withMessages(['visit_status_id' => 'Trạng thái lượt khám đã thay đổi. Vui lòng tải lại trang.']);
                }
                if ($newStatus->name === 'COMPLETED' && ServiceOrderItem::whereHas('serviceOrder', fn ($q) => $q->where('medical_visit_id', $medicalVisit->id))
                    ->unresolved()->exists()) {
                    throw ValidationException::withMessages(['visit_status_id' => 'Chưa thể hoàn tất lượt khám vì còn dịch vụ cần chờ. Vui lòng kiểm tra tiến độ dịch vụ.']);
                }
                if ($newStatus->name === 'COMPLETED' && (! $medicalVisit->diagnoses()->exists() || ! $medicalVisit->conclusion()->exists())) {
                    throw ValidationException::withMessages(['visit_status_id' => 'Chưa thể hoàn tất lượt khám. Vui lòng bổ sung đủ chẩn đoán và kết luận.']);
                }
                $updateData = [
                    'visit_status_id' => $newStatus->id,
                ];

                /*
                |--------------------------------------------------------------------------
                | Bắt đầu khám
                |--------------------------------------------------------------------------
                */

                if (
                    $newStatus->name
                    ===
                    'IN_EXAMINATION'
                ) {
                    if (
                        ! $medicalVisit
                            ->examination_started_at
                    ) {
                        $updateData[
                            'examination_started_at'
                        ] = now();
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Hoàn tất khám
                |--------------------------------------------------------------------------
                */

                if (
                    $newStatus->name
                    ===
                    'COMPLETED'
                ) {
                    $updateData[
                        'examination_ended_at'
                    ] =
                        $medicalVisit
                            ->examination_ended_at
                        ?? now();
                }

                $medicalVisit->update(
                    $updateData
                );
                if ($newStatus->name === 'COMPLETED') {
                    $medicalVisit->completeOpenQueues($medicalVisit->examination_ended_at);
                }

                /*
                |--------------------------------------------------------------------------
                | Đồng bộ trạng thái lịch hẹn
                |--------------------------------------------------------------------------
                */

                if (
                    $newStatus->name
                    ===
                    'IN_EXAMINATION'
                ) {
                    $this->syncAppointmentStatus(
                        $medicalVisit,
                        'IN_EXAMINATION'
                    );
                }

                if (
                    $newStatus->name
                    ===
                    'COMPLETED'
                ) {
                    $this->syncAppointmentStatus(
                        $medicalVisit,
                        'COMPLETED'
                    );
                }
            }
        );

        return redirect()
            ->route(
                'admin.visits.show',
                $medicalVisit
            )
            ->with(
                'success',
                'Cập nhật trạng thái lượt khám thành công.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Luồng trạng thái lượt khám
    |--------------------------------------------------------------------------
    */

    private function allowedTransitions(
        ?string $currentStatus
    ): array {
        return match ($currentStatus) {

            'WAITING' => [
                'IN_EXAMINATION',
            ],

            'IN_EXAMINATION' => [
                'COMPLETED',
            ],

            'WAITING_FOR_SERVICE' => [],

            'RETURNING' => [
                'COMPLETED',
            ],

            'COMPLETED' => [],

            default => [],
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Đồng bộ trạng thái lịch hẹn
    |--------------------------------------------------------------------------
    */

    private function syncAppointmentStatus(
        MedicalVisit $medicalVisit,
        string $statusName
    ): void {
        if (
            ! $medicalVisit
                ->appointment_id
        ) {
            return;
        }

        $appointmentStatus =
            AppointmentStatus::query()
                ->where(
                    'name',
                    $statusName
                )
                ->where(
                    'is_active',
                    true
                )
                ->first();

        if (! $appointmentStatus) {
            return;
        }

        DB::table('appointments')
            ->where(
                'id',
                $medicalVisit
                    ->appointment_id
            )
            ->update([
                'appointment_status_id' => $appointmentStatus->id,

                'updated_at' => now(),
            ]);
    }
}
