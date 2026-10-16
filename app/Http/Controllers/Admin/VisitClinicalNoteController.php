<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateVisitClinicalNoteRequest;
use App\Models\VisitClinicalNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VisitClinicalNoteController extends Controller
{
    public function index(Request $request): View
    {
        $query = VisitClinicalNote::query()
            ->with([
                'medicalVisit.patient',
                'medicalVisit.doctor',
                'medicalVisit.specialty',
                'medicalVisit.examinationRoom',
                'medicalVisit.status',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Tìm kiếm
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {
            $search = trim(
                (string) $request->input('search')
            );

            $query->where(function ($q) use ($search) {
                $q->where(
                    'reason_for_visit',
                    'like',
                    "%{$search}%"
                )
                    ->orWhere(
                        'current_symptoms',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'initial_assessment',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhereHas(
                        'medicalVisit',
                        function ($visitQuery) use ($search) {
                            $visitQuery
                                ->where(
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
                                );
                        }
                    );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Lọc ngày ghi nhận
        |--------------------------------------------------------------------------
        */

        if ($request->filled('date_from')) {
            $query->whereDate(
                'recorded_at',
                '>=',
                $request->input('date_from')
            );
        }

        if ($request->filled('date_to')) {
            $query->whereDate(
                'recorded_at',
                '<=',
                $request->input('date_to')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Lọc bác sĩ
        |--------------------------------------------------------------------------
        */

        if ($request->filled('doctor_id')) {
            $query->whereHas(
                'medicalVisit',
                function ($visitQuery) use ($request) {
                    $visitQuery->where(
                        'doctor_id',
                        $request->integer('doctor_id')
                    );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Lọc trạng thái lượt khám
        |--------------------------------------------------------------------------
        */

        if ($request->filled('visit_status_id')) {
            $query->whereHas(
                'medicalVisit',
                function ($visitQuery) use ($request) {
                    $visitQuery->where(
                        'visit_status_id',
                        $request->integer('visit_status_id')
                    );
                }
            );
        }

        $clinicalNotes = $query
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $doctors = \App\Models\Doctor::query()
            ->orderBy('full_name')
            ->get();

        $visitStatuses = \App\Models\VisitStatus::query()
            ->orderBy('process_order')
            ->orderBy('id')
            ->get();

        return view(
            'admin.clinical-notes.index',
            compact(
                'clinicalNotes',
                'doctors',
                'visitStatuses'
            )
        );
    }

    public function show(
        VisitClinicalNote $clinicalNote
    ): View {
        $clinicalNote->load([
            'medicalVisit.appointment',
            'medicalVisit.patient',
            'medicalVisit.doctor',
            'medicalVisit.specialty',
            'medicalVisit.examinationRoom',
            'medicalVisit.status',
        ]);

        return view(
            'admin.clinical-notes.show',
            compact('clinicalNote')
        );
    }

    public function edit(
        VisitClinicalNote $clinicalNote
    ): View {
        $clinicalNote->load([
            'medicalVisit.patient',
            'medicalVisit.doctor',
            'medicalVisit.status',
        ]);

        return view(
            'admin.clinical-notes.edit',
            compact('clinicalNote')
        );
    }

    public function update(
        UpdateVisitClinicalNoteRequest $request,
        VisitClinicalNote $clinicalNote
    ): RedirectResponse {
        /*
        |--------------------------------------------------------------------------
        | Không chỉnh hồ sơ lượt khám đã hoàn tất
        |--------------------------------------------------------------------------
        */

        $clinicalNote->load(
            'medicalVisit.status'
        );

        if (
            $clinicalNote->medicalVisit?->status?->name
            ===
            'COMPLETED'
        ) {
            return back()->with(
                'error',
                'Không thể chỉnh sửa nội dung khám của lượt khám đã hoàn tất.'
            );
        }

        $data = $request->validated();

        $clinicalNote->update([
            'reason_for_visit' =>
                $data['reason_for_visit'] ?? null,

            'current_symptoms' =>
                $data['current_symptoms'] ?? null,

            'present_illness_history' =>
                $data['present_illness_history'] ?? null,

            'relevant_medical_history' =>
                $data['relevant_medical_history'] ?? null,

            'physical_examination' =>
                $data['physical_examination'] ?? null,

            'initial_assessment' =>
                $data['initial_assessment'] ?? null,

            'notes' =>
                $data['notes'] ?? null,

            'recorded_at' =>
                $data['recorded_at']
                ?? $clinicalNote->recorded_at
                ?? now(),
        ]);

        return redirect()
            ->route(
                'admin.clinical-notes.show',
                $clinicalNote
            )
            ->with(
                'success',
                'Cập nhật nội dung khám thành công.'
            );
    }
}
