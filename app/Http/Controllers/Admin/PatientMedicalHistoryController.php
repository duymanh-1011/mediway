<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MedicalVisit;
use App\Models\VisitStatus;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PatientMedicalHistoryController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'history_q' => ['nullable', 'string', 'max:150'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('date_from') ? ['after_or_equal:date_from'] : [])],
            'visit_status_id' => ['nullable', 'integer', Rule::exists('visit_statuses', 'id')],
        ], [], ['history_q' => 'tìm kiếm lịch sử khám']);

        $visitStatuses = VisitStatus::orderBy('process_order')->get();
        $visits = MedicalVisit::with(['patient', 'doctor', 'specialty', 'examinationRoom', 'status'])
            ->forMedicalHistory($filters)
            ->orderByDesc('checked_in_at')->orderByDesc('id')
            ->paginate(10)->withQueryString();

        return view('admin.patient-medical-history.index', compact('visits', 'visitStatuses'));
    }
}
