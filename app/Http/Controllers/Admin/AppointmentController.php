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
use App\Models\ExaminationRoomQueue;
use App\Models\MedicalVisit;
use App\Models\Patient;
use App\Models\QueueStatus;
use App\Models\Specialty;
use App\Models\VisitStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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

        if (! empty($statusId)) {

            $query->where(
                'appointment_status_id',
                $statusId
            );
        }

        if (! empty($doctorId)) {

            $query->where(
                'doctor_id',
                $doctorId
            );
        }

        if (! empty($specialtyId)) {

            $query->where(
                'specialty_id',
                $specialtyId
            );
        }

        if (! empty($roomId)) {

            $query->where(
                'examination_room_id',
                $roomId
            );
        }

        if (! empty($appointmentDate)) {

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
                ->with('specialty')
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
                    'patient_id' => 'Hồ sơ bệnh nhân đã ngừng hoạt động nên chưa thể đặt lịch. Vui lòng kiểm tra lại hồ sơ bệnh nhân.',
                ]);
        }

        $slot =
            $this->getValidatedSlot(
                (int) $data[
                    'appointment_time_slot_id'
                ]
            );

        if (! $slot) {

            return back()
                ->withInput()
                ->withErrors([
                    'appointment_time_slot_id' => 'Khung giờ đã chọn không còn nhận lịch hẹn. Vui lòng chọn khung giờ khác.',
                ]);
        }

        if (
            ! $this->slotHasCapacity(
                $slot->id
            )
        ) {

            return back()
                ->withInput()
                ->withErrors([
                    'appointment_time_slot_id' => 'Khung giờ này đã đủ số bệnh nhân có thể tiếp nhận. Vui lòng chọn khung giờ khác.',
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

        if (! $bookedStatus) {

            return back()
                ->withInput()
                ->withErrors([
                    'appointment_time_slot_id' => 'Chưa có trạng thái “Đã đặt lịch” đang được sử dụng. Vui lòng kiểm tra mục Trạng thái lịch hẹn.',
                ]);
        }

        DB::beginTransaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | Tạo lịch hẹn
            |--------------------------------------------------------------------------
            */

            $slot = $this->lockBookableSlot($slot->id, $slot->doctor_schedule_id);
            $this->assertBookingSelection($data, $slot);
            $this->assertNoDuplicateBooking($patient->id, $slot->id);
            $schedule = $slot->doctorSchedule;
            $appointment =
                Appointment::create([
                    'appointment_code' => $this->generateAppointmentCode(),

                    'patient_id' => $patient->id,

                    'doctor_id' => $schedule->doctor_id,

                    'specialty_id' => $schedule->specialty_id,

                    'examination_room_id' => $schedule->examination_room_id,

                    'appointment_date' => $schedule
                        ->work_date
                        ->format('Y-m-d'),

                    'appointment_time_slot_id' => $slot->id,

                    'appointment_status_id' => $bookedStatus->id,

                    'checkin_token' => (string) Str::uuid(),

                    'reason_for_visit' => $data['reason_for_visit'],

                    'patient_notes' => $data['patient_notes']
                        ?? null,

                    'booked_at' => now(),

                    'cancelled_at' => null,

                    'cancellation_reason' => null,
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
                description: 'Quản trị viên tạo lịch hẹn '
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
            MedicalVisit::query()
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

    public function checkIn(
        Appointment $appointment
    ) {
        $appointment->load(
            'appointmentStatus'
        );

        /*
        |--------------------------------------------------------------------------
        | Nếu đã phát sinh lượt khám thì không tạo thêm
        |--------------------------------------------------------------------------
        */

        $existingVisit =
            MedicalVisit::query()
                ->where(
                    'appointment_id',
                    $appointment->id
                )
                ->first();

        if ($existingVisit) {
            return redirect()
                ->route(
                    'admin.visits.show',
                    $existingVisit
                )
                ->with(
                    'error',
                    'Bệnh nhân của lịch hẹn này đã được tiếp nhận và đã có lượt khám.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Chỉ lịch BOOKED mới được check-in
        |--------------------------------------------------------------------------
        */

        if (
            $appointment
                ->appointmentStatus
                ?->name
            !==
            'BOOKED'
        ) {
            return redirect()
                ->route(
                    'admin.appointments.show',
                    $appointment
                )
                ->with(
                    'error',
                    'Chỉ lịch hẹn ở trạng thái đã đặt lịch mới được tiếp nhận.'
                );
        }

        $checkedInAppointmentStatus =
            AppointmentStatus::query()
                ->where(
                    'name',
                    'CHECKED_IN'
                )
                ->where(
                    'is_active',
                    true
                )
                ->first();

        $waitingVisitStatus =
            VisitStatus::query()
                ->where(
                    'name',
                    'WAITING'
                )
                ->where(
                    'is_active',
                    true
                )
                ->first();

        $waitingQueueStatus =
            QueueStatus::query()
                ->where(
                    'name',
                    'WAITING'
                )
                ->where(
                    'is_active',
                    true
                )
                ->first();

        if (! $checkedInAppointmentStatus) {
            return redirect()
                ->route(
                    'admin.appointments.show',
                    $appointment
                )
                ->with(
                    'error',
                    'Chưa có trạng thái “Đã tiếp nhận” đang được sử dụng. Vui lòng kiểm tra mục Trạng thái lịch hẹn.'
                );
        }

        if (! $waitingVisitStatus) {
            return redirect()
                ->route(
                    'admin.appointments.show',
                    $appointment
                )
                ->with(
                    'error',
                    'Chưa có trạng thái “Đang chờ” đang được sử dụng. Vui lòng kiểm tra mục Trạng thái lượt khám.'
                );
        }

        if (! $waitingQueueStatus) {
            return redirect()
                ->route(
                    'admin.appointments.show',
                    $appointment
                )
                ->with(
                    'error',
                    'Chưa có trạng thái “Đang chờ” đang được sử dụng. Vui lòng kiểm tra mục Trạng thái hàng đợi.'
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
            | Khóa appointment để tránh check-in trùng
            |--------------------------------------------------------------------------
            */

            $receptionSlot = AppointmentTimeSlot::findOrFail($appointment->appointment_time_slot_id);
            $receptionSchedule = DoctorSchedule::whereKey($receptionSlot->doctor_schedule_id)->lockForUpdate()->firstOrFail();
            $receptionSlot = AppointmentTimeSlot::whereKey($receptionSlot->id)->lockForUpdate()->firstOrFail();
            $receptionSlot->setRelation('doctorSchedule', $receptionSchedule);
            $lockedAppointment =
                Appointment::query()
                    ->whereKey(
                        $appointment->id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

            $lockedAppointment->load('appointmentStatus');
            $lockedAppointment->setRelation('appointmentTimeSlot', $receptionSlot);
            if ($lockedAppointment->appointment_time_slot_id !== $receptionSlot->id) {
                throw ValidationException::withMessages(['appointment' => 'Khung giờ của lịch hẹn vừa thay đổi. Vui lòng tải lại trang.']);
            }
            $lockedAppointment->assertReceptionDate();
            $doctor = Doctor::whereKey($lockedAppointment->doctor_id)->lockForUpdate()->firstOrFail();
            $patient = Patient::whereKey($lockedAppointment->patient_id)->lockForUpdate()->firstOrFail();
            $specialty = Specialty::whereKey($lockedAppointment->specialty_id)->lockForUpdate()->firstOrFail();
            $room = ExaminationRoom::whereKey($lockedAppointment->examination_room_id)->lockForUpdate()->firstOrFail();
            if ($receptionSchedule->status !== 'ACTIVE' || $receptionSlot->status !== 'ACTIVE'
                || $doctor->status !== 'ACTIVE' || $patient->status !== 'ACTIVE'
                || $specialty->status !== 'ACTIVE' || $room->status !== 'ACTIVE') {
                throw ValidationException::withMessages(['appointment' => 'Chưa thể tiếp nhận vì lịch làm việc, khung giờ, bác sĩ, bệnh nhân, chuyên khoa hoặc phòng khám đã ngừng hoạt động. Vui lòng kiểm tra và điều chỉnh lịch hẹn.']);
            }
            if ((int) $receptionSchedule->doctor_id !== (int) $lockedAppointment->doctor_id
                || (int) $receptionSchedule->specialty_id !== (int) $lockedAppointment->specialty_id
                || (int) $receptionSchedule->examination_room_id !== (int) $lockedAppointment->examination_room_id
                || $receptionSlot->start_time < $receptionSchedule->start_time
                || $receptionSlot->end_time > $receptionSchedule->end_time
                || $receptionSlot->start_time >= $receptionSlot->end_time) {
                throw ValidationException::withMessages(['appointment' => 'Thông tin lịch hẹn không còn khớp với ca làm việc. Vui lòng kiểm tra lại bác sĩ, chuyên khoa, phòng và khung giờ trước khi tiếp nhận.']);
            }
            if (! $doctor->canPracticeSpecialty((int) $lockedAppointment->specialty_id)) {
                throw ValidationException::withMessages(['appointment' => 'Bác sĩ chưa được phân chuyên khoa phù hợp với lịch hẹn. Vui lòng kiểm tra hồ sơ bác sĩ trước khi tiếp nhận.']);
            }
            $duplicateVisit = MedicalVisit::query()->join('appointments', 'appointments.id', '=', 'medical_visits.appointment_id')
                ->where('appointments.patient_id', $lockedAppointment->patient_id)
                ->where('appointments.appointment_time_slot_id', $receptionSlot->id)
                ->where('appointments.id', '!=', $lockedAppointment->id)
                ->select('medical_visits.*', 'appointments.appointment_code')->lockForUpdate()->first();
            if ($duplicateVisit) {
                throw ValidationException::withMessages(['appointment' => 'Bệnh nhân đã được tiếp nhận ở lịch hẹn '.$duplicateVisit->appointment_code.' trong cùng khung giờ. Không thể tạo thêm lượt khám trùng.']);
            }
            if ($lockedAppointment->appointmentStatus?->name !== 'BOOKED') {
                throw ValidationException::withMessages(['appointment' => 'Lịch hẹn không còn ở trạng thái đã đặt lịch.']);
            }
            if (! $lockedAppointment->appointmentStatus->allows_transition) {
                throw ValidationException::withMessages(['appointment' => 'Trạng thái lịch hẹn đang bị khóa thay đổi. Vui lòng kiểm tra mục Trạng thái lịch hẹn.']);
            }

            $alreadyHasVisit =
                MedicalVisit::query()
                    ->where(
                        'appointment_id',
                        $lockedAppointment->id
                    )
                    ->exists();

            if ($alreadyHasVisit) {
                DB::rollBack();

                $existingVisit =
                    MedicalVisit::query()
                        ->where(
                            'appointment_id',
                            $lockedAppointment->id
                        )
                        ->first();

                return redirect()
                    ->route(
                        'admin.visits.show',
                        $existingVisit
                    )
                    ->with(
                        'error',
                        'Bệnh nhân của lịch hẹn này đã được tiếp nhận trước đó.'
                    );
            }

            /*
            |--------------------------------------------------------------------------
            | Tạo lượt khám
            |--------------------------------------------------------------------------
            */

            $medicalVisit =
                MedicalVisit::create([
                    'visit_code' => $this->generateVisitCode(),

                    'appointment_id' => $lockedAppointment->id,

                    'patient_id' => $lockedAppointment->patient_id,

                    'doctor_id' => $lockedAppointment->doctor_id,

                    'specialty_id' => $lockedAppointment->specialty_id,

                    'examination_room_id' => $lockedAppointment
                        ->examination_room_id,

                    'visit_status_id' => $waitingVisitStatus->id,

                    'checked_in_at' => now(),

                    'examination_started_at' => null,

                    'examination_ended_at' => null,

                    'reception_source' => 'COUNTER',

                    'reception_notes' => 'Tiếp nhận từ lịch hẹn '
                        .
                        $lockedAppointment
                            ->appointment_code
                        .
                        '.',

                    'examination_slip_pdf_path' => null,
                ]);

            /*
            |--------------------------------------------------------------------------
            | Tạo hàng đợi khám ban đầu
            |--------------------------------------------------------------------------
            */

            ExaminationRoomQueue::create([
                'medical_visit_id' => $medicalVisit->id,

                'queue_status_id' => $waitingQueueStatus->id,

                'queue_number' => $this->nextQueueNumber(
                    (int) $lockedAppointment
                        ->examination_room_id
                ),

                'queue_type' => 'INITIAL_EXAM',

                'queued_at' => now(),

                'called_at' => null,

                'service_started_at' => null,

                'completed_at' => null,

                'call_count' => 0,

                'notes' => null,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Cập nhật lịch hẹn sang CHECKED_IN
            |--------------------------------------------------------------------------
            */

            $lockedAppointment->update([
                'appointment_status_id' => $checkedInAppointmentStatus->id,
            ]);

            $lockedAppointment->refresh();

            /*
            |--------------------------------------------------------------------------
            | Audit lịch hẹn
            |--------------------------------------------------------------------------
            */

            $this->writeAuditLog(
                action: 'CHECK_IN',
                appointment: $lockedAppointment,
                oldValues: $oldValues,
                newValues: $this->appointmentSnapshot(
                    $lockedAppointment
                ),
                description: 'Quản trị viên tiếp nhận lịch hẹn '
                    .
                    $lockedAppointment
                        ->appointment_code
                    .
                    ' và tạo lượt khám '
                    .
                    $medicalVisit->visit_code
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
                'admin.visits.show',
                $medicalVisit
            )
            ->with(
                'success',
                'Tiếp nhận thành công. Đã tạo lượt khám và đưa bệnh nhân vào hàng đợi.'
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
                    'Bệnh nhân đã được tiếp nhận nên không thể sửa thông tin đặt lịch. Vui lòng mở lượt khám để xem thông tin khám.'
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
                    'Bệnh nhân đã được tiếp nhận nên không thể sửa thông tin đặt lịch. Vui lòng mở lượt khám để xem thông tin khám.'
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
                    'patient_id' => 'Hồ sơ bệnh nhân đã ngừng hoạt động nên chưa thể đặt lịch. Vui lòng kiểm tra lại hồ sơ bệnh nhân.',
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

        if (! $slot) {

            return back()
                ->withInput()
                ->withErrors([
                    'appointment_time_slot_id' => 'Khung giờ đã chọn không còn nhận lịch hẹn. Vui lòng chọn khung giờ khác.',
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
            ! $this->slotHasCapacity(
                $slot->id
            )
        ) {

            return back()
                ->withInput()
                ->withErrors([
                    'appointment_time_slot_id' => 'Khung giờ này đã đủ số bệnh nhân có thể tiếp nhận. Vui lòng chọn khung giờ khác.',
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

            $slot = $this->lockBookableSlot($slot->id, $slot->doctor_schedule_id, $appointment->id);
            $this->assertBookingSelection($data, $slot);
            $this->assertNoDuplicateBooking($patient->id, $slot->id, $appointment->id);
            $appointment = Appointment::whereKey($appointment->id)->lockForUpdate()->firstOrFail();
            $appointment->load('appointmentStatus');
            if ($appointment->medicalVisit()->exists() || in_array($appointment->appointmentStatus?->name, ['CANCELLED', 'COMPLETED'], true)) {
                throw ValidationException::withMessages(['appointment_time_slot_id' => 'Lịch hẹn đã được tiếp nhận hoặc kết thúc.']);
            }
            $schedule = $slot->doctorSchedule;
            $appointment->update([
                'patient_id' => $patient->id,

                'doctor_id' => $schedule->doctor_id,

                'specialty_id' => $schedule->specialty_id,

                'examination_room_id' => $schedule->examination_room_id,

                'appointment_date' => $schedule
                    ->work_date
                    ->format('Y-m-d'),

                'appointment_time_slot_id' => $slot->id,

                'reason_for_visit' => $data['reason_for_visit'],

                'patient_notes' => $data['patient_notes']
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
                description: 'Quản trị viên cập nhật lịch hẹn '
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
                    'Bệnh nhân đã được tiếp nhận nên không thể hủy lịch hẹn ở bước này. Vui lòng mở lượt khám để xem tình trạng hiện tại.'
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

        if (! $cancelledStatus) {

            return redirect()
                ->route(
                    'admin.appointments.show',
                    $appointment
                )
                ->with(
                    'error',
                    'Chưa có trạng thái “Đã hủy” đang được sử dụng. Vui lòng kiểm tra mục Trạng thái lịch hẹn.'
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

            $appointment = Appointment::whereKey($appointment->id)->lockForUpdate()->firstOrFail();
            $appointment->load('appointmentStatus');
            if ($appointment->medicalVisit()->exists() || in_array($appointment->appointmentStatus?->name, ['CANCELLED', 'COMPLETED'], true)) {
                throw ValidationException::withMessages(['cancellation_reason' => 'Lịch hẹn đã được tiếp nhận hoặc kết thúc, không thể hủy.']);
            }
            $appointment->update([
                'appointment_status_id' => $cancelledStatus->id,

                'cancelled_at' => now(),

                'cancellation_reason' => $request
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
                description: 'Quản trị viên hủy lịch hẹn '
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

    private function getSelectableSchedules(?int $currentSlotId = null)
    {
        return DoctorSchedule::query()
            ->withQualifiedDoctor()
            ->with([
                'doctor', 'specialty', 'examinationRoom',
                'appointmentTimeSlots' => fn ($query) => $query->where('status', 'ACTIVE')
                    ->withCount(['appointments as booked_count' => fn ($bookings) => $bookings
                        ->whereHas('appointmentStatus', fn ($status) => $status->where('name', '!=', 'CANCELLED'))])
                    ->orderBy('start_time'),
            ])
            ->where('status', 'ACTIVE')
            ->whereHas('doctor', fn ($query) => $query->where('status', 'ACTIVE'))
            ->whereHas('specialty', fn ($query) => $query->where('status', 'ACTIVE'))
            ->whereHas('examinationRoom', fn ($query) => $query->where('status', 'ACTIVE'))
            ->where('work_date', '>=', today()->toDateString())
            ->where('work_date', '<', today()->addDays(Appointment::BOOKING_ADVANCE_DAYS + 1)->toDateString())
            ->orderBy('work_date')->orderBy('start_time')->get()
            ->filter(function (DoctorSchedule $schedule) use ($currentSlotId) {
                $schedule->appointmentTimeSlots->each(function (AppointmentTimeSlot $slot) use ($schedule, $currentSlotId) {
                    $slot->is_selectable = ((int) $slot->id === (int) $currentSlotId)
                        || ($this->isSlotFuture($schedule, $slot) && $slot->booked_count < $slot->max_patients);
                });

                return $schedule->appointmentTimeSlots->contains(fn ($slot) => $slot->is_selectable);
            })->values();
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

        if (! $slot) {
            return null;
        }

        $schedule =
            $slot->doctorSchedule;

        if (! $schedule) {
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
            ! $this->isSlotFuture(
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

        return $slotDateTime->isFuture();
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

        if (! $slot) {
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

    /*
    |--------------------------------------------------------------------------
    | Sinh mã lượt khám
    |--------------------------------------------------------------------------
    */

    private function generateVisitCode(): string
    {
        do {
            $code =
                'LK'
                .
                now()->format(
                    'ymdHis'
                )
                .
                strtoupper(
                    Str::random(3)
                );

        } while (
            MedicalVisit::query()
                ->where(
                    'visit_code',
                    $code
                )
                ->exists()
        );

        return $code;
    }

    /*
    |--------------------------------------------------------------------------
    | Sinh số thứ tự hàng đợi theo phòng và ngày
    |--------------------------------------------------------------------------
    */

    private function nextQueueNumber(
        int $examinationRoomId
    ): int {
        ExaminationRoom::whereKey($examinationRoomId)->lockForUpdate()->firstOrFail();
        $lastQueueNumber =
            ExaminationRoomQueue::query()
                ->whereDate(
                    'queued_at',
                    now()->toDateString()
                )
                ->whereHas(
                    'medicalVisit',
                    function ($query) use (
                        $examinationRoomId
                    ) {
                        $query->where(
                            'examination_room_id',
                            $examinationRoomId
                        );
                    }
                )
                ->orderByDesc(
                    'queue_number'
                )
                ->lockForUpdate()
                ->value(
                    'queue_number'
                );

        return
            ((int) $lastQueueNumber)
            +
            1;
    }

    private function lockBookableSlot(int $slotId, int $doctorScheduleId, ?int $ignoreAppointmentId = null): AppointmentTimeSlot
    {
        $schedule = DoctorSchedule::whereKey($doctorScheduleId)->lockForUpdate()->firstOrFail();
        $slot = AppointmentTimeSlot::whereKey($slotId)->lockForUpdate()->firstOrFail();
        $doctor = Doctor::whereKey($schedule->doctor_id)->lockForUpdate()->firstOrFail();
        $schedule->setRelation('doctor', $doctor);
        $slot->setRelation('doctorSchedule', $schedule);
        if (! $doctor->canPracticeSpecialty((int) $schedule->specialty_id)) {
            throw ValidationException::withMessages(['appointment_time_slot_id' => 'Bác sĩ chưa được phân chuyên khoa phù hợp với lịch làm việc này. Vui lòng chọn lịch khác hoặc cập nhật hồ sơ bác sĩ.']);
        }
        if (! $schedule->work_date->betweenIncluded(today(), today()->addDays(Appointment::BOOKING_ADVANCE_DAYS))) {
            throw ValidationException::withMessages(['appointment_time_slot_id' => 'Chỉ được đặt lịch từ ngày '.today()->format('d/m/Y').' đến ngày '.today()->addDays(Appointment::BOOKING_ADVANCE_DAYS)->format('d/m/Y').'. Vui lòng chọn lại khung giờ.']);
        }
        $bookings = Appointment::where('appointment_time_slot_id', $slotId)
            ->whereHas('appointmentStatus', fn ($query) => $query->where('name', '!=', 'CANCELLED'))
            ->when($ignoreAppointmentId, fn ($query) => $query->where('id', '!=', $ignoreAppointmentId))->count();
        if ($slot->status !== 'ACTIVE' || $schedule->status !== 'ACTIVE'
            || ! $this->isSlotFuture($schedule, $slot)
            || $slot->start_time >= $slot->end_time
            || $slot->start_time < $schedule->start_time || $slot->end_time > $schedule->end_time
            || $bookings >= $slot->max_patients
            || $schedule->doctor?->status !== 'ACTIVE' || $schedule->specialty?->status !== 'ACTIVE'
            || $schedule->examinationRoom?->status !== 'ACTIVE') {
            throw ValidationException::withMessages(['appointment_time_slot_id' => 'Không thể đặt vào khung giờ đã chọn vì khung giờ đã đầy, đã qua hoặc đã ngừng nhận lịch. Vui lòng chọn khung giờ khác.']);
        }

        return $slot;
    }

    private function assertBookingSelection(array $data, AppointmentTimeSlot $slot): void
    {
        foreach (['booking_doctor_id' => 'doctor_id', 'booking_room_id' => 'examination_room_id', 'booking_specialty_id' => 'specialty_id'] as $field => $column) {
            if (! empty($data[$field]) && (int) $data[$field] !== (int) $slot->doctorSchedule->{$column}) {
                throw ValidationException::withMessages(['appointment_time_slot_id' => 'Khung giờ khám không thuộc lựa chọn hiện tại. Vui lòng chọn lại lịch làm việc và khung giờ.']);
            }
        }
    }

    private function assertNoDuplicateBooking(int $patientId, int $slotId, ?int $ignoreId = null): void
    {
        $duplicate = Appointment::where('patient_id', $patientId)->where('appointment_time_slot_id', $slotId)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->whereHas('appointmentStatus', fn ($query) => $query->where('name', '!=', 'CANCELLED'))
            ->lockForUpdate()->first();
        if ($duplicate) {
            throw ValidationException::withMessages(['patient_id' => 'Bệnh nhân đã có lịch hẹn '.$duplicate->appointment_code.' trong khung giờ này. Vui lòng mở lịch hẹn đã có hoặc chọn khung giờ khác.']);
        }
    }

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
            'appointment_code' => $appointment->appointment_code,

            'patient_id' => $appointment->patient_id,

            'doctor_id' => $appointment->doctor_id,

            'specialty_id' => $appointment->specialty_id,

            'examination_room_id' => $appointment->examination_room_id,

            'appointment_date' => $appointment->appointment_date
                ?->format('Y-m-d'),

            'appointment_time_slot_id' => $appointment
                ->appointment_time_slot_id,

            'appointment_status_id' => $appointment
                ->appointment_status_id,

            'reason_for_visit' => $appointment
                ->reason_for_visit,

            'patient_notes' => $appointment
                ->patient_notes,

            'booked_at' => $appointment
                ->booked_at
                ?->format('Y-m-d H:i:s'),

            'cancelled_at' => $appointment
                ->cancelled_at
                ?->format('Y-m-d H:i:s'),

            'cancellation_reason' => $appointment
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
            'user_id' => Auth::id(),

            'role_name_snapshot' => 'Administrator',

            'action' => $action,

            'entity_type' => 'Appointment',

            'entity_id' => $appointment->id,

            'entity_business_code' => $appointment
                ->appointment_code,

            'old_values' => $oldValues
                    ? json_encode(
                        $oldValues,
                        JSON_UNESCAPED_UNICODE
                        |
                        JSON_UNESCAPED_SLASHES
                    )
                    : null,

            'new_values' => $newValues
                    ? json_encode(
                        $newValues,
                        JSON_UNESCAPED_UNICODE
                        |
                        JSON_UNESCAPED_SLASHES
                    )
                    : null,

            'description' => $description,

            'ip_address' => request()->ip(),

            'user_agent' => Str::limit(
                (string) request()
                    ->userAgent(),
                500,
                ''
            ),

            'performed_at' => now(),

            'created_at' => now(),
        ]);
    }
}
