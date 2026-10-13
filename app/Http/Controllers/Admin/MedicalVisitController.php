<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MedicalVisitController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('medical_visits')
            ->join('patients', 'medical_visits.patient_id', '=', 'patients.id')
            ->join('doctors', 'medical_visits.doctor_id', '=', 'doctors.id')
            ->join('specialties', 'medical_visits.specialty_id', '=', 'specialties.id')
            ->join('examination_rooms', 'medical_visits.examination_room_id', '=', 'examination_rooms.id')
            ->join('visit_statuses', 'medical_visits.visit_status_id', '=', 'visit_statuses.id')
            ->select(
                'medical_visits.*',
                'patients.patient_code',
                'patients.full_name as patient_name',
                'patients.gender as patient_gender',
                'doctors.full_name as doctor_name',
                'specialties.name as specialty_name',
                'examination_rooms.name as room_name',
                'visit_statuses.name as status_name'
            );

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('medical_visits.visit_code', 'like', "%{$search}%")
                  ->orWhere('patients.patient_code', 'like', "%{$search}%")
                  ->orWhere('patients.full_name', 'like', "%{$search}%");
            });
        }

        $date = $request->input('date', '2026-10-15');
        $query->whereDate('medical_visits.checked_in_at', $date);

        if ($request->filled('visit_status_id')) {
            $query->where('medical_visits.visit_status_id', $request->visit_status_id);
        }

        if ($request->filled('doctor_id')) {
            $query->where('medical_visits.doctor_id', $request->doctor_id);
        }

        $visits = $query->orderBy('medical_visits.checked_in_at', 'desc')->paginate(10)->withQueryString();
        $statuses = DB::table('visit_statuses')->where('is_active', true)->orderBy('process_order')->get();
        $doctors = DB::table('doctors')->where('status', 'ACTIVE')->select('id', 'full_name', 'doctor_code')->get();

        return view('admin.visits.index', compact('visits', 'statuses', 'doctors', 'date'));
    }

    public function show($id)
    {
        $visit = DB::table('medical_visits')
            ->join('patients', 'medical_visits.patient_id', '=', 'patients.id')
            ->leftJoin('patient_details', 'patients.id', '=', 'patient_details.patient_id')
            ->join('doctors', 'medical_visits.doctor_id', '=', 'doctors.id')
            ->join('specialties', 'medical_visits.specialty_id', '=', 'specialties.id')
            ->join('examination_rooms', 'medical_visits.examination_room_id', '=', 'examination_rooms.id')
            ->join('visit_statuses', 'medical_visits.visit_status_id', '=', 'visit_statuses.id')
            ->leftJoin('appointments', 'medical_visits.appointment_id', '=', 'appointments.id')
            ->select(
                'medical_visits.*',
                'patients.patient_code', 'patients.full_name as patient_name', 'patients.date_of_birth', 'patients.gender',
                'patient_details.citizen_id_number', 'patient_details.email', 'patient_details.blood_type',
                'doctors.full_name as doctor_name', 'doctors.doctor_code',
                'specialties.name as specialty_name',
                'examination_rooms.name as room_name', 'examination_rooms.room_number',
                'visit_statuses.name as status_name',
                'appointments.appointment_code', 'appointments.reason_for_visit as booked_reason'
            )
            ->where('medical_visits.id', $id)
            ->first();

        if (!$visit) {
            return redirect()->route('admin.visits.index')->with('error', 'Không tìm thấy lượt khám.');
        }

        $clinicalNote = DB::table('visit_clinical_notes')->where('medical_visit_id', $id)->first();
        $diagnoses = DB::table('visit_diagnoses')
            ->join('diseases', 'visit_diagnoses.disease_id', '=', 'diseases.id')
            ->select('visit_diagnoses.*', 'diseases.disease_code', 'diseases.name as disease_name')
            ->where('visit_diagnoses.medical_visit_id', $id)
            ->get();
        $queues = DB::table('examination_room_queues')
            ->join('queue_statuses', 'examination_room_queues.queue_status_id', '=', 'queue_statuses.id')
            ->select('examination_room_queues.*', 'queue_statuses.name as status_name')
            ->where('examination_room_queues.medical_visit_id', $id)
            ->get();
        $conclusion = DB::table('visit_conclusions')
            ->leftJoin('doctors', 'visit_conclusions.doctor_id', '=', 'doctors.id')
            ->select('visit_conclusions.*', 'doctors.full_name as doctor_name')
            ->where('visit_conclusions.medical_visit_id', $id)
            ->first();

        $diseases = DB::table('diseases')->where('status', 'ACTIVE')->select('id', 'disease_code', 'name')->get();
        $serviceOrders = DB::table('service_orders')->where('medical_visit_id', $id)->get();
        $prescription = DB::table('prescriptions')->where('medical_visit_id', $id)->first();
        $payables = DB::table('payables')
            ->join('payment_statuses', 'payables.payment_status_id', '=', 'payment_statuses.id')
            ->select('payables.*', 'payment_statuses.name as status_name')
            ->where('payables.medical_visit_id', $id)
            ->get();

        return view('admin.visits.show', compact('visit', 'clinicalNote', 'diagnoses', 'queues', 'conclusion', 'diseases', 'serviceOrders', 'prescription', 'payables'));
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate(['visit_status_id' => 'required|exists:visit_statuses,id']);
        DB::table('medical_visits')->where('id', $id)->update([
            'visit_status_id' => $request->visit_status_id,
            'updated_at' => now(),
        ]);
        return back()->with('success', 'Cập nhật trạng thái lượt khám thành công.');
    }
}
