<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Doctor\SaveConclusionRequest;
use App\Models\AuditLog;
use App\Models\ClinicalTimeline;
use App\Models\MedicalVisit;
use App\Services\DoctorClinicalVersion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConclusionController extends Controller
{
    public function save(SaveConclusionRequest $request, int $visit): RedirectResponse
    {
        $data = $request->validated();
        $version = $request->validate(['record_version' => ['required', 'string', 'size:64']])['record_version'];
        DB::transaction(function () use ($request, $visit, $data, $version): void {
            // Kiểm tra phân công và trạng thái trong giao dịch trước khi ghi hồ sơ.
            $record = MedicalVisit::whereKey($visit)->where('doctor_id', $request->user()->doctor->id)
                ->lockForUpdate()->firstOrFail()->lockForClinicalWrite();
            $conclusion = $record->conclusion()->lockForUpdate()->first();
            if (! hash_equals(DoctorClinicalVersion::of($conclusion), $version)) {
                throw ValidationException::withMessages(['record_version' => 'Kết luận đã thay đổi. Vui lòng tải lại và kiểm tra trước khi lưu.']);
            }
            $followUp = (bool) $data['has_follow_up'];
            $at = $conclusion?->concluded_at ?? now();
            ClinicalTimeline::assertFollowUp($followUp, $data['follow_up_date'] ?? null, $at);
            $values = [
                'professional_conclusion' => $data['professional_conclusion'],
                'disposition' => $data['disposition'] ?? null, 'treatment_plan' => $data['treatment_plan'] ?? null,
                'instructions' => $data['instructions'] ?? null, 'has_follow_up' => $followUp,
                'follow_up_date' => $followUp ? ($data['follow_up_date'] ?? null) : null,
                'follow_up_reason' => $followUp ? ($data['follow_up_reason'] ?? null) : null,
                'notes' => $data['notes'] ?? null,
            ];
            if ($conclusion) {
                $conclusion->update($values);
                $action = 'UPDATE';
            } else {
                $conclusion = $record->conclusion()->create([...$values, 'doctor_id' => $record->doctor_id, 'concluded_at' => now()]);
                $action = 'CREATE';
            }
            // Ghi người thao tác; nội dung bệnh án không sao chép vào nhật ký.
            AuditLog::create(['user_id' => $request->user()->id, 'role_name_snapshot' => 'Doctor', 'action' => $action,
                'entity_type' => 'visit_conclusions', 'entity_id' => $conclusion->id, 'entity_business_code' => $record->visit_code,
                'description' => 'Bác sĩ lưu kết luận lượt khám.', 'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500), 'performed_at' => now()]);
        }, 3);
        return redirect()->route('doctor.visits.show', $visit)->with('success', 'Đã lưu kết luận.');
    }
}
