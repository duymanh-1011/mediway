<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\StoreAppointmentRequest;
use App\Models\Appointment;
use App\Models\AppointmentStatus;
use App\Models\AppointmentTimeSlot;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    public function index(): View
    {
        $patient =
            $this->currentPatient();

        /*
    |--------------------------------------------------------------------------
    | M06.3 - Lịch sử lịch hẹn của chính bệnh nhân
    |--------------------------------------------------------------------------
    |
    | M06 sử dụng appointments + schedules + slots + statuses.
    | medical_visits / queue thuộc M08 nên không kéo vào màn lịch sử M06.
    |
    */

        $appointments =
            Appointment::query()
            ->where(
                'patient_id',
                $patient->id
            )
            ->with([
                'doctor',
                'specialty',
                'examinationRoom',
                'appointmentTimeSlot.doctorSchedule',
                'appointmentStatus',
            ])
            ->orderByDesc(
                'appointment_date'
            )
            ->orderByDesc(
                'id'
            )
            ->paginate(
                10
            );

        return view(
            'portal.appointments.index',
            compact(
                'appointments'
            )
        );
    }
    public function create(
        AppointmentTimeSlot $slot
    ): View|RedirectResponse {
        $patient =
            $this->currentPatient();

        $slot =
            $this->getValidatedSlot(
                $slot->id
            );

        if (
            ! $slot
            ||
            ! $this->slotHasCapacity(
                $slot->id
            )
        ) {
            return redirect()
                ->route(
                    'patient.doctors.index'
                )
                ->withErrors([
                    'appointment_time_slot_id' =>
                    'Khung giờ đã chọn không còn nhận lịch hẹn. Vui lòng chọn khung giờ khác.',
                ]);
        }

        $slot->load([
            'doctorSchedule.doctor',
            'doctorSchedule.specialty',
            'doctorSchedule.examinationRoom',
        ]);

        return view(
            'portal.appointments.create',
            compact(
                'patient',
                'slot'
            )
        );
    }

    public function store(
        StoreAppointmentRequest $request
    ): RedirectResponse {
        $data =
            $request->validated();

        /*
        |--------------------------------------------------------------------------
        | Patient lấy từ tài khoản đăng nhập
        |--------------------------------------------------------------------------
        */

        $patient =
            $this->currentPatient();

        /*
        |--------------------------------------------------------------------------
        | Kiểm tra slot ban đầu
        |--------------------------------------------------------------------------
        */

        $slot =
            $this->getValidatedSlot(
                (int) $data['appointment_time_slot_id']
            );

        if (! $slot) {
            return back()
                ->withInput()
                ->withErrors([
                    'appointment_time_slot_id' =>
                    'Khung giờ đã chọn không còn nhận lịch hẹn. Vui lòng chọn khung giờ khác.',
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
                    'appointment_time_slot_id' =>
                    'Khung giờ này đã đủ số bệnh nhân có thể tiếp nhận. Vui lòng chọn khung giờ khác.',
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Trạng thái BOOKED
        |--------------------------------------------------------------------------
        */

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
                    'appointment_time_slot_id' =>
                    'Chưa có trạng thái “Đã đặt lịch” đang được sử dụng. Vui lòng liên hệ quản trị viên.',
                ]);
        }

        DB::beginTransaction();

        try {
            /*
            |--------------------------------------------------------------------------
            | Khóa Patient hiện tại
            |--------------------------------------------------------------------------
            */

            $lockedPatient =
                Patient::query()
                ->whereKey(
                    $patient->id
                )
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $lockedPatient->status
                !==
                'ACTIVE'
            ) {
                throw ValidationException::withMessages([
                    'appointment_time_slot_id' =>
                    'Hồ sơ bệnh nhân đã ngừng hoạt động nên chưa thể đặt lịch.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Khóa slot + schedule để chống overbooking
            |--------------------------------------------------------------------------
            */

            $slot =
                $this->lockBookableSlot(
                    $slot->id,
                    $slot
                        ->doctor_schedule_id
                );

            /*
            |--------------------------------------------------------------------------
            | Chặn submit trùng
            |--------------------------------------------------------------------------
            */

            $this->assertNoDuplicateBooking(
                $lockedPatient->id,
                $slot->id
            );

            $schedule =
                $slot->doctorSchedule;

            /*
            |--------------------------------------------------------------------------
            | Tạo Appointment
            |--------------------------------------------------------------------------
            */

            $appointment =
                Appointment::create([
                    'appointment_code' =>
                    $this->generateAppointmentCode(),

                    'patient_id' =>
                    $lockedPatient->id,

                    'doctor_id' =>
                    $schedule->doctor_id,

                    'specialty_id' =>
                    $schedule->specialty_id,

                    'examination_room_id' =>
                    $schedule->examination_room_id,

                    'appointment_date' =>
                    $schedule
                        ->work_date
                        ->format(
                            'Y-m-d'
                        ),

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

            DB::commit();
        } catch (\Throwable $exception) {
            DB::rollBack();

            throw $exception;
        }

        return redirect()
            ->route(
                'patient.appointments.show',
                $appointment
            )
            ->with(
                'success',
                'Đặt lịch thành công. Mã lịch hẹn: '
                    .
                    $appointment
                    ->appointment_code
                    .
                    '.'
            );
    }

    public function show(
        Appointment $appointment
    ): View {
        $patient =
            $this->currentPatient();

        /*
        |--------------------------------------------------------------------------
        | Owner validation
        |--------------------------------------------------------------------------
        |
        | Patient A không được xem Appointment của Patient B.
        |
        */

        if (
            (int) $appointment
                ->patient_id
            !==
            (int) $patient->id
        ) {
            abort(404);
        }

        $appointment->load([
            'doctor',
            'specialty',
            'examinationRoom',
            'appointmentTimeSlot.doctorSchedule',
            'appointmentStatus',
        ]);

        return view(
            'portal.appointments.show',
            compact(
                'appointment'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Patient hiện tại
    |--------------------------------------------------------------------------
    */

    private function currentPatient(): Patient
    {
        $user =
            Auth::user();

        $user?->loadMissing(
            'patient'
        );

        $patient =
            $user?->patient;

        if (
            ! $patient
            ||
            $patient->status
            !==
            'ACTIVE'
        ) {
            throw ValidationException::withMessages([
                'auth' =>
                'Hồ sơ bệnh nhân không còn đủ điều kiện sử dụng Patient Portal.',
            ]);
        }

        return $patient;
    }

    /*
    |--------------------------------------------------------------------------
    | Validate slot
    |--------------------------------------------------------------------------
    */

    private function getValidatedSlot(
        int $slotId
    ): ?AppointmentTimeSlot {
        $slot =
            AppointmentTimeSlot::query()
            ->with(
                'doctorSchedule'
            )
            ->find(
                $slotId
            );

        if (! $slot) {
            return null;
        }

        $schedule =
            $slot->doctorSchedule;

        if (! $schedule) {
            return null;
        }

        if (
            $slot->status
            !==
            'ACTIVE'
            ||
            $schedule->status
            !==
            'ACTIVE'
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
    | Slot phải ở tương lai
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
                    ->format(
                        'Y-m-d'
                    )
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
            $slotDateTime
            ->isFuture();
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
            (int) $slot
                ->max_patients;
    }

    /*
    |--------------------------------------------------------------------------
    | Appointment chưa CANCELLED
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
    | Khóa slot để chống 2 request tranh suất cuối
    |--------------------------------------------------------------------------
    */

    private function lockBookableSlot(
        int $slotId,
        int $doctorScheduleId
    ): AppointmentTimeSlot {
        $schedule =
            DoctorSchedule::query()
            ->whereKey(
                $doctorScheduleId
            )
            ->lockForUpdate()
            ->firstOrFail();

        $slot =
            AppointmentTimeSlot::query()
            ->whereKey(
                $slotId
            )
            ->lockForUpdate()
            ->firstOrFail();

        $doctor =
            Doctor::query()
            ->whereKey(
                $schedule->doctor_id
            )
            ->lockForUpdate()
            ->firstOrFail();

        $schedule->setRelation(
            'doctor',
            $doctor
        );

        $slot->setRelation(
            'doctorSchedule',
            $schedule
        );

        /*
        |--------------------------------------------------------------------------
        | Doctor phải đủ điều kiện khám chuyên khoa
        |--------------------------------------------------------------------------
        */

        if (
            ! $doctor->canPracticeSpecialty(
                (int) $schedule
                    ->specialty_id
            )
        ) {
            throw ValidationException::withMessages([
                'appointment_time_slot_id' =>
                'Bác sĩ chưa được phân chuyên khoa phù hợp với lịch làm việc này. Vui lòng chọn lịch khác.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Giới hạn ngày đặt trước giống Admin
        |--------------------------------------------------------------------------
        */

        if (
            ! $schedule
                ->work_date
                ->betweenIncluded(
                    today(),
                    today()->addDays(
                        Appointment::BOOKING_ADVANCE_DAYS
                    )
                )
        ) {
            throw ValidationException::withMessages([
                'appointment_time_slot_id' =>
                'Chỉ được đặt lịch từ ngày '
                    .
                    today()->format(
                        'd/m/Y'
                    )
                    .
                    ' đến ngày '
                    .
                    today()
                    ->addDays(
                        Appointment::BOOKING_ADVANCE_DAYS
                    )
                    ->format(
                        'd/m/Y'
                    )
                    .
                    '. Vui lòng chọn lại khung giờ.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Đếm lại capacity sau khi đã lock
        |--------------------------------------------------------------------------
        */

        $bookings =
            Appointment::query()
            ->where(
                'appointment_time_slot_id',
                $slotId
            )
            ->whereHas(
                'appointmentStatus',
                fn($query) =>
                $query->where(
                    'name',
                    '!=',
                    'CANCELLED'
                )
            )
            ->count();

        if (
            $slot->status
            !==
            'ACTIVE'
            ||
            $schedule->status
            !==
            'ACTIVE'
            ||
            ! $this->isSlotFuture(
                $schedule,
                $slot
            )
            ||
            $slot->start_time
            >=
            $slot->end_time
            ||
            $slot->start_time
            <
            $schedule->start_time
            ||
            $slot->end_time
            >
            $schedule->end_time
            ||
            $bookings
            >=
            $slot->max_patients
            ||
            $schedule->doctor
            ?->status
            !==
            'ACTIVE'
            ||
            $schedule->specialty
            ?->status
            !==
            'ACTIVE'
            ||
            $schedule->examinationRoom
            ?->status
            !==
            'ACTIVE'
        ) {
            throw ValidationException::withMessages([
                'appointment_time_slot_id' =>
                'Không thể đặt vào khung giờ đã chọn vì khung giờ đã đầy, đã qua hoặc đã ngừng nhận lịch. Vui lòng chọn khung giờ khác.',
            ]);
        }

        return $slot;
    }

    /*
    |--------------------------------------------------------------------------
    | Chặn cùng Patient submit lại đúng cùng slot
    |--------------------------------------------------------------------------
    */

    private function assertNoDuplicateBooking(
        int $patientId,
        int $slotId
    ): void {
        $duplicate =
            Appointment::query()
            ->where(
                'patient_id',
                $patientId
            )
            ->where(
                'appointment_time_slot_id',
                $slotId
            )
            ->whereHas(
                'appointmentStatus',
                fn($query) =>
                $query->where(
                    'name',
                    '!=',
                    'CANCELLED'
                )
            )
            ->lockForUpdate()
            ->first();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'appointment_time_slot_id' =>
                'Bạn đã có lịch hẹn '
                    .
                    $duplicate
                    ->appointment_code
                    .
                    ' trong khung giờ này. Vui lòng mở lịch hẹn đã có hoặc chọn khung giờ khác.',
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Sinh mã Appointment
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
                    Str::random(
                        6
                    )
                );
        } while (
            Appointment::query()
            ->where(
                'appointment_code',
                $code
            )
            ->exists()
        );

        return $code;
    }
}
