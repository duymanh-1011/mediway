<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVisitClinicalNoteRequest;
use App\Http\Requests\Admin\StoreVisitDiagnosisRequest;
use App\Http\Requests\Doctor\UpdateDiagnosisRequest;
use App\Models\AuditLog;
use App\Models\Disease;
use App\Models\MedicalVisit;
use App\Services\DoctorClinicalVersion;
use App\Services\DoctorPrescriptionLock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClinicalController extends Controller
{
    public function saveNote(StoreVisitClinicalNoteRequest $request, int $visit): RedirectResponse
    {
        $data = $request->validated();
        $version = $request->validate(['record_version' => ['required', 'string', 'size:64']])['record_version'];
        DB::transaction(function () use ($request, $visit, $data, $version): void {
            $record = $this->lockedVisit($request, $visit);
            $note = $record->clinicalNote()->lockForUpdate()->first();
            $this->assertVersion($note, $version);
            if (! collect($data)->contains(fn ($value) => is_string($value) && trim($value) !== '')) {
                throw ValidationException::withMessages(['clinical_note' => 'Vui lòng nhập ít nhất một nội dung khám.']);
            }
            // Giữ thời điểm ghi ban đầu khi chỉnh sửa, không tạo bản khám thứ hai.
            if ($note) {
                $note->update($data);
                $this->audit($request, $record, 'UPDATE', 'visit_clinical_notes', $note->id);
            } else {
                $note = $record->clinicalNote()->create([...$data, 'recorded_at' => now()]);
                $this->audit($request, $record, 'CREATE', 'visit_clinical_notes', $note->id);
            }
        }, 3);
        return redirect()->route('doctor.visits.show', $visit)->with('success', 'Đã lưu nội dung khám.');
    }

    public function storeDiagnosis(StoreVisitDiagnosisRequest $request, int $visit): RedirectResponse
    {
        $data = $request->validated();
        DB::transaction(function () use ($request, $visit, $data): void {
            $record = $this->lockedVisit($request, $visit);
            $this->ensureDiseaseAndUnique($record, $data);
            $item = $record->diagnoses()->create([...$data, 'diagnosed_at' => now()]);
            $this->audit($request, $record, 'CREATE', 'visit_diagnoses', $item->id);
        }, 3);
        return redirect()->route('doctor.visits.show', $visit)->with('success', 'Đã thêm chẩn đoán.');
    }

    public function updateDiagnosis(UpdateDiagnosisRequest $request, int $visit, int $diagnosis): RedirectResponse
    {
        $data = $request->validated();
        $version = $data['record_version'];
        unset($data['record_version']);
        DB::transaction(function () use ($request, $visit, $diagnosis, $data, $version): void {
            $record = $this->lockedVisit($request, $visit);
            // Chẩn đoán phải thuộc đúng lượt khám, không chỉ đúng ID.
            $item = $record->diagnoses()->whereKey($diagnosis)->lockForUpdate()->firstOrFail();
            $this->assertVersion($item, $version);
            $this->ensureDiseaseAndUnique($record, $data, $item->id, $item->disease_id);
            $item->update($data);
            $this->audit($request, $record, 'UPDATE', 'visit_diagnoses', $item->id);
        }, 3);
        return redirect()->route('doctor.visits.show', $visit)->with('success', 'Đã cập nhật chẩn đoán.');
    }

    private function lockedVisit(Request $request, int $id): MedicalVisit
    {
        // Khóa lượt trước khi kiểm tra trạng thái để tránh ghi đè đồng thời.
        $record = MedicalVisit::whereKey($id)->lockForUpdate()->firstOrFail();
        $record = $record->lockForClinicalWrite();
        // Cùng khóa lượt khám với tạo đơn để kiểm tra lại tại thời điểm ghi.
        DoctorPrescriptionLock::assertUnlocked($record);
        return $record;
    }

    private function assertVersion($record, string $version): void
    {
        if (! hash_equals(DoctorClinicalVersion::of($record), $version)) {
            throw ValidationException::withMessages(['record_version' => 'Nội dung đã thay đổi. Vui lòng tải lại và kiểm tra trước khi lưu.']);
        }
    }

    private function ensureDiseaseAndUnique(MedicalVisit $visit, array $data, ?int $ignore = null, ?int $previousDisease = null): void
    {
        $disease = Disease::whereKey($data['disease_id'])->lockForUpdate()->firstOrFail();
        if ($disease->status !== 'ACTIVE' && (int) $previousDisease !== (int) $disease->id) {
            throw ValidationException::withMessages(['disease_id' => 'Bệnh đã ngừng hoạt động. Vui lòng chọn bệnh khác.']);
        }
        if ($visit->diagnoses()->where('disease_id', $disease->id)->where('diagnosis_type', $data['diagnosis_type'])
            ->when($ignore, fn ($q) => $q->whereKeyNot($ignore))->exists()) {
            throw ValidationException::withMessages(['diagnosis_type' => 'Bệnh này đã có chẩn đoán cùng loại trong lượt khám.']);
        }
    }

    private function audit(Request $request, MedicalVisit $visit, string $action, string $entity, int $id): void
    {
        // Ghi nhận người thao tác và lượt khám; không chép nội dung nhạy cảm vào nhật ký.
        AuditLog::create([
            'user_id' => $request->user()->id, 'role_name_snapshot' => 'Doctor', 'action' => $action,
            'entity_type' => $entity, 'entity_id' => $id, 'entity_business_code' => $visit->visit_code,
            'description' => 'Bác sĩ cập nhật hồ sơ lượt khám.', 'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500), 'performed_at' => now(),
        ]);
    }
}
