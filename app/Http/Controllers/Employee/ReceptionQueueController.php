<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\ExaminationRoom;
use App\Models\ExaminationRoomQueue;
use App\Models\QueueStatus;
use App\Support\EmployeeAccess;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;

class ReceptionQueueController extends Controller
{
    private const FILTER_KEY = 'employee.reception.queue_filters';

    public function index(Request $request)
    {
        $employee = app(EmployeeAccess::class)->employee($request->user());
        $filters = $request->session()->get(self::FILTER_KEY, ['date' => today()->toDateString()]);
        $term = trim($filters['q'] ?? '');
        try {
        $queues = ExaminationRoomQueue::query()
            ->select(['id', 'medical_visit_id', 'queue_status_id', 'queue_number', 'queue_type', 'queued_at', 'called_at'])
            ->with(['status:id,name', 'medicalVisit:id,visit_code,patient_id,doctor_id,examination_room_id',
                'medicalVisit.patient:id,patient_code,full_name', 'medicalVisit.doctor:id,full_name', 'medicalVisit.examinationRoom:id,name'])
            ->when($filters['date'] ?? null, fn ($q, $date) => $q->whereDate('queued_at', $date))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('queue_status_id', $status))
            ->when($filters['doctor'] ?? null, fn ($q, $id) => $q->whereHas('medicalVisit', fn ($v) => $v->where('doctor_id', $id)))
            ->when($filters['room'] ?? null, fn ($q, $id) => $q->whereHas('medicalVisit', fn ($v) => $v->where('examination_room_id', $id)))
            ->when($term !== '', fn ($q) => $q->whereHas('medicalVisit', fn ($v) => $v->where(fn ($v) => $v->where('visit_code', 'like', '%'.$term.'%')
                ->orWhereHas('patient', fn ($p) => $p->where('patient_code', 'like', '%'.$term.'%')->orWhere('full_name', 'like', '%'.$term.'%')))))
            ->orderByDesc('queued_at')->orderByDesc('id')->paginate(15);
        $statuses = QueueStatus::orderBy('id')->get(['id', 'name']);
        $doctors = Doctor::orderBy('full_name')->get(['id', 'full_name']);
        $rooms = ExaminationRoom::orderBy('name')->get(['id', 'name']);
        return view('employee.reception.queues', compact('employee', 'filters', 'queues', 'statuses', 'doctors', 'rooms'));
        } catch (QueryException $exception) {
            logger()->error('Không thể tải hàng đợi hành chính.', ['exception' => get_class($exception)]);
            return response()->view('employee.reception.unavailable', ['employee' => $employee, 'backRoute' => 'employee.queue.index'], 503);
        }
    }

    public function filter(Request $request)
    {
        if ($request->input('reset')) {
            $request->session()->put(self::FILTER_KEY, ['date' => today()->toDateString()]);
            return redirect()->route('employee.queue.index');
        }
        $data = $request->validate(['q' => ['nullable', 'string', 'max:120'], 'date' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['nullable', 'integer', 'exists:queue_statuses,id'], 'doctor' => ['nullable', 'integer', 'exists:doctors,id'],
            'room' => ['nullable', 'integer', 'exists:examination_rooms,id']]);
        $request->session()->put(self::FILTER_KEY, $data);
        return redirect()->route('employee.queue.index');
    }
}
