<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVisitDiagnosisRequest;
use App\Models\Disease;
use App\Models\MedicalVisit;
use App\Models\VisitDiagnosis;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MedicalVisitDiagnosisController extends Controller
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
        ]);

        if ($visit->status?->name === 'COMPLETED') {
            return redirect()
                ->route('admin.visits.show', $visit)
                ->with(
                    'error',
                    'Không thể thêm chẩn đoán cho lượt khám đã hoàn tất.'
                );
        }

        $diseases = Disease::query()
            ->where('status', 'ACTIVE')
            ->orderBy('name')
            ->get([
                'id',
                'disease_code',
                'name',
            ]);

        $diagnosisTypes = [
            'PRIMARY' => 'Chẩn đoán chính',
            'SECONDARY' => 'Chẩn đoán phụ',
            'PRELIMINARY' => 'Chẩn đoán sơ bộ',
            'CONFIRMED' => 'Chẩn đoán xác định',
        ];

        $severityOptions = VisitDiagnosis::query()
            ->whereNotNull('severity')
            ->where('severity', '!=', '')
            ->distinct()
            ->orderBy('severity')
            ->pluck('severity');

        return view(
            'admin.diagnoses.create',
            compact(
                'visit',
                'diseases',
                'diagnosisTypes',
                'severityOptions'
            )
        );
    }

    public function store(
        StoreVisitDiagnosisRequest $request,
        MedicalVisit $visit
    ): RedirectResponse {
        $visit->load('status');

        if ($visit->status?->name === 'COMPLETED') {
            return redirect()
                ->route('admin.visits.show', $visit)
                ->with(
                    'error',
                    'Không thể thêm chẩn đoán cho lượt khám đã hoàn tất.'
                );
        }

        $data = $request->validated();

        $duplicateExists = VisitDiagnosis::query()
            ->where('medical_visit_id', $visit->id)
            ->where('disease_id', $data['disease_id'])
            ->where('diagnosis_type', $data['diagnosis_type'])
            ->exists();

        if ($duplicateExists) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Lượt khám đã có chẩn đoán cùng bệnh và cùng loại chẩn đoán.'
                );
        }

        $diagnosis = DB::transaction(
            function () use (
                $visit,
                $data
            ) {
                return VisitDiagnosis::create([
                    'medical_visit_id' =>
                        $visit->id,

                    'disease_id' =>
                        $data['disease_id'],

                    'diagnosis_type' =>
                        $data['diagnosis_type'],

                    'severity' =>
                        $data['severity']
                        ?? null,

                    'diagnosis_description' =>
                        $data['diagnosis_description']
                        ?? null,

                    'diagnosed_at' =>
                        now(),

                    'notes' =>
                        $data['notes']
                        ?? null,
                ]);
            }
        );

        return redirect()
            ->route(
                'admin.diagnoses.show',
                $diagnosis
            )
            ->with(
                'success',
                'Đã thêm chẩn đoán thành công.'
            );
    }
}
