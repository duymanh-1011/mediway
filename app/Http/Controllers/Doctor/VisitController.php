<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\MedicalVisit;
use App\Models\Disease;
use App\Services\DoctorVisitReadiness;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VisitController extends Controller
{
    public function index(Request $request): View
    {
        $doctor = $request->user()->doctor;
        // Chỉ truy vấn lượt khám được phân công trực tiếp cho bác sĩ đăng nhập.
        $visits = MedicalVisit::query()->where('doctor_id', $doctor->id)
            ->with(['patient', 'specialty', 'examinationRoom', 'status'])
            ->orderByDesc('checked_in_at')->orderByDesc('id')->paginate(10);
        return view('doctor.visits.index', compact('doctor', 'visits'));
    }

    public function show(Request $request, int $visit, DoctorVisitReadiness $readiness): View
    {
        $doctor = $request->user()->doctor;
        // Ràng buộc quyền ngay trong truy vấn, kể cả khi tự nhập địa chỉ URL.
        $record = MedicalVisit::query()->where('doctor_id', $doctor->id)
            ->with(['patient', 'specialty', 'examinationRoom', 'status', 'appointment', 'clinicalNote',
                'diagnoses.disease', 'conclusion', 'prescription.items.medicine',
                'serviceOrders.items.medicalService', 'serviceOrders.items.serviceStatus', 'serviceOrders.items.result'])
            ->findOrFail($visit);
        $check = $readiness->check($record);
        $canEdit = $record->canRecordClinicalData();
        $diseases = $canEdit ? Disease::where('status', 'ACTIVE')->orderBy('name')->get(['id', 'name', 'disease_code']) : collect();
        return view('doctor.visits.show', compact('doctor', 'record', 'check', 'canEdit', 'diseases'));
    }
}
