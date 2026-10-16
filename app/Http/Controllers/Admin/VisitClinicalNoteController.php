<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VisitClinicalNoteController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('visit_clinical_notes')
            ->join('medical_visits', 'visit_clinical_notes.medical_visit_id', '=', 'medical_visits.id')
            ->join('patients', 'medical_visits.patient_id', '=', 'patients.id')
            ->join('doctors', 'medical_visits.doctor_id', '=', 'doctors.id')
            ->select(
                'visit_clinical_notes.*',
                'medical_visits.visit_code',
                'patients.patient_code',
                'patients.full_name as patient_name',
                'doctors.full_name as doctor_name'
            );

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('medical_visits.visit_code', 'like', "%{$search}%")
                  ->orWhere('patients.full_name', 'like', "%{$search}%")
                  ->orWhere('visit_clinical_notes.reason_for_visit', 'like', "%{$search}%");
            });
        }

        $notes = $query->orderBy('visit_clinical_notes.recorded_at', 'desc')->paginate(10)->withQueryString();

        return view('admin.clinical-notes.index', compact('notes'));
    }

    public function storeOrUpdate(Request $request, $visitId)
    {
        DB::table('visit_clinical_notes')->updateOrInsert(
            ['medical_visit_id' => $visitId],
            [
                'reason_for_visit' => $request->reason_for_visit,
                'current_symptoms' => $request->current_symptoms,
                'present_illness_history' => $request->present_illness_history,
                'relevant_medical_history' => $request->relevant_medical_history,
                'physical_examination' => $request->physical_examination,
                'initial_assessment' => $request->initial_assessment,
                'recorded_at' => now(),
                'updated_at' => now(),
            ]
        );
        return back()->with('success', 'Cập nhật nội dung khám lâm sàng thành công.');
    }
}
