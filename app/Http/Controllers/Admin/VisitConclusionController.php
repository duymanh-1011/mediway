<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateVisitConclusionRequest;
use App\Models\Doctor;
use App\Models\VisitConclusion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VisitConclusionController extends Controller
{
    public function index(Request $request): View
    {
        $query = VisitConclusion::query()
            ->with([
                'doctor',
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
                    'professional_conclusion',
                    'like',
                    "%{$search}%"
                )
                    ->orWhere(
                        'disposition',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'follow_up_reason',
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
        | Bác sĩ kết luận
        |--------------------------------------------------------------------------
        */

        if ($request->filled('doctor_id')) {
            $query->where(
                'doctor_id',
                $request->integer('doctor_id')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Hướng xử trí
        |--------------------------------------------------------------------------
        */

        if ($request->filled('disposition')) {
            $query->where(
                'disposition',
                $request->input('disposition')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Có tái khám
        |--------------------------------------------------------------------------
        */

        if (
            $request->filled('has_follow_up')
            &&
            in_array(
                $request->input('has_follow_up'),
                ['0', '1'],
                true
            )
        ) {
            $query->where(
                'has_follow_up',
                (bool) $request->integer('has_follow_up')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Khoảng ngày kết luận
        |--------------------------------------------------------------------------
        */

        if ($request->filled('date_from')) {
            $query->whereDate(
                'concluded_at',
                '>=',
                $request->input('date_from')
            );
        }

        if ($request->filled('date_to')) {
            $query->whereDate(
                'concluded_at',
                '<=',
                $request->input('date_to')
            );
        }

        $conclusions = $query
            ->orderByDesc('concluded_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $doctors = Doctor::query()
            ->orderBy('full_name')
            ->get();

        $dispositions = VisitConclusion::query()
            ->whereNotNull('disposition')
            ->where('disposition', '!=', '')
            ->distinct()
            ->orderBy('disposition')
            ->pluck('disposition');

        return view(
            'admin.conclusions.index',
            compact(
                'conclusions',
                'doctors',
                'dispositions'
            )
        );
    }

    public function show(
        VisitConclusion $conclusion
    ): View {
        $conclusion->load([
            'doctor',
            'medicalVisit.appointment',
            'medicalVisit.patient',
            'medicalVisit.doctor',
            'medicalVisit.specialty',
            'medicalVisit.examinationRoom',
            'medicalVisit.status',
            'medicalVisit.clinicalNote',
            'medicalVisit.diagnoses.disease',
        ]);

        return view(
            'admin.conclusions.show',
            compact('conclusion')
        );
    }

    public function edit(
        VisitConclusion $conclusion
    ): View {
        $conclusion->load([
            'doctor',
            'medicalVisit.patient',
            'medicalVisit.doctor',
            'medicalVisit.status',
        ]);

        if (! $conclusion->medicalVisit->canRecordClinicalData()) {
            abort(
                403,
                'Chưa thể sửa hồ sơ ở bước này. Chỉ được sửa khi đang khám hoặc đang khám lại sau dịch vụ.'
            );
        }

        $doctors = Doctor::query()
            ->where('status', 'ACTIVE')
            ->where(fn ($query) => $query->forSpecialty((int) $conclusion->medicalVisit->specialty_id)->orWhere('doctors.id', $conclusion->doctor_id))
            ->orderBy('full_name')
            ->get();

        $dispositions = VisitConclusion::query()
            ->whereNotNull('disposition')
            ->where('disposition', '!=', '')
            ->distinct()
            ->orderBy('disposition')
            ->pluck('disposition');

        return view(
            'admin.conclusions.edit',
            compact(
                'conclusion',
                'doctors',
                'dispositions'
            )
        );
    }

    public function update(
        UpdateVisitConclusionRequest $request,
        VisitConclusion $conclusion
    ): RedirectResponse {
        $conclusion->load(
            'medicalVisit.status'
        );

        if (! $conclusion->medicalVisit->canRecordClinicalData()) {
            return back()->with(
                'error',
                'Chưa thể sửa hồ sơ ở bước này. Chỉ được sửa khi đang khám hoặc đang khám lại sau dịch vụ.'
            );
        }

        $data = $request->validated();

        /*
        |--------------------------------------------------------------------------
        | Nếu không tái khám thì xóa dữ liệu follow-up
        |--------------------------------------------------------------------------
        */

        $hasFollowUp = (bool) $data['has_follow_up'];

        DB::transaction(function () use ($conclusion, $data, $hasFollowUp): void {
            $conclusion->medicalVisit->lockForClinicalWrite();
            if ((int) $data['doctor_id'] !== (int) $conclusion->doctor_id) {
                $doctor = Doctor::whereKey($data['doctor_id'])->lockForUpdate()->firstOrFail();
                if ($doctor->status !== 'ACTIVE' || ! $doctor->canPracticeSpecialty((int) $conclusion->medicalVisit->specialty_id)) {
                    throw ValidationException::withMessages(['doctor_id' => 'Bác sĩ không thuộc chuyên khoa của lượt khám. Vui lòng chọn lại bác sĩ.']);
                }
            }
            $conclusion->update([
                'doctor_id' => $data['doctor_id'],

                'professional_conclusion' => $data['professional_conclusion'],

                'disposition' => $data['disposition'] ?? null,

                'treatment_plan' => $data['treatment_plan'] ?? null,

                'instructions' => $data['instructions'] ?? null,

                'has_follow_up' => $hasFollowUp,

                'follow_up_date' => $hasFollowUp
                        ? ($data['follow_up_date'] ?? null)
                        : null,

                'follow_up_reason' => $hasFollowUp
                        ? ($data['follow_up_reason'] ?? null)
                        : null,

                'notes' => $data['notes'] ?? null,

                'concluded_at' => $data['concluded_at']
                    ?? $conclusion->concluded_at
                    ?? now(),
            ]);
        }, 3);

        return redirect()
            ->route(
                'admin.conclusions.show',
                $conclusion
            )
            ->with(
                'success',
                'Cập nhật kết luận lượt khám thành công.'
            );
    }
}
