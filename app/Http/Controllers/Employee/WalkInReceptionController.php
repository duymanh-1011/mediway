<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\DoctorSchedule;
use App\Models\MedicalVisit;
use App\Models\Patient;
use App\Services\WalkInReceptionService;
use App\Support\EmployeeAccess;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WalkInReceptionController extends Controller
{
    private const MATCHES = 'employee.walk_in_patient_matches';

    public function create(Request $request)
    {
        $employee = app(EmployeeAccess::class)->employee($request->user());
        try {
            $patientIds = $request->session()->get(self::MATCHES, []);
            $patients = Patient::whereIn('id', $patientIds)->where('status', 'ACTIVE')->get(['id', 'patient_code', 'full_name', 'date_of_birth']);
            $schedules = DoctorSchedule::where('status', 'ACTIVE')->whereDate('work_date', today())
                ->where('start_time', '<=', now()->format('H:i:s'))->where('end_time', '>', now()->format('H:i:s'))
                ->withQualifiedDoctor()
                ->whereHas('doctor', fn ($q) => $q->where('status', 'ACTIVE'))
                ->whereHas('specialty', fn ($q) => $q->where('status', 'ACTIVE'))
                ->whereHas('examinationRoom', fn ($q) => $q->where('status', 'ACTIVE'))
                ->with(['doctor:id,full_name', 'specialty:id,name', 'examinationRoom:id,name'])->orderBy('start_time')->get();
            return view('employee.reception.walk-in', compact('employee', 'patients', 'schedules'));
        } catch (QueryException $exception) {
            logger()->error('Không thể tải tiếp đón trực tiếp.', ['exception' => get_class($exception)]);
            return response()->view('employee.reception.unavailable', ['employee' => $employee, 'backRoute' => 'employee.walk-in.create'], 503);
        }
    }

    public function search(Request $request)
    {
        $data = $request->validate(['identifier' => ['required', 'string', 'max:30']], ['identifier.required' => 'Vui lòng nhập mã bệnh nhân, số điện thoại hoặc CCCD.']);
        $term = trim($data['identifier']);
        $ids = Patient::where('status', 'ACTIVE')->where(fn ($q) => $q->where('patient_code', $term)
            ->orWhereHas('user', fn ($u) => $u->where('phone', $term))
            ->orWhereHas('detail', fn ($d) => $d->where('citizen_id_number', $term)))->limit(5)->pluck('id')->all();
        $request->session()->put(self::MATCHES, $ids);
        return redirect()->route('employee.walk-in.create')->with('walkin_searched', true);
    }

    public function store(Request $request, WalkInReceptionService $service)
    {
        $data = $request->validate([
            'patient_mode' => ['required', Rule::in(['existing', 'new'])],
            'patient_id' => ['nullable', 'required_if:patient_mode,existing', 'integer', Rule::exists('patients', 'id')],
            'full_name' => ['nullable', 'required_if:patient_mode,new', 'string', 'max:120', 'regex:/\S/u'],
            'date_of_birth' => ['nullable', 'required_if:patient_mode,new', 'date_format:Y-m-d', 'before_or_equal:today'],
            'gender' => ['nullable', 'required_if:patient_mode,new', Rule::in(['Nam', 'Nữ', 'Khác'])],
            'citizen_id_number' => ['nullable', 'string', 'regex:/^[0-9]{12}$/', Rule::unique('patient_details', 'citizen_id_number')],
            'confirm_search' => ['accepted_if:patient_mode,new'],
            'schedule_id' => ['required', 'integer', Rule::exists('doctor_schedules', 'id')],
            'request_token' => ['required', 'uuid'],
            'confirm_reception' => ['accepted'],
        ], [
            'patient_mode.required' => 'Vui lòng chọn bệnh nhân có hồ sơ hoặc tạo hồ sơ mới.',
            'patient_id.required_if' => 'Vui lòng tìm và chọn bệnh nhân đã có hồ sơ.',
            'full_name.required_if' => 'Vui lòng nhập họ tên bệnh nhân.', 'full_name.regex' => 'Họ tên không được để trống.',
            'date_of_birth.required_if' => 'Vui lòng nhập ngày sinh.', 'date_of_birth.before_or_equal' => 'Ngày sinh không thể ở tương lai.',
            'gender.required_if' => 'Vui lòng chọn giới tính.', 'citizen_id_number.regex' => 'CCCD phải gồm 12 chữ số.',
            'citizen_id_number.unique' => 'CCCD đã có trong hồ sơ. Vui lòng tìm bệnh nhân hiện có.',
            'confirm_search.accepted_if' => 'Vui lòng xác nhận đã tìm hồ sơ trước khi tạo mới.',
            'schedule_id.required' => 'Vui lòng chọn ca khám đang hoạt động.',
            'confirm_reception.accepted' => 'Vui lòng xác nhận thông tin tiếp nhận.',
        ]);
        if ($data['patient_mode'] === 'existing' && ! in_array((int) $data['patient_id'], $request->session()->get(self::MATCHES, []), true)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['patient_id' => 'Vui lòng tìm và chọn lại đúng hồ sơ bệnh nhân.']);
        }
        try {
            $visit = $service->receive($request->user(), $data);
        } catch (QueryException $exception) {
            logger()->error('Không thể hoàn tất tiếp nhận trực tiếp.', ['exception' => get_class($exception)]);
            return back()->withInput()->withErrors(['reception' => 'Không thể tiếp nhận lúc này. Vui lòng kiểm tra thông tin và thử lại.']);
        }
        $request->session()->forget(self::MATCHES);
        return redirect()->route('employee.walk-in.show', $visit)->with('success', 'Tiếp nhận thành công. Bệnh nhân cần thanh toán phí khám trước khi được cấp số.');
    }

    public function show(Request $request, MedicalVisit $visit)
    {
        abort_unless($visit->reception_source === 'WALK_IN' && $visit->appointment_id === null, 404);
        $employee = app(EmployeeAccess::class)->employee($request->user());
        $visit->load(['patient:id,patient_code,full_name', 'doctor:id,full_name', 'specialty:id,name', 'examinationRoom:id,name', 'status:id,name', 'payables.paymentStatus']);
        return view('employee.reception.walk-in-show', compact('employee', 'visit'));
    }
}
