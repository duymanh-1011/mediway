<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDoctorScheduleRequest;
use App\Http\Requests\Admin\UpdateDoctorScheduleRequest;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\ExaminationRoom;
use App\Models\Specialty;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DoctorScheduleController extends Controller
{
    public function index(Request $request)
    {
        $keyword = trim(
            (string) $request->get('keyword')
        );

        $doctorId =
            $request->get('doctor_id');

        $specialtyId =
            $request->get('specialty_id');

        $roomId =
            $request->get('examination_room_id');

        $status =
            $request->get('status');

        $workDate =
            $request->get('work_date');

        $query =
            DoctorSchedule::query()
                ->with([
                    'doctor',
                    'specialty',
                    'examinationRoom',
                ]);

        /*
        |--------------------------------------------------------------------------
        | Tìm kiếm
        |--------------------------------------------------------------------------
        */

        if ($keyword !== '') {

            $query->where(
                function ($q) use ($keyword) {

                    $q->whereHas(
                        'doctor',
                        function ($doctorQuery) use ($keyword) {

                            $doctorQuery
                                ->where(
                                    'doctor_code',
                                    'like',
                                    "%{$keyword}%"
                                )
                                ->orWhere(
                                    'full_name',
                                    'like',
                                    "%{$keyword}%"
                                );
                        }
                    )
                    ->orWhereHas(
                        'specialty',
                        function ($specialtyQuery) use ($keyword) {

                            $specialtyQuery
                                ->where(
                                    'name',
                                    'like',
                                    "%{$keyword}%"
                                );
                        }
                    )
                    ->orWhereHas(
                        'examinationRoom',
                        function ($roomQuery) use ($keyword) {

                            $roomQuery
                                ->where(
                                    'room_code',
                                    'like',
                                    "%{$keyword}%"
                                )
                                ->orWhere(
                                    'name',
                                    'like',
                                    "%{$keyword}%"
                                );
                        }
                    );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Bộ lọc
        |--------------------------------------------------------------------------
        */

        if (!empty($doctorId)) {

            $query->where(
                'doctor_id',
                $doctorId
            );
        }

        if (!empty($specialtyId)) {

            $query->where(
                'specialty_id',
                $specialtyId
            );
        }

        if (!empty($roomId)) {

            $query->where(
                'examination_room_id',
                $roomId
            );
        }

        if (!empty($workDate)) {

            $query->whereDate(
                'work_date',
                $workDate
            );
        }

        if (
            in_array(
                $status,
                ['ACTIVE', 'INACTIVE'],
                true
            )
        ) {
            $query->where(
                'status',
                $status
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Đếm slot
        |--------------------------------------------------------------------------
        */

        $query->withCount(
            'appointmentTimeSlots'
        );

        /*
        |--------------------------------------------------------------------------
        | Danh sách
        |--------------------------------------------------------------------------
        */

        $doctorSchedules =
            $query
                ->orderByDesc('work_date')
                ->orderBy('start_time')
                ->paginate(10)
                ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Dữ liệu filter
        |--------------------------------------------------------------------------
        */

        $doctors =
            Doctor::query()
                ->orderBy('full_name')
                ->get();

        $specialties =
            Specialty::query()
                ->orderBy('name')
                ->get();

        $examinationRooms =
            ExaminationRoom::query()
                ->orderBy('name')
                ->get();

        /*
        |--------------------------------------------------------------------------
        | Thống kê
        |--------------------------------------------------------------------------
        */

        $totalSchedules =
            DoctorSchedule::count();

        $activeSchedules =
            DoctorSchedule::where(
                'status',
                'ACTIVE'
            )->count();

        $todaySchedules =
            DoctorSchedule::whereDate(
                'work_date',
                now()->toDateString()
            )->count();

        return view(
            'admin.doctor-schedules.index',
            compact(
                'doctorSchedules',
                'doctors',
                'specialties',
                'examinationRooms',
                'keyword',
                'doctorId',
                'specialtyId',
                'roomId',
                'status',
                'workDate',
                'totalSchedules',
                'activeSchedules',
                'todaySchedules'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Calendar
    |--------------------------------------------------------------------------
    */

    public function calendar(Request $request)
    {
        $month =
            $request->get(
                'month',
                now()->format('Y-m')
            );

        /*
        |--------------------------------------------------------------------------
        | Kiểm tra format tháng
        |--------------------------------------------------------------------------
        */

        try {

            $currentMonth =
                Carbon::createFromFormat(
                    'Y-m',
                    $month
                )->startOfMonth();

        } catch (\Throwable $exception) {

            $currentMonth =
                now()->startOfMonth();

            $month =
                $currentMonth->format('Y-m');
        }

        $doctorId =
            $request->get('doctor_id');

        $specialtyId =
            $request->get('specialty_id');

        $roomId =
            $request->get(
                'examination_room_id'
            );

        /*
        |--------------------------------------------------------------------------
        | Phạm vi tháng
        |--------------------------------------------------------------------------
        */

        $monthStart =
            $currentMonth
                ->copy()
                ->startOfMonth();

        $monthEnd =
            $currentMonth
                ->copy()
                ->endOfMonth();

        /*
        |--------------------------------------------------------------------------
        | Calendar luôn bắt đầu Thứ 2
        |--------------------------------------------------------------------------
        */

        $calendarStart =
            $monthStart
                ->copy()
                ->startOfWeek(
                    Carbon::MONDAY
                );

        $calendarEnd =
            $monthEnd
                ->copy()
                ->endOfWeek(
                    Carbon::SUNDAY
                );

        /*
        |--------------------------------------------------------------------------
        | Lấy lịch
        |--------------------------------------------------------------------------
        */

        $query =
            DoctorSchedule::query()
                ->with([
                    'doctor',
                    'specialty',
                    'examinationRoom',
                ])
                ->whereBetween(
                    'work_date',
                    [
                        $calendarStart
                            ->format('Y-m-d'),

                        $calendarEnd
                            ->format('Y-m-d'),
                    ]
                );

        if (!empty($doctorId)) {

            $query->where(
                'doctor_id',
                $doctorId
            );
        }

        if (!empty($specialtyId)) {

            $query->where(
                'specialty_id',
                $specialtyId
            );
        }

        if (!empty($roomId)) {

            $query->where(
                'examination_room_id',
                $roomId
            );
        }

        $schedules =
            $query
                ->orderBy('work_date')
                ->orderBy('start_time')
                ->get()
                ->groupBy(
                    function (
                        DoctorSchedule $schedule
                    ) {
                        return $schedule
                            ->work_date
                            ->format('Y-m-d');
                    }
                );

        /*
        |--------------------------------------------------------------------------
        | Tạo danh sách ngày cho calendar
        |--------------------------------------------------------------------------
        */

        $calendarDays =
            collect();

        $cursor =
            $calendarStart->copy();

        while (
            $cursor->lte($calendarEnd)
        ) {
            $calendarDays->push(
                $cursor->copy()
            );

            $cursor->addDay();
        }

        /*
        |--------------------------------------------------------------------------
        | Dữ liệu filter
        |--------------------------------------------------------------------------
        */

        $doctors =
            Doctor::query()
                ->orderBy('full_name')
                ->get();

        $specialties =
            Specialty::query()
                ->orderBy('name')
                ->get();

        $examinationRooms =
            ExaminationRoom::query()
                ->orderBy('name')
                ->get();

        /*
        |--------------------------------------------------------------------------
        | Điều hướng tháng
        |--------------------------------------------------------------------------
        */

        $previousMonth =
            $currentMonth
                ->copy()
                ->subMonth()
                ->format('Y-m');

        $nextMonth =
            $currentMonth
                ->copy()
                ->addMonth()
                ->format('Y-m');

        return view(
            'admin.doctor-schedules.calendar',
            compact(
                'currentMonth',
                'calendarDays',
                'schedules',
                'doctors',
                'specialties',
                'examinationRooms',
                'doctorId',
                'specialtyId',
                'roomId',
                'month',
                'previousMonth',
                'nextMonth'
            )
        );
    }

    public function create()
    {
        $doctors =
            Doctor::query()
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->orderBy('full_name')
                ->get();

        $specialties =
            Specialty::query()
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->orderBy('name')
                ->get();

        $examinationRooms =
            ExaminationRoom::query()
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->orderBy('name')
                ->get();

        return view(
            'admin.doctor-schedules.create',
            compact(
                'doctors',
                'specialties',
                'examinationRooms'
            )
        );
    }

    public function store(
        StoreDoctorScheduleRequest $request
    ) {
        $data =
            $request->validated();

        if ($data['status'] === 'ACTIVE') {

            $conflictMessage =
                $this->getConflictMessage(
                    $data
                );

            if ($conflictMessage !== null) {

                return back()
                    ->withInput()
                    ->withErrors([
                        'schedule_conflict' =>
                            $conflictMessage,
                    ]);
            }
        }

        $doctorSchedule =
            DoctorSchedule::create(
                $data
            );

        return redirect()
            ->route(
                'admin.doctor-schedules.show',
                $doctorSchedule
            )
            ->with(
                'success',
                'Thêm lịch làm việc bác sĩ thành công.'
            );
    }

    public function show(
        DoctorSchedule $doctorSchedule
    ) {
        $doctorSchedule->load([
            'doctor',
            'specialty',
            'examinationRoom',
        ]);

        $timeSlots =
            DB::table(
                'appointment_time_slots as slots'
            )
                ->leftJoin(
                    'appointments',
                    'appointments.appointment_time_slot_id',
                    '=',
                    'slots.id'
                )
                ->where(
                    'slots.doctor_schedule_id',
                    $doctorSchedule->id
                )
                ->groupBy(
                    'slots.id',
                    'slots.start_time',
                    'slots.end_time',
                    'slots.max_patients',
                    'slots.status',
                    'slots.notes',
                    'slots.created_at',
                    'slots.updated_at'
                )
                ->select([
                    'slots.id',
                    'slots.start_time',
                    'slots.end_time',
                    'slots.max_patients',
                    'slots.status',
                    'slots.notes',
                    'slots.created_at',
                    'slots.updated_at',
                ])
                ->selectRaw(
                    'COUNT(appointments.id) as appointments_count'
                )
                ->orderBy(
                    'slots.start_time'
                )
                ->get();

        $totalSlots =
            $timeSlots->count();

        $totalAppointments =
            $timeSlots->sum(
                'appointments_count'
            );

        $totalCapacity =
            $timeSlots->sum(
                'max_patients'
            );

        return view(
            'admin.doctor-schedules.show',
            compact(
                'doctorSchedule',
                'timeSlots',
                'totalSlots',
                'totalAppointments',
                'totalCapacity'
            )
        );
    }

    public function edit(
        DoctorSchedule $doctorSchedule
    ) {
        $doctors =
            Doctor::query()
                ->orderBy('full_name')
                ->get();

        $specialties =
            Specialty::query()
                ->orderBy('name')
                ->get();

        $examinationRooms =
            ExaminationRoom::query()
                ->orderBy('name')
                ->get();

        return view(
            'admin.doctor-schedules.edit',
            compact(
                'doctorSchedule',
                'doctors',
                'specialties',
                'examinationRooms'
            )
        );
    }

    public function update(
        UpdateDoctorScheduleRequest $request,
        DoctorSchedule $doctorSchedule
    ) {
        $data =
            $request->validated();

        if ($data['status'] === 'ACTIVE') {

            $conflictMessage =
                $this->getConflictMessage(
                    $data,
                    $doctorSchedule->id
                );

            if ($conflictMessage !== null) {

                return back()
                    ->withInput()
                    ->withErrors([
                        'schedule_conflict' =>
                            $conflictMessage,
                    ]);
            }
        }

        $hasAppointments =
            DB::table(
                'appointment_time_slots as slots'
            )
                ->join(
                    'appointments',
                    'appointments.appointment_time_slot_id',
                    '=',
                    'slots.id'
                )
                ->where(
                    'slots.doctor_schedule_id',
                    $doctorSchedule->id
                )
                ->exists();

        if ($hasAppointments) {

            $coreChanged =
                (int) $doctorSchedule->doctor_id
                    !==
                (int) $data['doctor_id']
                ||
                (int) $doctorSchedule->specialty_id
                    !==
                (int) $data['specialty_id']
                ||
                (int) $doctorSchedule->examination_room_id
                    !==
                (int) $data['examination_room_id']
                ||
                $doctorSchedule
                    ->work_date
                    ->format('Y-m-d')
                    !==
                $data['work_date']
                ||
                substr(
                    $doctorSchedule->start_time,
                    0,
                    5
                )
                    !==
                $data['start_time']
                ||
                substr(
                    $doctorSchedule->end_time,
                    0,
                    5
                )
                    !==
                $data['end_time'];

            if ($coreChanged) {

                return back()
                    ->withInput()
                    ->withErrors([
                        'schedule_conflict' =>
                            'Lịch làm việc đã có lịch hẹn nên không thể thay đổi bác sĩ, chuyên khoa, phòng, ngày hoặc khung giờ. Bạn chỉ nên cập nhật trạng thái hoặc ghi chú.',
                    ]);
            }
        }

        $doctorSchedule->update(
            $data
        );

        return redirect()
            ->route(
                'admin.doctor-schedules.show',
                $doctorSchedule
            )
            ->with(
                'success',
                'Cập nhật lịch làm việc thành công.'
            );
    }

    public function destroy(
        DoctorSchedule $doctorSchedule
    ) {
        $hasAppointments =
            DB::table(
                'appointment_time_slots as slots'
            )
                ->join(
                    'appointments',
                    'appointments.appointment_time_slot_id',
                    '=',
                    'slots.id'
                )
                ->where(
                    'slots.doctor_schedule_id',
                    $doctorSchedule->id
                )
                ->exists();

        if ($hasAppointments) {

            return redirect()
                ->route(
                    'admin.doctor-schedules.index'
                )
                ->with(
                    'error',
                    'Lịch làm việc đã có lịch hẹn nên không thể xóa.'
                );
        }

        $hasSlots =
            DB::table(
                'appointment_time_slots'
            )
                ->where(
                    'doctor_schedule_id',
                    $doctorSchedule->id
                )
                ->exists();

        if ($hasSlots) {

            return redirect()
                ->route(
                    'admin.doctor-schedules.show',
                    $doctorSchedule
                )
                ->with(
                    'error',
                    'Lịch làm việc đang có khung giờ. Hãy xóa các khung giờ chưa được đặt trước khi xóa lịch.'
                );
        }

        $doctorSchedule->delete();

        return redirect()
            ->route(
                'admin.doctor-schedules.index'
            )
            ->with(
                'success',
                'Xóa lịch làm việc thành công.'
            );
    }

    private function getConflictMessage(
        array $data,
        ?int $ignoreScheduleId = null
    ): ?string {
        $doctorConflict =
            DoctorSchedule::query()
                ->whereDate(
                    'work_date',
                    $data['work_date']
                )
                ->where(
                    'doctor_id',
                    $data['doctor_id']
                )
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->when(
                    $ignoreScheduleId,
                    function ($query) use (
                        $ignoreScheduleId
                    ) {
                        $query->where(
                            'id',
                            '!=',
                            $ignoreScheduleId
                        );
                    }
                )
                ->where(
                    'start_time',
                    '<',
                    $data['end_time']
                )
                ->where(
                    'end_time',
                    '>',
                    $data['start_time']
                )
                ->exists();

        if ($doctorConflict) {

            return
                'Bác sĩ đã có lịch làm việc bị trùng thời gian trong ngày này.';
        }

        $roomConflict =
            DoctorSchedule::query()
                ->whereDate(
                    'work_date',
                    $data['work_date']
                )
                ->where(
                    'examination_room_id',
                    $data['examination_room_id']
                )
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->when(
                    $ignoreScheduleId,
                    function ($query) use (
                        $ignoreScheduleId
                    ) {
                        $query->where(
                            'id',
                            '!=',
                            $ignoreScheduleId
                        );
                    }
                )
                ->where(
                    'start_time',
                    '<',
                    $data['end_time']
                )
                ->where(
                    'end_time',
                    '>',
                    $data['start_time']
                )
                ->exists();

        if ($roomConflict) {

            return
                'Phòng khám đã được sử dụng bởi lịch làm việc khác trong khoảng thời gian này.';
        }

        return null;
    }
}