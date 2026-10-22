<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateVisitDiagnosisRequest;
use App\Models\AdminLookup;
use App\Models\Disease;
use App\Models\VisitDiagnosis;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class VisitDiagnosisController extends Controller
{
    public function index(Request $request): View
    {
        $query = VisitDiagnosis::query()
            ->with([
                'disease',
                'medicalVisit.patient',
                'medicalVisit.doctor',
                'medicalVisit.status',
            ]);

        if ($request->filled('search')) {
            $search = trim(
                (string) $request->input('search')
            );

            $query->where(function ($q) use ($search) {
                $q->where(
                    'diagnosis_description',
                    'like',
                    "%{$search}%"
                )
                    ->orWhere(
                        'notes',
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
                    )
                    ->orWhereHas(
                        'disease',
                        function ($diseaseQuery) use ($search) {
                            $diseaseQuery->where(
                                'name',
                                'like',
                                "%{$search}%"
                            );
                        }
                    );
            });
        }

        if ($request->filled('disease_id')) {
            $query->where(
                'disease_id',
                $request->integer('disease_id')
            );
        }

        if ($request->filled('diagnosis_type')) {
            $query->where(
                'diagnosis_type',
                $request->input('diagnosis_type')
            );
        }

        if ($request->filled('severity')) {
            $query->where(
                'severity',
                $request->input('severity')
            );
        }

        if ($request->filled('date_from')) {
            $query->whereDate(
                'diagnosed_at',
                '>=',
                $request->input('date_from')
            );
        }

        if ($request->filled('date_to')) {
            $query->whereDate(
                'diagnosed_at',
                '<=',
                $request->input('date_to')
            );
        }

        $diagnoses = $query
            ->orderByDesc('diagnosed_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $diseases = AdminLookup::initial(Disease::query()->orderBy('name'), $request->input('disease_id'));

        $diagnosisTypes = VisitDiagnosis::query()
            ->whereNotNull('diagnosis_type')
            ->where('diagnosis_type', '!=', '')
            ->distinct()
            ->orderBy('diagnosis_type')
            ->pluck('diagnosis_type');

        $severityLevels = VisitDiagnosis::query()
            ->whereNotNull('severity')
            ->where('severity', '!=', '')
            ->distinct()
            ->orderBy('severity')
            ->pluck('severity');

        return view(
            'admin.diagnoses.index',
            compact(
                'diagnoses',
                'diseases',
                'diagnosisTypes',
                'severityLevels'
            )
        );
    }

    public function show(
        VisitDiagnosis $diagnosis
    ): View {
        $diagnosis->load([
            'disease',
            'medicalVisit.appointment',
            'medicalVisit.patient',
            'medicalVisit.doctor',
            'medicalVisit.specialty',
            'medicalVisit.examinationRoom',
            'medicalVisit.status',
        ]);

        return view(
            'admin.diagnoses.show',
            compact('diagnosis')
        );
    }

    public function edit(
        VisitDiagnosis $diagnosis
    ): View {
        $diagnosis->load([
            'medicalVisit.patient',
            'medicalVisit.doctor',
            'medicalVisit.status',
            'disease',
        ]);

        if (! $diagnosis->medicalVisit->canRecordClinicalData()) {
            abort(
                403,
                'Chỉ được sửa hồ sơ khi đang khám hoặc đã quay lại bác sĩ.'
            );
        }

        $diseases = AdminLookup::initial(Disease::query()->orderBy('name'), old('disease_id', $diagnosis->disease_id));

        $diagnosisTypes = VisitDiagnosis::query()
            ->whereNotNull('diagnosis_type')
            ->where('diagnosis_type', '!=', '')
            ->distinct()
            ->orderBy('diagnosis_type')
            ->pluck('diagnosis_type');

        $severityLevels = VisitDiagnosis::query()
            ->whereNotNull('severity')
            ->where('severity', '!=', '')
            ->distinct()
            ->orderBy('severity')
            ->pluck('severity');

        return view(
            'admin.diagnoses.edit',
            compact(
                'diagnosis',
                'diseases',
                'diagnosisTypes',
                'severityLevels'
            )
        );
    }

    public function update(
        UpdateVisitDiagnosisRequest $request,
        VisitDiagnosis $diagnosis
    ): RedirectResponse {
        $diagnosis->load(
            'medicalVisit.status'
        );

        if (! $diagnosis->medicalVisit->canRecordClinicalData()) {
            return back()->with(
                'error',
                'Chỉ được sửa hồ sơ khi đang khám hoặc đã quay lại bác sĩ.'
            );
        }

        $data = $request->validated();

        DB::transaction(function () use ($diagnosis, $data): void {
            $diagnosis->medicalVisit->lockForClinicalWrite();
            $diagnosis->update([
                'disease_id' => $data['disease_id'],

                'diagnosis_type' => $data['diagnosis_type'] ?? null,

                'severity' => $data['severity'] ?? null,

                'diagnosis_description' => $data['diagnosis_description'] ?? null,

                'diagnosed_at' => $data['diagnosed_at']
                    ?? $diagnosis->diagnosed_at
                    ?? now(),

                'notes' => $data['notes'] ?? null,
            ]);
        }, 3);

        return redirect()
            ->route(
                'admin.diagnoses.show',
                $diagnosis
            )
            ->with(
                'success',
                'Cập nhật chẩn đoán thành công.'
            );
    }
}
