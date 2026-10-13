<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VisitDiagnosisController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('visit_diagnoses')
            ->join('medical_visits', 'visit_diagnoses.medical_visit_id', '=', 'medical_visits.id')
            ->join('patients', 'medical_visits.patient_id', '=', 'patients.id')
            ->join('diseases', 'visit_diagnoses.disease_id', '=', 'diseases.id')
            ->join('doctors', 'medical_visits.doctor_id', '=', 'doctors.id')
            ->select(
                'visit_diagnoses.*',
                'medical_visits.visit_code',
                'patients.patient_code',
                'patients.full_name as patient_name',
                'diseases.disease_code',
                'diseases.name as disease_name',
                'doctors.full_name as doctor_name'
            );

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('medical_visits.visit_code', 'like', "%{$search}%")
                  ->orWhere('patients.full_name', 'like', "%{$search}%")
                  ->orWhere('diseases.name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('diagnosis_type')) {
            $query->where('visit_diagnoses.diagnosis_type', $request->diagnosis_type);
        }

        $diagnoses = $query->orderBy('visit_diagnoses.diagnosed_at', 'desc')->paginate(10)->withQueryString();

        return view('admin.diagnoses.index', compact('diagnoses'));
    }

    public function store(Request $request, $visitId)
    {
        $request->validate([
            'disease_id' => 'required|exists:diseases,id',
            'diagnosis_type' => 'required|in:PRIMARY,SECONDARY,PRELIMINARY,CONFIRMED',
        ]);

        DB::table('visit_diagnoses')->insert([
            'medical_visit_id' => $visitId,
            'disease_id' => $request->disease_id,
            'diagnosis_type' => $request->diagnosis_type,
            'severity' => $request->severity,
            'diagnosis_description' => $request->diagnosis_description,
            'diagnosed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return back()->with('success', 'Thêm chẩn đoán thành công.');
    }

    public function destroy($id)
    {
        DB::table('visit_diagnoses')->where('id', $id)->delete();
        return back()->with('success', 'Đã xóa chẩn đoán.');
    }
}
