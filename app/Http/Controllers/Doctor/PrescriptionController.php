<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Admin\PrescriptionController as AdminPrescriptionController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePrescriptionRequest;
use App\Models\MedicalVisit;
use App\Models\Medicine;
use App\Services\DoctorPrescriptionLock;
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
        $record = MedicalVisit::whereKey($visit)
            ->with(['patient', 'status', 'appointment', 'prescription'])->firstOrFail();
        if ($record->conclusion()->exists()) {
            return redirect()->route('doctor.visits.show', $record)->withErrors(['conclusion_lock' => DoctorPrescriptionLock::CONCLUSION_MESSAGE]);
        }
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
            $record = MedicalVisit::whereKey($visit)
                ->lockForUpdate()->firstOrFail();
            $record = $record->lockForClinicalWrite();
            // Kết luận và đơn dùng cùng khóa lượt để tránh lưu song song sai thứ tự.
            DoctorPrescriptionLock::assertConclusionUnlocked($record);
            if ($record->prescription()->exists()) {
                throw ValidationException::withMessages(['prescription' => 'Lượt khám này đã có đơn thuốc.']);
            }
            // Admin xử lý giá tại thời điểm kê đơn, thuốc trùng và ghi nhật ký trong cùng giao dịch.
            $admin->store($request, $record, $request->user()->doctor->id);
        }, 3);
        return redirect()->route('doctor.visits.show', $visit)->with('success', 'Đã lưu đơn thuốc.');
    }

    public function print(Request $request, int $visit)
    {
        // Lấy đúng lượt khám trước khi truy vấn đơn và thông tin bệnh nhân.
        $record = MedicalVisit::whereKey($visit)
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

}
