<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Admin\PrescriptionController as AdminPrescriptionController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePrescriptionRequest;
use App\Models\MedicalVisit;
use App\Models\Medicine;
use App\Models\AuditLog;
use App\Models\Payable;
use App\Services\DoctorClinicalVersion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PrescriptionController extends Controller
{
    public function create(Request $request, int $visit): View|RedirectResponse
    {
        $doctor = $request->user()->doctor;
        $record = MedicalVisit::whereKey($visit)->where('doctor_id', $doctor->id)
            ->with(['patient', 'status', 'appointment', 'prescription'])->firstOrFail();
        if (! $record->canRecordClinicalData() || $record->prescription) {
            return redirect()->route('doctor.visits.show', $record)->withErrors(['prescription' => 'Chỉ có thể tạo một đơn thuốc khi lượt khám đang được phép ghi hồ sơ.']);
        }
        $medicines = Medicine::where('status', 'ACTIVE')->orderBy('name')->get(['id', 'medicine_code', 'name', 'strength', 'unit', 'price_per_unit', 'price_per_blister', 'price_per_box']);
        return view('doctor.visits.prescription-create', compact('doctor', 'record', 'medicines'));
    }

    public function store(StorePrescriptionRequest $request, int $visit, AdminPrescriptionController $admin): RedirectResponse
    {
        DB::transaction(function () use ($request, $visit, $admin): void {
            // Khóa lượt được phân công trước khi gọi chung nghiệp vụ tạo đơn hiện có.
            $record = MedicalVisit::whereKey($visit)->where('doctor_id', $request->user()->doctor->id)
                ->lockForUpdate()->firstOrFail();
            $record = $record->lockForClinicalWrite();
            if ($record->prescription()->exists()) {
                throw ValidationException::withMessages(['prescription' => 'Lượt khám này đã có đơn thuốc.']);
            }
            // Admin xử lý giá tại thời điểm kê đơn, thuốc trùng và ghi nhật ký trong cùng giao dịch.
            $admin->store($request, $record);
        }, 3);
        return redirect()->route('doctor.visits.show', $visit)->with('success', 'Đã lưu đơn thuốc.');
    }

    public function print(Request $request, int $visit)
    {
        // Kiểm tra phân công trước khi truy vấn đơn và thông tin bệnh nhân.
        $record = MedicalVisit::whereKey($visit)->where('doctor_id', $request->user()->doctor->id)
            ->with(['patient.user', 'patient.detail', 'examinationRoom', 'diagnoses.disease', 'prescription.doctor', 'prescription.items.medicine'])
            ->firstOrFail();
        $prescription = $record->prescription;
        abort_unless($prescription, 404);
        $prescription->setRelation('medicalVisit', $record);
        $patient = $record->patient;
        $phone = $patient?->user?->phone;
        $backUrl = route('doctor.visits.show', $record);

        // Dùng chung mẫu in với bệnh nhân, không cung cấp đường tải tệp thuộc quyền bệnh nhân.
        return response()->view('portal.clinical.prescription-document', [
            'record' => $prescription, 'patient' => $patient, 'phone' => $phone,
            'backUrl' => $backUrl, 'doctorPrint' => true,
        ])->header('Cache-Control', 'private, no-store');
    }

    public function update(Request $request, int $visit): RedirectResponse
    {
        $data = $request->validate([
            'record_version' => ['required', 'string', 'size:64'],
            'action' => ['required', 'in:save,cancel'],
            'general_instructions' => ['nullable', 'string', 'max:5000'],
            'doctor_notes' => ['nullable', 'string', 'max:5000'],
        ]);
        DB::transaction(function () use ($request, $visit, $data): void {
            // Cùng thứ tự khóa với luồng tạo khoản thu để tránh sửa đồng thời.
            $record = MedicalVisit::whereKey($visit)->where('doctor_id', $request->user()->doctor->id)
                ->lockForUpdate()->firstOrFail()->lockForClinicalWrite();
            $prescription = $record->prescription()->lockForUpdate()->firstOrFail();
            if (! hash_equals(DoctorClinicalVersion::of($prescription), $data['record_version'])) {
                throw ValidationException::withMessages(['record_version' => 'Đơn thuốc đã thay đổi. Vui lòng tải lại trước khi lưu.']);
            }
            if ($prescription->status !== 'ACTIVE') {
                throw ValidationException::withMessages(['prescription' => 'Đơn thuốc không còn hiệu lực để chỉnh sửa.']);
            }
            if ($data['action'] === 'cancel') {
                $hasPayable = Payable::where('prescription_id', $prescription->id)
                    ->whereDoesntHave('paymentStatus', fn ($status) => $status->where('name', 'CANCELLED'))->exists();
                if ($hasPayable) {
                    throw ValidationException::withMessages(['prescription' => 'Đơn thuốc đã có khoản thu. Vui lòng đối chiếu khoản thu trước khi hủy đơn.']);
                }
                $prescription->update(['status' => 'CANCELLED']);
            } else {
                // Phần Admin hiện chỉ hỗ trợ sửa lời dặn, không sửa trực tiếp danh sách thuốc.
                $prescription->update(['general_instructions' => $data['general_instructions'] ?? null, 'doctor_notes' => $data['doctor_notes'] ?? null]);
            }
            AuditLog::create(['user_id' => $request->user()->id, 'role_name_snapshot' => 'Doctor', 'action' => 'UPDATE',
                'entity_type' => 'prescriptions', 'entity_id' => $prescription->id, 'entity_business_code' => $prescription->prescription_code,
                'description' => $data['action'] === 'cancel' ? 'Bác sĩ hủy đơn thuốc.' : 'Bác sĩ cập nhật lời dặn đơn thuốc.',
                'ip_address' => $request->ip(), 'user_agent' => mb_substr((string) $request->userAgent(), 0, 500), 'performed_at' => now()]);
        }, 3);
        return redirect()->route('doctor.visits.show', $visit)->with('success', $data['action'] === 'cancel' ? 'Đã hủy đơn thuốc.' : 'Đã cập nhật lời dặn.');
    }
}
