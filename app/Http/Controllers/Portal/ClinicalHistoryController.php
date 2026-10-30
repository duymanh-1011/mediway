<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ClinicalHistoryController extends Controller
{
    public function index(Request $request)
    {
        // Chỉ lấy hồ sơ gắn với tài khoản bệnh nhân hiện tại.
        $patient = $request->user()->patient;
        abort_unless($patient && $patient->status === 'ACTIVE', 404);

        // Chỉ đưa lượt khám đã hoàn tất vào lịch sử, kèm bác sĩ, chẩn đoán và kết luận.
        // visits_page là tham số phân trang riêng cho danh sách lượt khám.
        $visits = $patient->medicalVisits()
            ->select(['id', 'patient_id', 'visit_code', 'doctor_id', 'specialty_id', 'visit_status_id', 'checked_in_at', 'examination_ended_at'])
            ->whereHas('status', fn ($status) => $status->where('name', 'COMPLETED'))
            ->with([
                'doctor:id,full_name',
                'specialty:id,name',
                'status:id,name,description',
                'diagnoses:id,medical_visit_id,disease_id,diagnosis_type,severity,diagnosis_description,diagnosed_at',
                'diagnoses.disease:id,name',
                'conclusion:id,medical_visit_id,professional_conclusion,disposition,treatment_plan,instructions,has_follow_up,follow_up_date,follow_up_reason',
            ])
            ->orderByDesc('checked_in_at')
            ->paginate(10, ['*'], 'visits_page')->withQueryString();

        // Lấy chỉ số theo thời gian ghi nhận, mới nhất trước; không lấy ghi chú nội bộ.
        // withQueryString giữ các tham số trang còn lại khi chuyển trang.
        $healthRecords = $patient->healthRecords()
            ->select([
                'id', 'patient_id', 'height_cm', 'weight_kg', 'bmi',
                'systolic_blood_pressure', 'diastolic_blood_pressure', 'heart_rate',
                'body_temperature', 'spo2', 'respiratory_rate', 'blood_glucose', 'recorded_at',
            ])
            ->orderByDesc('recorded_at')
            ->paginate(10, ['*'], 'health_page')->withQueryString();

        // Truyền hai danh sách sang giao diện hồ sơ sức khỏe.
        return view('portal.clinical.history', compact('visits', 'healthRecords'));
    }
}
