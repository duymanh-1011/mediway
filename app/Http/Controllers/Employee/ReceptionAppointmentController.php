<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentStatus;
use App\Models\Doctor;
use App\Models\Specialty;
use App\Support\EmployeeAccess;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use App\Services\AppointmentReceptionService;

class ReceptionAppointmentController extends Controller
{
    private const FILTER_KEY = 'employee.reception.appointment_filters';

    public function index(Request $request)
    {
        $employee = app(EmployeeAccess::class)->employee($request->user());
        $filters = $request->session()->get(self::FILTER_KEY, ['date' => today()->toDateString()]);
        $term = trim($filters['q'] ?? '');
        try {
        $appointments = Appointment::query()
            ->select(['id', 'appointment_code', 'patient_id', 'doctor_id', 'specialty_id', 'examination_room_id', 'appointment_time_slot_id', 'appointment_status_id', 'appointment_date'])
            ->with(['patient:id,patient_code,full_name', 'doctor:id,full_name', 'specialty:id,name', 'examinationRoom:id,name', 'appointmentStatus:id,name', 'appointmentTimeSlot:id,start_time,end_time', 'medicalVisit:id,appointment_id,visit_code,checked_in_at'])
            ->when($filters['date'] ?? null, fn ($q, $date) => $q->whereDate('appointment_date', $date))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('appointment_status_id', $status))
            ->when($filters['doctor'] ?? null, fn ($q, $doctor) => $q->where('doctor_id', $doctor))
            ->when($filters['specialty'] ?? null, fn ($q, $specialty) => $q->where('specialty_id', $specialty))
            ->when($term !== '', fn ($q) => $q->where(fn ($q) => $q->where('appointment_code', 'like', '%'.$term.'%')
                ->orWhereHas('patient', fn ($q) => $q->where('patient_code', 'like', '%'.$term.'%')->orWhere('full_name', 'like', '%'.$term.'%'))))
            ->orderByDesc('appointment_date')->orderByDesc('id')->paginate(15);
        $statuses = AppointmentStatus::orderBy('id')->get(['id', 'name']);
        $doctors = Doctor::orderBy('full_name')->get(['id', 'full_name']);
        $specialties = Specialty::orderBy('name')->get(['id', 'name']);
        return view('employee.reception.appointments', compact('employee', 'filters', 'appointments', 'statuses', 'doctors', 'specialties'));
        } catch (QueryException $exception) {
            logger()->error('Không thể tải danh sách lịch hẹn tiếp đón.', ['exception' => get_class($exception)]);
            return response()->view('employee.reception.unavailable', ['employee' => $employee, 'backRoute' => 'employee.appointments.index'], 503);
        }
    }

    public function filter(Request $request)
    {
        if ($request->input('reset')) {
            $request->session()->put(self::FILTER_KEY, ['date' => today()->toDateString()]);
            return redirect()->route('employee.appointments.index');
        }
        $data = $request->validate(['q' => ['nullable', 'string', 'max:120'], 'date' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['nullable', 'integer', 'exists:appointment_statuses,id'], 'doctor' => ['nullable', 'integer', 'exists:doctors,id'],
            'specialty' => ['nullable', 'integer', 'exists:specialties,id']]);
        $request->session()->put(self::FILTER_KEY, $data);
        return redirect()->route('employee.appointments.index');
    }

    public function show(Request $request, Appointment $appointment)
    {
        $employee = app(EmployeeAccess::class)->employee($request->user());
        $appointment->load(['patient:id,patient_code,full_name', 'doctor:id,full_name', 'specialty:id,name', 'examinationRoom:id,name',
            'appointmentStatus:id,name', 'appointmentTimeSlot.doctorSchedule', 'medicalVisit:id,appointment_id,visit_code,checked_in_at']);
        return view('employee.reception.appointment', compact('employee', 'appointment'));
    }

    public function checkIn(Request $request, Appointment $appointment, AppointmentReceptionService $reception)
    {
        $request->validate(['confirm_reception' => ['accepted']], ['confirm_reception.accepted' => 'Vui lòng xác nhận thông tin trước khi tiếp nhận.']);
        try {
            $reception->checkIn($appointment, $request->user(), true);
        } catch (QueryException $exception) {
            logger()->error('Không thể hoàn tất tiếp nhận lịch hẹn.', ['exception' => get_class($exception)]);
            return back()->withErrors(['appointment' => 'Không thể tiếp nhận lúc này. Vui lòng thử lại hoặc liên hệ hỗ trợ.']);
        }
        return redirect()->route('employee.appointments.show', $appointment)->with('success', 'Tiếp nhận thành công. Bệnh nhân cần thanh toán phí khám trước khi được xếp hàng.');
    }
}
