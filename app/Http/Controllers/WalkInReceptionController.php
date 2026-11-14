<?php

namespace App\Http\Controllers;

use App\Models\DoctorSchedule;
use App\Models\MedicalVisit;
use App\Models\Patient;
use App\Services\PatientAccountRegistrationService;
use App\Services\WalkInReceptionService;
use App\Support\EmployeeAccess;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WalkInReceptionController extends Controller
{
    private function matchesKey(Request $request): string
    {
        $scope = $request->routeIs('admin.visits.walk-in.*') ? 'admin' : 'employee';

        return $scope.'.walk_in_patient_matches';
    }

    public function create(Request $request)
    {
        $isAdmin = $request->routeIs('admin.visits.walk-in.*');
        $employee = $isAdmin ? null : app(EmployeeAccess::class)->employee($request->user());
        try {
            $matchesKey = $this->matchesKey($request);
            $searched = $request->session()->has($matchesKey);
            $patientIds = $request->session()->get($matchesKey, []);
            $patients = Patient::whereIn('id', $patientIds)->where('status', 'ACTIVE')
                ->with(['detail:id,patient_id,citizen_id_number,current_address,permanent_address', 'user:id,phone,must_change_password'])
                ->get(['id', 'user_id', 'patient_code', 'full_name', 'date_of_birth', 'gender']);
            $schedules = DoctorSchedule::where('status', 'ACTIVE')->whereDate('work_date', today())
                ->where('start_time', '<=', now()->format('H:i:s'))->where('end_time', '>', now()->format('H:i:s'))
                ->withQualifiedDoctor()
                ->whereHas('doctor', fn ($q) => $q->where('status', 'ACTIVE'))
                ->whereHas('specialty', fn ($q) => $q->where('status', 'ACTIVE'))
                ->whereHas('examinationRoom', fn ($q) => $q->where('status', 'ACTIVE'))
                ->with(['doctor:id,full_name', 'specialty:id,name', 'examinationRoom:id,name'])->orderBy('start_time')->get();
            $roomIds = $schedules->pluck('examination_room_id')->unique()->values();
            $roomVisitCounts = $roomIds->isEmpty()
                ? collect()
                : MedicalVisit::query()
                    ->selectRaw('examination_room_id, COUNT(*) as aggregate')
                    ->whereIn('examination_room_id', $roomIds)
                    ->whereDate('checked_in_at', today())
                    ->whereHas('status', fn ($q) => $q->whereIn('name', ['WAITING', 'IN_EXAMINATION', 'WAITING_FOR_SERVICE', 'RETURNING']))
                    ->groupBy('examination_room_id')
                    ->pluck('aggregate', 'examination_room_id');
            $activation = $request->session()->pull($matchesKey.'_activation');

            return response()->view('reception.walk-in', compact('isAdmin', 'employee', 'patients', 'schedules', 'roomVisitCounts', 'searched', 'activation'))
                ->header('Cache-Control', 'private, no-store');
        } catch (QueryException $exception) {
            logger()->error('Không thể tải tiếp đón trực tiếp.', ['exception' => get_class($exception)]);
            if ($isAdmin) {
                return response()->view('reception.walk-in', ['isAdmin' => true, 'unavailable' => true], 503);
            }
            return response()->view('employee.reception.unavailable', ['employee' => $employee, 'backRoute' => 'employee.walk-in.create'], 503);
        }
    }

    public function search(Request $request)
    {
        $matchesKey = $this->matchesKey($request);
        $request->session()->forget($matchesKey);
        $data = $request->validate(['identifier' => ['required', 'string', 'max:30']], ['identifier.required' => 'Vui lòng nhập mã bệnh nhân, số điện thoại hoặc CCCD.']);
        $term = trim($data['identifier']);
        $ids = Patient::where('status', 'ACTIVE')->where(fn ($q) => $q->whereIn('patient_code', [$term, strtoupper($term)])
            ->orWhereHas('user', fn ($u) => $u->where('phone', $term))
            ->orWhereHas('detail', fn ($d) => $d->where('citizen_id_number', $term)))->limit(5)->pluck('id')->all();
        $request->session()->put($matchesKey, $ids);
        $createRoute = $request->routeIs('admin.visits.walk-in.*') ? 'admin.visits.walk-in.create' : 'employee.walk-in.create';

        return redirect()->route($createRoute)->withInput(['identifier' => $term]);
    }

    public function store(Request $request, WalkInReceptionService $service)
    {
        $isAdmin = $request->routeIs('admin.visits.walk-in.*');
        $matchesKey = $this->matchesKey($request);
        $isNewPatient = $request->input('patient_mode') === 'new';
        $existingPatient = null;
        if (! $isNewPatient && is_numeric($request->input('patient_id'))
            && in_array((int) $request->input('patient_id'), $request->session()->get($matchesKey, []), true)) {
            $existingPatient = Patient::with(['detail:id,patient_id,citizen_id_number', 'user:id'])->find((int) $request->input('patient_id'));
        }
        $needsBirthDate = $isNewPatient || ($existingPatient !== null && blank($existingPatient->date_of_birth));
        $needsGender = $isNewPatient || ($existingPatient !== null && blank($existingPatient->gender));
        $needsAccount = $isNewPatient || ($existingPatient !== null && $existingPatient->user_id === null);
        $needsCitizenId = $isNewPatient || ($needsAccount && blank($existingPatient?->detail?->citizen_id_number));
        $data = $request->validate([
            'patient_mode' => ['required', Rule::in(['existing', 'new'])],
            'patient_id' => ['exclude_unless:patient_mode,existing', 'required', 'integer', Rule::exists('patients', 'id')],
            'full_name' => ['exclude_unless:patient_mode,new', 'required', 'string', 'max:120', 'regex:/\S/u'],
            'date_of_birth' => $needsBirthDate ? ['required', 'date_format:Y-m-d', 'before_or_equal:today'] : ['exclude'],
            'gender' => $needsGender ? ['required', Rule::in(['Nam', 'Nữ', 'Khác'])] : ['exclude'],
            'citizen_id_number' => $needsCitizenId ? ['required', 'string', 'regex:/^[0-9]{12}$/D'] : ['exclude'],
            'phone' => $needsAccount ? ['required', 'string', 'regex:/^0[0-9]{9}$/D'] : ['exclude'],
            'current_address' => ['exclude_unless:patient_mode,new', 'nullable', 'string', 'max:255'],
            'reason_for_visit' => ['nullable', 'string', 'max:1000'],
            'confirm_search' => ['exclude_unless:patient_mode,new', 'accepted'],
            'schedule_id' => ['required', 'integer', Rule::exists('doctor_schedules', 'id')],
            'request_token' => ['required', 'uuid'],
            'confirm_reception' => ['accepted'],
        ], [
            'patient_mode.required' => 'Vui lòng chọn bệnh nhân có hồ sơ hoặc tạo hồ sơ mới.',
            'patient_id.required' => 'Vui lòng tìm và chọn bệnh nhân đã có hồ sơ.',
            'full_name.required' => 'Vui lòng nhập họ tên bệnh nhân.', 'full_name.regex' => 'Họ tên không được để trống.',
            'date_of_birth.required' => 'Vui lòng nhập ngày sinh.', 'date_of_birth.before_or_equal' => 'Ngày sinh không thể ở tương lai.',
            'gender.required' => 'Vui lòng chọn giới tính.',
            'citizen_id_number.required' => 'Vui lòng nhập số CCCD khi tạo hồ sơ mới hoặc liên kết hồ sơ cũ chưa có CCCD.',
            'citizen_id_number.regex' => 'CCCD phải gồm 12 chữ số.',
            'phone.required' => 'Vui lòng nhập số điện thoại để tạo tài khoản bệnh nhân.',
            'phone.regex' => 'Số điện thoại phải gồm 10 chữ số và bắt đầu bằng số 0.',
            'current_address.max' => 'Địa chỉ không được vượt quá 255 ký tự.',
            'reason_for_visit.max' => 'Lý do khám không được vượt quá 1.000 ký tự.',
            'confirm_search.accepted' => 'Vui lòng xác nhận đã tìm hồ sơ trước khi tạo mới.',
            'schedule_id.required' => 'Vui lòng chọn phòng khám đang tiếp nhận.',
            'confirm_reception.accepted' => 'Vui lòng xác nhận thông tin tiếp nhận.',
        ]);
        if ($data['patient_mode'] === 'existing' && ! in_array((int) $data['patient_id'], $request->session()->get($matchesKey, []), true)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['patient_id' => 'Vui lòng tìm và chọn lại đúng hồ sơ bệnh nhân.']);
        }
        try {
            $result = $service->receive($request->user(), $data, asAdministrator: $isAdmin);
        } catch (QueryException $exception) {
            logger()->error('Không thể hoàn tất tiếp nhận trực tiếp.', ['exception' => get_class($exception)]);
            return back()->withInput()->withErrors(['reception' => 'Không thể tiếp nhận lúc này. Vui lòng kiểm tra thông tin và thử lại.']);
        }
        $request->session()->forget($matchesKey);

        $visit = $result['visit'];
        if (! $isAdmin && ($handoff = $this->redirectToCredentials($request, $visit, $result['credentials']))) {
            return $handoff;
        }

        return redirect()->route($isAdmin ? 'admin.visits.show' : 'employee.walk-in.show', $visit)
            ->with('success', 'Đã tiếp nhận và cấp số khám. Bệnh nhân cần thanh toán phí khám trước khi được gọi.');
    }

    private function redirectToCredentials(Request $request, MedicalVisit $visit, ?array $credentials): ?RedirectResponse
    {
        $credentialsKey = 'walk_in_credentials_'.$visit->id;
        if ($credentials !== null) {
            $request->session()->flash($credentialsKey, encrypt([
                ...$credentials, 'issued_by' => $request->user()->id,
            ]));
        }
        if (! $request->session()->has($credentialsKey)) {
            return null;
        }
        $request->session()->keep($credentialsKey);

        return redirect()->route('employee.walk-in.credentials', $visit);
    }

    public function credentials(Request $request, MedicalVisit $visit)
    {
        abort_unless($visit->reception_source === 'WALK_IN' && $visit->appointment_id === null, 404);
        $payload = $request->session()->pull('walk_in_credentials_'.$visit->id);
        $credentials = $payload ? decrypt($payload) : null;
        if (! $credentials || $credentials['issued_by'] !== $request->user()->id) {
            return redirect()->route('employee.walk-in.show', $visit);
        }

        $employee = app(EmployeeAccess::class)->employee($request->user());

        return response()->view('reception.walk-in-credentials', compact('visit', 'credentials', 'employee'))
            ->header('Cache-Control', 'private, no-store, no-cache, must-revalidate');
    }

    public function issueTemporaryPassword(Request $request, Patient $patient, WalkInReceptionService $service)
    {
        abort_unless(in_array($patient->id, $request->session()->get($this->matchesKey($request), []), true), 404);
        $request->validate(['confirm_identity' => ['accepted']], [
            'confirm_identity.accepted' => 'Vui lòng đối chiếu CCCD của bệnh nhân trước khi cấp mật khẩu tạm.',
        ]);

        $result = $service->issueTemporaryPassword($request->user(), $patient);

        return $this->redirectToCredentials($request, $result['visit'], $result['credentials']);
    }

    public function issueActivation(Request $request, Patient $patient, PatientAccountRegistrationService $registration)
    {
        $matchesKey = $this->matchesKey($request);
        abort_unless(in_array($patient->id, $request->session()->get($matchesKey, []), true), 404);
        $request->validate(['confirm_identity' => ['accepted']], [
            'confirm_identity.accepted' => 'Vui lòng đối chiếu CCCD của bệnh nhân trước khi cấp mã kích hoạt.',
        ]);

        $isAdmin = $request->routeIs('admin.visits.walk-in.*');
        $code = $registration->issueActivationCode($request->user(), $patient, $isAdmin);

        return redirect()->route($isAdmin ? 'admin.visits.walk-in.create' : 'employee.walk-in.create')
            ->with($matchesKey.'_activation', ['patient_code' => $patient->patient_code, 'code' => $code]);
    }

    public function show(Request $request, MedicalVisit $visit)
    {
        abort_unless($visit->reception_source === 'WALK_IN' && $visit->appointment_id === null, 404);
        $employee = app(EmployeeAccess::class)->employee($request->user());
        $visit->load(['patient:id,patient_code,full_name', 'doctor:id,full_name', 'specialty:id,name', 'examinationRoom:id,name', 'status:id,name', 'payables.paymentStatus']);
        return view('employee.reception.walk-in-show', compact('employee', 'visit'));
    }
}
