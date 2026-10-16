<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVisitConclusionRequest;
use App\Models\MedicalVisit;
use App\Models\VisitConclusion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MedicalVisitConclusionController extends Controller
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
            'conclusion',
        ]);

        if ($visit->conclusion) {
            return redirect()
                ->route(
                    'admin.conclusions.show',
                    $visit->conclusion
                )
                ->with(
                    'error',
                    'Lượt khám này đã có kết luận.'
                );
        }

        if ($visit->status?->name === 'COMPLETED') {
            return redirect()
                ->route(
                    'admin.visits.show',
                    $visit
                )
                ->with(
                    'error',
                    'Không thể thêm kết luận cho lượt khám đã hoàn tất.'
                );
        }

        $dispositionOptions = VisitConclusion::query()
            ->whereNotNull('disposition')
            ->where('disposition', '!=', '')
            ->distinct()
            ->orderBy('disposition')
            ->pluck('disposition');

        return view(
            'admin.conclusions.create',
            compact(
                'visit',
                'dispositionOptions'
            )
        );
    }

    public function store(
        StoreVisitConclusionRequest $request,
        MedicalVisit $visit
    ): RedirectResponse {
        $visit->load([
            'status',
            'conclusion',
        ]);

        if ($visit->conclusion) {
            return redirect()
                ->route(
                    'admin.conclusions.show',
                    $visit->conclusion
                )
                ->with(
                    'error',
                    'Lượt khám này đã có kết luận.'
                );
        }

        if ($visit->status?->name === 'COMPLETED') {
            return redirect()
                ->route(
                    'admin.visits.show',
                    $visit
                )
                ->with(
                    'error',
                    'Không thể thêm kết luận cho lượt khám đã hoàn tất.'
                );
        }

        $data = $request->validated();

        $hasFollowUp = (bool) $data['has_follow_up'];

        $conclusion = DB::transaction(
            function () use (
                $visit,
                $data,
                $hasFollowUp
            ) {
                return VisitConclusion::create([
                    'medical_visit_id' =>
                        $visit->id,

                    'doctor_id' =>
                        $visit->doctor_id,

                    'professional_conclusion' =>
                        $data['professional_conclusion'],

                    'disposition' =>
                        $data['disposition']
                        ?? null,

                    'treatment_plan' =>
                        $data['treatment_plan']
                        ?? null,

                    'instructions' =>
                        $data['instructions']
                        ?? null,

                    'has_follow_up' =>
                        $hasFollowUp,

                    'follow_up_date' =>
                        $hasFollowUp
                            ? ($data['follow_up_date'] ?? null)
                            : null,

                    'follow_up_reason' =>
                        $hasFollowUp
                            ? ($data['follow_up_reason'] ?? null)
                            : null,

                    'notes' =>
                        $data['notes']
                        ?? null,

                    'concluded_at' =>
                        now(),
                ]);
            }
        );

        return redirect()
            ->route(
                'admin.conclusions.show',
                $conclusion
            )
            ->with(
                'success',
                'Đã thêm kết luận lượt khám thành công.'
            );
    }
}
