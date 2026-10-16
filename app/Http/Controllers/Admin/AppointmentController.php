<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CancelAppointmentRequest;
use App\Http\Requests\Admin\StoreAppointmentRequest;
use App\Http\Requests\Admin\UpdateAppointmentRequest;
use App\Models\Appointment;
use App\Models\AppointmentStatus;
use App\Models\AppointmentTimeSlot;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\ExaminationRoom;
use App\Models\Patient;
use App\Models\Specialty;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $keyword =
            trim((string) $request->get('keyword'));

        $statusId =
            $request->get('appointment_status_id');

        $doctorId =
            $request->get('doctor_id');

        $specialtyId =
            $request->get('specialty_id');

        $roomId =
            $request->get('examination_room_id');

        $appointmentDate =
            $request->get('appointment_date');

        $query =
            Appointment::query()
                ->with([
                    'patient',
                    'doctor',
                    'specialty',
                    'examinationRoom',
                    'appointmentTimeSlot',
                    'appointmentStatus',
                ])
                ->select('appointments.*')
                ->selectSub(
                    DB::table('medical_visits')
                        ->selectRaw('COUNT(*)')
                        ->whereColumn(
                            'medical_visits.appointment_id',
                            'appointments.id'
                        ),
                    'medical_visits_count'
                );

        /*
        |--------------------------------------------------------------------------
        | Tìm kiếm
        |--------------------------------------------------------------------------
        */

        if ($keyword !== '') {

            $query->where(
                function ($q) use ($keyword) {

                    $q->where(
                        'appointment_code',
                        'like',
                        "%{$keyword}%"
                    )
                    ->orWhereHas(
                        'patient',
                        function ($patientQuery) use ($keyword) {

                            $patientQuery
                                ->where(
                                    'patient_code',
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
                    );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Bộ lọc
        |--------------------------------------------------------------------------
        */

        if (!empty($statusId)) {

            $query->where(
                'appointment_status_id',
                $statusId
            );
        }

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

        if (!empty($appointmentDate)) {

            $query->whereDate(
                'appointment_date',
                $appointmentDate
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Danh sách
        |--------------------------------------------------------------------------
        */

        $appointments =
            $query
                ->orderByDesc('appointment_date')
                ->orderByDesc('booked_at')
                ->paginate(12)
                ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Dữ liệu bộ lọc
        |--------------------------------------------------------------------------
        */

        $statuses =
            AppointmentStatus::query()
                ->orderBy('process_order')
                ->get();

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

        $totalAppointments =
            Appointment::count();

        $todayAppointments =
            Appointment::whereDate(
                'appointment_date',
                now()->toDateString()
            )->count();

        $cancelledStatusId =
            AppointmentStatus::where(
                'name',
                'CANCELLED'
            )->value('id');

        $cancelledAppointments =
            $cancelledStatusId
                ? Appointment::where(
                    'appointment_status_id',
                    $cancelledStatusId
                )->count()
                : 0;

        return view(
            'admin.appointments.index',
            compact(
                'appointments',
                'statuses',
                'doctors',
                'specialties',
                'examinationRooms',
                'keyword',
                'statusId',
                'doctorId',
                'specialtyId',
                'roomId',
                'appointmentDate',
                'totalAppointments',
                'todayAppointments',
                'cancelledAppointments'
            )
        );
    }

    public function create()
    {
        $patients =
            Patient::query()
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->orderBy('full_name')
                ->get();

        $schedules =
            $this->getSelectableSchedules();

        return view(
            'admin.appointments.create',
            compact(
                'patients',
                'schedules'
            )
        );
    }

    public function store(
        StoreAppointmentRequest $request
    ) {
        $data =
            $request->validated();

        $patient =
            Patient::findOrFail(
                $data['patient_id']
            );

        if ($patient->status !== 'ACTIVE') {

            return back()
                ->withInput()
                ->withErrors([
                    'patient_id' =>
                        'Bệnh nhân hiện không ở trạng thái ACTIVE.',
                ]);
        }

        $slot =
            $this->getValidatedSlot(
                (int) $data[
                    'appointment_time_slot_id'
                ]
            );

        if (!$slot) {

            return back()
                ->withInput()
                ->withErrors([
                    'appointment_time_slot_id' =>
                        'Khung giờ không còn khả dụng.',
                ]);
        }

        if (
            !$this->slotHasCapacity(
                $slot->id
            )
        ) {

            return back()
                ->withInput()
                ->withErrors([
                    'appointment_time_slot_id' =>
                        'Khung giờ này đã đủ số bệnh nhân tối đa.',
                ]);
        }

        $schedule =
            $slot->doctorSchedule;

        $bookedStatus =
            AppointmentStatus::query()
                ->where(
                    'name',
                    'BOOKED'
                )
                ->where(
                    'is_active',
                    true
                )
                ->first();

        if (!$bookedStatus) {

            return back()
                ->withInput()
                ->withErrors([
                    'appointment_time_slot_id' =>
                        'Không tìm thấy trạng thái BOOKED đang hoạt động.',
                ]);
        }

        DB::beginTransaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | Tạo lịch hẹn
            |--------------------------------------------------------------------------
            */

            $appointment =
                Appointment::create([
                    'appointment_code' =>
                        $this->generateAppointmentCode(),

                    'patient_id' =>
                        $patient->id,

                    'doctor_id' =>
                        $schedule->doctor_id,

                    'specialty_id' =>
                        $schedule->specialty_id,

                    'examination_room_id' =>
                        $schedule->examination_room_id,

                    'appointment_date' =>
                        $schedule
                            ->work_date
                            ->format('Y-m-d'),

                    'appointment_time_slot_id' =>
                        $slot->id,

                    'appointment_status_id' =>
                        $bookedStatus->id,

                    'checkin_token' =>
                        (string) Str::uuid(),

                    'reason_for_visit' =>
                        $data['reason_for_visit'],

                    'patient_notes' =>
                        $data['patient_notes']
                        ?? null,

                    'booked_at' =>
                        now(),

                    'cancelled_at' =>
                        null,

                    'cancellation_reason' =>
                        null,
                ]);

            /*
            |--------------------------------------------------------------------------
            | Audit
            |--------------------------------------------------------------------------
            */

            $this->writeAuditLog(
                action: 'CREATE',
                appointment: $appointment,
                oldValues: null,
                newValues: $this->appointmentSnapshot(
                    $appointment
                ),
                description:
                    'Quản trị viên tạo lịch hẹn '
                    .
                    $appointment->appointment_code
                    .
                    '.'
            );

            DB::commit();

        } catch (\Throwable $exception) {

            DB::rollBack();

            throw $exception;
        }

        return redirect()
            ->route(
                'admin.appointments.show',
                $appointment
            )
            ->with(
                'success',
                'Tạo lịch hẹn thành công.'
            );
    }

    public function show(
        Appointment $appointment
    ) {
        $appointment->load([
            'patient',
            'doctor',
            'specialty',
            'examinationRoom',
            'appointmentTimeSlot.doctorSchedule',
            'appointmentStatus',
        ]);

        $medicalVisit =
            DB::table('medical_visits')
                ->where(
                    'appointment_id',
                    $appointment->id
                )
                ->first();

        return view(
            'admin.appointments.show',
            compact(
                'appointment',
                'medicalVisit'
            )
        );
    }

    public function edit(
        Appointment $appointment
    ) {
        $appointment->load([
            'appointmentTimeSlot',
            'appointmentStatus',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Đã phát sinh lượt khám
        |--------------------------------------------------------------------------
        */

        if (
            $this->hasMedicalVisit(
                $appointment->id
            )
        ) {

            return redirect()
                ->route(
                    'admin.appointments.show',
                    $appointment
                )
                ->with(
                    'error',
                    'Lịch hẹn đã phát sinh lượt khám nên không thể chỉnh sửa thông tin đặt lịch.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Lịch đã kết thúc
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $appointment
                    ->appointmentStatus
                    ?->name,
                [
                    'CANCELLED',
                    'COMPLETED',
                ],
                true
            )
        ) {

            return redirect()
                ->route(
                    'admin.appointments.show',
                    $appointment
                )
                ->with(
                    'error',
                    'Lịch hẹn đã kết thúc nên không thể chỉnh sửa.'
                );
        }

        $patients =
            Patient::query()
                ->where(
                    function ($query) use (
                        $appointment
                    ) {

                        $query
                            ->where(
                                'status',
                                'ACTIVE'
                            )
                            ->orWhere(
                                'id',
                                $appointment->patient_id
                            );
                    }
                )
                ->orderBy('full_name')
                ->get();

        $schedules =
            $this->getSelectableSchedules(
                $appointment
                    ->appointment_time_slot_id
            );

        return view(
            'admin.appointments.edit',
            compact(
                'appointment',
                'patients',
                'schedules'
            )
        );
    }

    public function update(
        UpdateAppointmentRequest $request,
        Appointment $appointment
    ) {
        $appointment->load(
            'appointmentStatus'
        );

        /*
        |--------------------------------------------------------------------------
        | Đã phát sinh lượt khám
        |--------------------------------------------------------------------------
        */

        if (
            $this->hasMedicalVisit(
                $appointment->id
            )
        ) {

            return redirect()
                ->route(
                    'admin.appointments.show',
                    $appointment
                )
                ->with(
                    'error',
                    'Lịch hẹn đã phát sinh lượt khám nên không thể chỉnh sửa.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Lịch đã kết thúc
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $appointment
                    ->appointmentStatus
                    ?->name,
                [
                    'CANCELLED',
                    'COMPLETED',
                ],
                true
            )
        ) {

            return redirect()
                ->route(
                    'admin.appointments.show',
                    $appointment
                )
                ->with(
                    'error',
                    'Lịch hẹn đã kết thúc nên không thể chỉnh sửa.'
                );
        }

        $data =
            $request->validated();

        $patient =
            Patient::findOrFail(
                $data['patient_id']
            );

        if (
            $patient->status !== 'ACTIVE'
        ) {

            return back()
                ->withInput()
                ->withErrors([
                    'patient_id' =>
                        'Bệnh nhân hiện không ở trạng thái ACTIVE.',
                ]);
        }

        $slot =
            $this->getValidatedSlot(
                (int) $data[
                    'appointment_time_slot_id'
                ],
                $appointment
                    ->appointment_time_slot_id
            );

        if (!$slot) {

            return back()
                ->withInput()
                ->withErrors([
                    'appointment_time_slot_id' =>
                        'Khung giờ không còn khả dụng.',
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Nếu chuyển sang slot khác thì kiểm tra capacity
        |--------------------------------------------------------------------------
        */

        if (
            (int) $slot->id
            !==
            (int) $appointment
                ->appointment_time_slot_id
            &&
            !$this->slotHasCapacity(
                $slot->id
            )
        ) {

            return back()
                ->withInput()
                ->withErrors([
                    'appointment_time_slot_id' =>
                        'Khung giờ này đã đủ số bệnh nhân tối đa.',
                ]);
        }

        $schedule =
            $slot->doctorSchedule;

        /*
        |--------------------------------------------------------------------------
        | Snapshot trước khi sửa
        |--------------------------------------------------------------------------
        */

        $oldValues =
            $this->appointmentSnapshot(
                $appointment
            );

        DB::beginTransaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | Update
            |--------------------------------------------------------------------------
            */

            $appointment->update([
                'patient_id' =>
                    $patient->id,

                'doctor_id' =>
                    $schedule->doctor_id,

                'specialty_id' =>
                    $schedule->specialty_id,

                'examination_room_id' =>
                    $schedule->examination_room_id,

                'appointment_date' =>
                    $schedule
                        ->work_date
                        ->format('Y-m-d'),

                'appointment_time_slot_id' =>
                    $slot->id,

                'reason_for_visit' =>
                    $data['reason_for_visit'],

                'patient_notes' =>
                    $data['patient_notes']
                    ?? null,
            ]);

            $appointment->refresh();

            /*
            |--------------------------------------------------------------------------
            | Audit
            |--------------------------------------------------------------------------
            */

            $this->writeAuditLog(
                action: 'UPDATE',
                appointment: $appointment,
                oldValues: $oldValues,
                newValues: $this->appointmentSnapshot(
                    $appointment
                ),
                description:
                    'Quản trị viên cập nhật lịch hẹn '
                    .
                    $appointment->appointment_code
                    .
                    '.'
            );

            DB::commit();

        } catch (\Throwable $exception) {

            DB::rollBack();

            throw $exception;
        }

        return redirect()
            ->route(
                'admin.appointments.show',
                $appointment
            )
            ->with(
                'success',
                'Cập nhật lịch hẹn thành công.'
            );
    }

    public function cancel(
        CancelAppointmentRequest $request,
        Appointment $appointment
    ) {
        $appointment->load(
            'appointmentStatus'
        );

        /*
        |--------------------------------------------------------------------------
        | Không hủy khi đã có visit
        |--------------------------------------------------------------------------
        */

        if (
            $this->hasMedicalVisit(
                $appointment->id
            )
        ) {

            return redirect()
                ->route(
                    'admin.appointments.show',
                    $appointment
                )
                ->with(
                    'error',
                    'Lịch hẹn đã phát sinh lượt khám nên không thể hủy tại module lịch hẹn.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Đã hủy
        |--------------------------------------------------------------------------
        */

        if (
            $appointment
                ->appointmentStatus
                ?->name
            ===
            'CANCELLED'
        ) {

            return redirect()
                ->route(
                    'admin.appointments.show',
                    $appointment
                )
                ->with(
                    'error',
                    'Lịch hẹn này đã được hủy trước đó.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Đã hoàn thành
        |--------------------------------------------------------------------------
        */

        if (
            $appointment
                ->appointmentStatus
                ?->name
            ===
            'COMPLETED'
        ) {

            return redirect()
                ->route(
                    'admin.appointments.show',
                    $appointment
                )
                ->with(
                    'error',
                    'Lịch hẹn đã hoàn thành nên không thể hủy.'
                );
        }

        $cancelledStatus =
            AppointmentStatus::query()
                ->where(
                    'name',
                    'CANCELLED'
                )
                ->first();

        if (!$cancelledStatus) {

            return redirect()
                ->route(
                    'admin.appointments.show',
                    $appointment
                )
                ->with(
                    'error',
                    'Không tìm thấy trạng thái CANCELLED.'
                );
        }

        $oldValues =
            $this->appointmentSnapshot(
                $appointment
            );

        DB::beginTransaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | Cancel
            |--------------------------------------------------------------------------
            */

            $appointment->update([
                'appointment_status_id' =>
                    $cancelledStatus->id,

                'cancelled_at' =>
                    now(),

                'cancellation_reason' =>
                    $request
                        ->validated()[
                            'cancellation_reason'
                        ],
            ]);

            $appointment->refresh();

            /*
            |--------------------------------------------------------------------------
            | Audit
            |--------------------------------------------------------------------------
            */

            $this->writeAuditLog(
                action: 'CANCEL',
                appointment: $appointment,
                oldValues: $oldValues,
                newValues: $this->appointmentSnapshot(
                    $appointment
                ),
                description:
                    'Quản trị viên hủy lịch hẹn '
                    .
                    $appointment->appointment_code
                    .
                    '.'
            );

            DB::commit();

        } catch (\Throwable $exception) {

            DB::rollBack();

            throw $exception;
        }

        return redirect()
            ->route(
                'admin.appointments.show',
                $appointment
            )
            ->with(
                'success',
                'Hủy lịch hẹn thành công.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Danh sách lịch có thể đặt
    |--------------------------------------------------------------------------
    */

    private function getSelectableSchedules(
        ?int $currentSlotId = null
    ) {
        return DoctorSchedule::query()
            ->with([
                'doctor',
                'specialty',
                'examinationRoom',

                'appointmentTimeSlots' =>
                    function ($query) use (
                        $currentSlotId
                    ) {

                        $query
                            ->where(
                                function ($slotQuery) use (
                                    $currentSlotId
                                ) {

                                    $slotQuery
                                        ->where(
                                            'status',
                                            'ACTIVE'
                                        );

                                    if ($currentSlotId) {

                                        $slotQuery
                                            ->orWhere(
                                                'id',
                                                $currentSlotId
                                            );
                                    }
                                }
                            )
                            ->orderBy(
                                'start_time'
                            );
                    },
            ])
            ->where(
                'status',
                'ACTIVE'
            )
            ->whereDate(
                'work_date',
                '>=',
                now()->toDateString()
            )
            ->orderBy('work_date')
            ->orderBy('start_time')
            ->get()
            ->filter(
                function (
                    DoctorSchedule $schedule
                ) use (
                    $currentSlotId
                ) {

                    $schedule
                        ->appointmentTimeSlots
                        ->transform(
                            function (
                                AppointmentTimeSlot $slot
                            ) use (
                                $schedule,
                                $currentSlotId
                            ) {

                                $slot->is_selectable =
                                    (int) $slot->id
                                    ===
                                    (int) $currentSlotId
                                    ||
                                    (
                                        $this->isSlotFuture(
                                            $schedule,
                                            $slot
                                        )
                                        &&
                                        $this->slotHasCapacity(
                                            $slot->id
                                        )
                                    );

                                $slot->booked_count =
                                    $this->getActiveBookingCount(
                                        $slot->id
                                    );

                                return $slot;
                            }
                        );

                    return $schedule
                        ->appointmentTimeSlots
                        ->contains(
                            function ($slot) {
                                return
                                    $slot->is_selectable;
                            }
                        );
                }
            )
            ->values();
    }

    /*
    |--------------------------------------------------------------------------
    | Validate slot
    |--------------------------------------------------------------------------
    */

    private function getValidatedSlot(
        int $slotId,
        ?int $currentSlotId = null
    ): ?AppointmentTimeSlot {
        $slot =
            AppointmentTimeSlot::query()
                ->with(
                    'doctorSchedule'
                )
                ->find($slotId);

        if (!$slot) {
            return null;
        }

        $schedule =
            $slot->doctorSchedule;

        if (!$schedule) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Slot hiện tại khi edit
        |--------------------------------------------------------------------------
        */

        if (
            $currentSlotId
            &&
            $slotId === $currentSlotId
        ) {
            return $slot;
        }

        if (
            $slot->status !== 'ACTIVE'
            ||
            $schedule->status !== 'ACTIVE'
        ) {
            return null;
        }

        if (
            !$this->isSlotFuture(
                $schedule,
                $slot
            )
        ) {
            return null;
        }

        return $slot;
    }

    /*
    |--------------------------------------------------------------------------
    | Slot còn ở tương lai
    |--------------------------------------------------------------------------
    */

    private function isSlotFuture(
        DoctorSchedule $schedule,
        AppointmentTimeSlot $slot
    ): bool {
        $slotDateTime =
            Carbon::parse(
                $schedule
                    ->work_date
                    ->format('Y-m-d')
                .
                ' '
                .
                substr(
                    $slot->start_time,
                    0,
                    8
                )
            );

        return
            $slotDateTime->isFuture();
    }

    /*
    |--------------------------------------------------------------------------
    | Capacity
    |--------------------------------------------------------------------------
    */

    private function slotHasCapacity(
        int $slotId
    ): bool {
        $slot =
            AppointmentTimeSlot::find(
                $slotId
            );

        if (!$slot) {
            return false;
        }

        return
            $this->getActiveBookingCount(
                $slotId
            )
            <
            (int) $slot->max_patients;
    }

    /*
    |--------------------------------------------------------------------------
    | Số appointment chưa bị hủy trong slot
    |--------------------------------------------------------------------------
    */

    private function getActiveBookingCount(
        int $slotId
    ): int {
        return DB::table(
            'appointments'
        )
            ->join(
                'appointment_statuses',
                'appointment_statuses.id',
                '=',
                'appointments.appointment_status_id'
            )
            ->where(
                'appointments.appointment_time_slot_id',
                $slotId
            )
            ->where(
                'appointment_statuses.name',
                '!=',
                'CANCELLED'
            )
            ->count();
    }

    /*
    |--------------------------------------------------------------------------
    | Appointment đã sinh visit?
    |--------------------------------------------------------------------------
    */

    private function hasMedicalVisit(
        int $appointmentId
    ): bool {
        return DB::table(
            'medical_visits'
        )
            ->where(
                'appointment_id',
                $appointmentId
            )
            ->exists();
    }

    /*
    |--------------------------------------------------------------------------
    | Sinh mã appointment
    |--------------------------------------------------------------------------
    */

    private function generateAppointmentCode(): string
    {
        do {

            $code =
                'LH'
                .
                now()->format(
                    'ymd'
                )
                .
                strtoupper(
                    Str::random(6)
                );

        } while (
            Appointment::where(
                'appointment_code',
                $code
            )->exists()
        );

        return $code;
    }

    /*
    |--------------------------------------------------------------------------
    | Snapshot dữ liệu appointment dùng cho audit
    |--------------------------------------------------------------------------
    */

    private function appointmentSnapshot(
        Appointment $appointment
    ): array {
        return [
            'appointment_code' =>
                $appointment->appointment_code,

            'patient_id' =>
                $appointment->patient_id,

            'doctor_id' =>
                $appointment->doctor_id,

            'specialty_id' =>
                $appointment->specialty_id,

            'examination_room_id' =>
                $appointment->examination_room_id,

            'appointment_date' =>
                $appointment->appointment_date
                    ?->format('Y-m-d'),

            'appointment_time_slot_id' =>
                $appointment
                    ->appointment_time_slot_id,

            'appointment_status_id' =>
                $appointment
                    ->appointment_status_id,

            'reason_for_visit' =>
                $appointment
                    ->reason_for_visit,

            'patient_notes' =>
                $appointment
                    ->patient_notes,

            'booked_at' =>
                $appointment
                    ->booked_at
                    ?->format('Y-m-d H:i:s'),

            'cancelled_at' =>
                $appointment
                    ->cancelled_at
                    ?->format('Y-m-d H:i:s'),

            'cancellation_reason' =>
                $appointment
                    ->cancellation_reason,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Ghi audit_logs
    |--------------------------------------------------------------------------
    */

    private function writeAuditLog(
        string $action,
        Appointment $appointment,
        ?array $oldValues,
        ?array $newValues,
        string $description
    ): void {
        DB::table(
            'audit_logs'
        )->insert([
            'user_id' =>
                Auth::id(),

            'role_name_snapshot' =>
                'Administrator',

            'action' =>
                $action,

            'entity_type' =>
                'Appointment',

            'entity_id' =>
                $appointment->id,

            'entity_business_code' =>
                $appointment
                    ->appointment_code,

            'old_values' =>
                $oldValues
                    ? json_encode(
                        $oldValues,
                        JSON_UNESCAPED_UNICODE
                        |
                        JSON_UNESCAPED_SLASHES
                    )
                    : null,

            'new_values' =>
                $newValues
                    ? json_encode(
                        $newValues,
                        JSON_UNESCAPED_UNICODE
                        |
                        JSON_UNESCAPED_SLASHES
                    )
                    : null,

            'description' =>
                $description,

            'ip_address' =>
                request()->ip(),

            'user_agent' =>
                Str::limit(
                    (string) request()
                        ->userAgent(),
                    500,
                    ''
                ),

            'performed_at' =>
                now(),

            'created_at' =>
                now(),
        ]);
    }
}