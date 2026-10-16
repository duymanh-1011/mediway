<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVisitClinicalNoteRequest;
use App\Models\MedicalVisit;
use App\Models\VisitClinicalNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MedicalVisitClinicalNoteController extends Controller
{
    public function create(
        MedicalVisit $visit
    ): View|RedirectResponse {
        $visit->load([
            'patient',
            'doctor',
            'specialty',
            'examinationRoom',
            'status',
            'clinicalNote',
        ]);

        if ($visit->clinicalNote) {
            return redirect()
                ->route(
                    'admin.clinical-notes.show',
                    $visit->clinicalNote
                )
                ->with(
                    'error',
                    'Lượt khám này đã có nội dung khám.'
                );
        }

        if (
            $visit->status?->name
            ===
            'COMPLETED'
        ) {
            return redirect()
                ->route(
                    'admin.visits.show',
                    $visit
                )
                ->with(
                    'error',
                    'Không thể thêm nội dung khám cho lượt khám đã hoàn tất.'
                );
        }

        return view(
            'admin.clinical-notes.create',
            compact('visit')
        );
    }

    public function store(
        StoreVisitClinicalNoteRequest $request,
        MedicalVisit $visit
    ): RedirectResponse {
        $visit->load([
            'status',
            'clinicalNote',
        ]);

        if ($visit->clinicalNote) {
            return redirect()
                ->route(
                    'admin.clinical-notes.show',
                    $visit->clinicalNote
                )
                ->with(
                    'error',
                    'Lượt khám này đã có nội dung khám.'
                );
        }

        if (
            $visit->status?->name
            ===
            'COMPLETED'
        ) {
            return redirect()
                ->route(
                    'admin.visits.show',
                    $visit
                )
                ->with(
                    'error',
                    'Không thể thêm nội dung khám cho lượt khám đã hoàn tất.'
                );
        }

        $data = $request->validated();

        $clinicalNote = DB::transaction(
            function () use (
                $visit,
                $data
            ) {
                return VisitClinicalNote::create([
                    'medical_visit_id' =>
                        $visit->id,

                    'reason_for_visit' =>
                        $data['reason_for_visit']
                        ?? null,

                    'current_symptoms' =>
                        $data['current_symptoms']
                        ?? null,

                    'present_illness_history' =>
                        $data['present_illness_history']
                        ?? null,

                    'relevant_medical_history' =>
                        $data['relevant_medical_history']
                        ?? null,

                    'physical_examination' =>
                        $data['physical_examination']
                        ?? null,

                    'initial_assessment' =>
                        $data['initial_assessment']
                        ?? null,

                    'notes' =>
                        $data['notes']
                        ?? null,

                    'recorded_at' =>
                        now(),
                ]);
            }
        );

        return redirect()
            ->route(
                'admin.clinical-notes.show',
                $clinicalNote
            )
            ->with(
                'success',
                'Đã thêm nội dung khám thành công.'
            );
    }
}
