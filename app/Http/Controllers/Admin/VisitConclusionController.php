<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VisitConclusionController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('visit_conclusions')
            ->join('medical_visits', 'visit_conclusions.medical_visit_id', '=', 'medical_visits.id')
            ->join('patients', 'medical_visits.patient_id', '=', 'patients.id')
            ->join('doctors', 'visit_conclusions.doctor_id', '=', 'doctors.id')
            ->select(
                'visit_conclusions.*',
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
                  ->orWhere('visit_conclusions.professional_conclusion', 'like', "%{$search}%");
            });
        }

        $conclusions = $query->orderBy('visit_conclusions.concluded_at', 'desc')->paginate(10)->withQueryString();

        return view('admin.conclusions.index', compact('conclusions'));
    }

    public function storeOrUpdate(Request $request, $visitId)
    {
        $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
            'professional_conclusion' => 'required|string',
            'has_follow_up' => 'required|boolean',
        ]);

        DB::transaction(function () use ($request, $visitId) {
            DB::table('visit_conclusions')->updateOrInsert(
                ['medical_visit_id' => $visitId],
                [
                    'doctor_id' => $request->doctor_id,
                    'professional_conclusion' => $request->professional_conclusion,
                    'disposition' => $request->disposition,
                    'treatment_plan' => $request->treatment_plan,
                    'instructions' => $request->instructions,
                    'has_follow_up' => $request->has_follow_up,
                    'follow_up_date' => $request->has_follow_up ? $request->follow_up_date : null,
                    'concluded_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $completedStatus = DB::table('visit_statuses')->where('name', 'COMPLETED')->first();
            if ($completedStatus) {
                DB::table('medical_visits')->where('id', $visitId)->update([
                    'visit_status_id' => $completedStatus->id,
                    'examination_ended_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        return back()->with('success', 'Đã lưu kết luận và hoàn tất lượt khám.');
    }
}
