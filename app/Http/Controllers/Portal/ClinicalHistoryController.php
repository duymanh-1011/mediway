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

        // Lấy chỉ số theo thời gian ghi nhận, mới nhất trước; không lấy ghi chú nội bộ.
        // withQueryString giữ các tham số trang còn lại khi chuyển trang.
        $healthRecords = $patient->healthRecords()
            ->select([
                'id', 'patient_id', 'height_cm', 'weight_kg', 'bmi',
                'systolic_blood_pressure', 'diastolic_blood_pressure', 'heart_rate',
                'body_temperature', 'spo2', 'respiratory_rate', 'blood_glucose', 'recorded_at',
            ])
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'health_page')->withQueryString();

        return view('portal.clinical.history', compact('healthRecords'));
    }
}
