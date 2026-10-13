<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExaminationRoomQueueController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('examination_room_queues')
            ->join('medical_visits', 'examination_room_queues.medical_visit_id', '=', 'medical_visits.id')
            ->join('patients', 'medical_visits.patient_id', '=', 'patients.id')
            ->join('doctors', 'medical_visits.doctor_id', '=', 'doctors.id')
            ->join('examination_rooms', 'medical_visits.examination_room_id', '=', 'examination_rooms.id')
            ->join('queue_statuses', 'examination_room_queues.queue_status_id', '=', 'queue_statuses.id')
            ->select(
                'examination_room_queues.*',
                'medical_visits.visit_code',
                'patients.patient_code',
                'patients.full_name as patient_name',
                'doctors.full_name as doctor_name',
                'examination_rooms.name as room_name',
                'queue_statuses.name as status_name'
            );

        if ($request->filled('room_id')) {
            $query->where('medical_visits.examination_room_id', $request->room_id);
        }
        if ($request->filled('queue_status_id')) {
            $query->where('examination_room_queues.queue_status_id', $request->queue_status_id);
        }

        $date = $request->input('date', '2026-10-15');
        $query->whereDate('examination_room_queues.queued_at', $date);

        $queues = $query->orderBy('examination_room_queues.queue_number', 'asc')->paginate(15)->withQueryString();
        $rooms = DB::table('examination_rooms')->where('status', 'ACTIVE')->get();
        $statuses = DB::table('queue_statuses')->where('is_active', true)->get();

        return view('admin.queues.index', compact('queues', 'rooms', 'statuses', 'date'));
    }

    public function callPatient($id)
    {
        $status = DB::table('queue_statuses')->where('name', 'CALLED')->first();
        if ($status) {
            DB::table('examination_room_queues')->where('id', $id)->update([
                'queue_status_id' => $status->id,
                'called_at' => now(),
                'call_count' => DB::raw('call_count + 1'),
                'updated_at' => now(),
            ]);
        }
        return back()->with('success', 'Đã gọi bệnh nhân.');
    }

    public function startService($id)
    {
        $inService = DB::table('queue_statuses')->where('name', 'IN_SERVICE')->first();
        $inExam = DB::table('visit_statuses')->where('name', 'IN_EXAMINATION')->first();

        DB::transaction(function () use ($id, $inService, $inExam) {
            if ($inService) {
                DB::table('examination_room_queues')->where('id', $id)->update([
                    'queue_status_id' => $inService->id,
                    'service_started_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $queue = DB::table('examination_room_queues')->where('id', $id)->first();
            if ($queue && $inExam) {
                DB::table('medical_visits')->where('id', $queue->medical_visit_id)->update([
                    'visit_status_id' => $inExam->id,
                    'examination_started_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
        return back()->with('success', 'Đã bắt đầu khám.');
    }

    public function complete($id)
    {
        $status = DB::table('queue_statuses')->where('name', 'COMPLETED')->first();
        if ($status) {
            DB::table('examination_room_queues')->where('id', $id)->update([
                'queue_status_id' => $status->id,
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
        }
        return back()->with('success', 'Đã hoàn thành lượt chờ.');
    }

    public function skip($id)
    {
        $status = DB::table('queue_statuses')->where('name', 'SKIPPED')->first();
        if ($status) {
            DB::table('examination_room_queues')->where('id', $id)->update([
                'queue_status_id' => $status->id,
                'updated_at' => now(),
            ]);
        }
        return back()->with('warning', 'Đã bỏ qua lượt.');
    }
}
