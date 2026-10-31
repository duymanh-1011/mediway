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
use App\Support\Portal\BookingProfileGate;
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
        $patient = $this->currentPatient();

        $appointments = Appointment::query()
            ->where('patient_id', $patient->id)
            ->with([
                'doctor',
                'specialty',
                'examinationRoom',
                'appointmentTimeSlot.doctorSchedule',
                'appointmentStatus',
            ])
            ->orderByDesc('appointment_date')
            ->orderByDesc('id')
            ->paginate(10);

        return view('portal.appointments.index', compact('appointments'));
    }

    public function create(AppointmentTimeSlot $slot): View|RedirectResponse
    {
        $patient = $this->currentPatient();

        $slot = $this->getValidatedSlot((int) $slot->id);

        if (! $slot || ! $this->slotHasCapacity((int) $slot->id)) {
            return redirect()
                ->route('patient.doctors.index')
                ->withErrors([
                    'appointment_time_slot_id' =>
                    'Khung giờ đã chọn không còn nhận lịch. Vui lòng chọn khung giờ khác.',
                ]);
        }

        // GET luôn hiển thị trang xác nhận.
        // KHÔNG chuyển sang M09 tại bước này.
        $patient->loadMissing(['user', 'detail']);

        $slot->load([
            'doctorSchedule.doctor',
            'doctorSchedule.specialty',
            'doctorSchedule.examinationRoom',
        ]);

        return view(
            'portal.appointments.create',
            compact('patient', 'slot')
        );
    }

    public function store(StoreAppointmentRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $patient = $this->currentPatient();

        $slotId = (int) $data['appointment_time_slot_id'];

        $profileGate = app(BookingProfileGate::class);

        // Chỉ kiểm tra hồ sơ khi bệnh nhân
        // bấm XÁC NHẬN ĐẶT LỊCH.
        if ($profileGate->missingFields($patient) !== []) {
            return $profileGate->redirectToProfile(
                $patient,
                $slotId,
                [
                    'reason_for_visit' => $data['reason_for_visit'],
                    'patient_notes' => $data['patient_notes'] ?? null,
                ]
            );
        }

        // Hồ sơ đầy đủ: tiếp tục kiểm tra suất khám.
        $slot = $this->getValidatedSlot($slotId);

        if (! $slot) {
            return back()
                ->withInput()
                ->withErrors([
                    'appointment_time_slot_id' =>
                    'Khung giờ đã chọn không còn nhận lịch. Vui lòng chọn khung giờ khác.',
                ]);
        }

        if (! $this->slotHasCapacity((int) $slot->id)) {
            return back()
                ->withInput()
                ->withErrors([
                    'appointment_time_slot_id' =>
                    'Khung giờ đã đầy. Vui lòng chọn khung giờ khác.',
                ]);
        }

        $bookedStatus = AppointmentStatus::query()
            ->where('name', 'BOOKED')
            ->where('is_active', true)
            ->first();

        if (! $bookedStatus) {
            return back()
                ->withInput()
                ->withErrors([
                    'appointment_time_slot_id' =>
                    'Trạng thái đặt lịch chưa được cấu hình. Vui lòng liên hệ quản trị viên.',
                ]);
        }

        DB::beginTransaction();

        try {
            // Khóa bệnh nhân trước khi tạo lịch.
            $lockedPatient = Patient::query()
                ->whereKey($patient->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedPatient->status !== 'ACTIVE') {
                throw ValidationException::withMessages([
                    'appointment_time_slot_id' =>
                    'Hồ sơ bệnh nhân đã ngừng hoạt động.',
                ]);
            }

            // Kiểm tra lại hồ sơ trong giao dịch.
            $lockedPatient->load(['user', 'detail']);

            $missing = $profileGate->missingFields($lockedPatient);

            if ($missing !== []) {
                throw ValidationException::withMessages([
                    'profile' =>
                    'Hồ sơ chưa đầy đủ: '
                        . implode(', ', $missing) . '.',
                ]);
            }

            // Giữ cơ chế khóa lịch và slot chống
            // nhiều người cùng đặt vượt sức chứa.
            $lockedSlot = $this->lockBookableSlot(
                (int) $slot->id,
                (int) $slot->doctor_schedule_id
            );

            // Không cho bệnh nhân đặt trùng suất.
            $this->assertNoDuplicateBooking(
                (int) $lockedPatient->id,
                (int) $lockedSlot->id
            );

            $schedule = $lockedSlot->doctorSchedule;

            // Chỉ tạo Appointment.
            // KHÔNG sửa thông tin hồ sơ bệnh nhân.
            $appointment = Appointment::create([
                'appointment_code' => $this->generateAppointmentCode(),
                'patient_id' => $lockedPatient->id,
                'doctor_id' => $schedule->doctor_id,
                'specialty_id' => $schedule->specialty_id,
                'examination_room_id' => $schedule->examination_room_id,
                'appointment_date' => $schedule->work_date->format('Y-m-d'),
                'appointment_time_slot_id' => $lockedSlot->id,
                'appointment_status_id' => $bookedStatus->id,
                'checkin_token' => (string) Str::uuid(),
                'reason_for_visit' => $data['reason_for_visit'],
                'patient_notes' => $data['patient_notes'] ?? null,
                'booked_at' => now(),
                'cancelled_at' => null,
                'cancellation_reason' => null,
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return redirect()
            ->route('patient.appointments.show', $appointment)
            ->with(
                'success',
                'Đặt lịch thành công. Mã: '
                    . $appointment->appointment_code
            );
    }

    public function show(Appointment $appointment): View
    {
        $patient = $this->currentPatient();

        if ((int) $appointment->patient_id !== (int) $patient->id) {
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
            compact('appointment')
        );
    }

    private function currentPatient(): Patient
    {
        $user = Auth::user();

        $user?->loadMissing('patient');

        $patient = $user?->patient;

        if (! $patient || $patient->status !== 'ACTIVE') {
            throw ValidationException::withMessages([
                'auth' =>
                'Hồ sơ bệnh nhân không đủ điều kiện sử dụng Patient Portal.',
            ]);
        }

        return $patient;
    }

    private function getValidatedSlot(int $slotId): ?AppointmentTimeSlot
    {
        $slot = AppointmentTimeSlot::query()
            ->with('doctorSchedule')
            ->find($slotId);

        if (! $slot || ! $slot->doctorSchedule) {
            return null;
        }

        $schedule = $slot->doctorSchedule;

        if (
            $slot->status !== 'ACTIVE'
            || $schedule->status !== 'ACTIVE'
        ) {
            return null;
        }

        return $this->isSlotFuture($schedule, $slot)
            ? $slot
            : null;
    }

    private function isSlotFuture(
        DoctorSchedule $schedule,
        AppointmentTimeSlot $slot
    ): bool {
        $startsAt = Carbon::parse(
            $schedule->work_date->format('Y-m-d')
                . ' '
                . substr((string) $slot->start_time, 0, 8)
        );

        return $startsAt->isFuture();
    }

    private function slotHasCapacity(int $slotId): bool
    {
        $slot = AppointmentTimeSlot::find($slotId);

        return $slot !== null
            && $this->getActiveBookingCount($slotId)
            < (int) $slot->max_patients;
    }

    private function getActiveBookingCount(int $slotId): int
    {
        return DB::table('appointments')
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

    private function lockBookableSlot(
        int $slotId,
        int $doctorScheduleId
    ): AppointmentTimeSlot {
        $schedule = DoctorSchedule::query()
            ->whereKey($doctorScheduleId)
            ->lockForUpdate()
            ->firstOrFail();

        $slot = AppointmentTimeSlot::query()
            ->whereKey($slotId)
            ->lockForUpdate()
            ->firstOrFail();

        $doctor = Doctor::query()
            ->whereKey($schedule->doctor_id)
            ->lockForUpdate()
            ->firstOrFail();

        $schedule->setRelation('doctor', $doctor);

        $slot->setRelation('doctorSchedule', $schedule);

        if (
            ! $doctor->canPracticeSpecialty(
                (int) $schedule->specialty_id
            )
        ) {
            throw ValidationException::withMessages([
                'appointment_time_slot_id' =>
                'Bác sĩ chưa được phân chuyên khoa phù hợp. Vui lòng chọn lịch khác.',
            ]);
        }

        if (
            ! $schedule->work_date->betweenIncluded(
                today(),
                today()->addDays(Appointment::BOOKING_ADVANCE_DAYS)
            )
        ) {
            throw ValidationException::withMessages([
                'appointment_time_slot_id' =>
                'Ngày khám không nằm trong khoảng thời gian cho phép đặt trước.',
            ]);
        }

        // Đếm lại sau khi khóa database.
        $bookings = Appointment::query()
            ->where('appointment_time_slot_id', $slotId)
            ->whereHas(
                'appointmentStatus',
                fn($query) =>
                $query->where('name', '!=', 'CANCELLED')
            )
            ->count();

        if (
            $slot->status !== 'ACTIVE'
            || $schedule->status !== 'ACTIVE'
            || ! $this->isSlotFuture($schedule, $slot)
            || $slot->start_time >= $slot->end_time
            || $slot->start_time < $schedule->start_time
            || $slot->end_time > $schedule->end_time
            || $bookings >= (int) $slot->max_patients
            || $doctor->status !== 'ACTIVE'
            || $schedule->specialty?->status !== 'ACTIVE'
            || $schedule->examinationRoom?->status !== 'ACTIVE'
        ) {
            throw ValidationException::withMessages([
                'appointment_time_slot_id' =>
                'Khung giờ không hợp lệ, đã qua, đã đầy hoặc đã ngừng nhận lịch.',
            ]);
        }

        return $slot;
    }

    private function assertNoDuplicateBooking(
        int $patientId,
        int $slotId
    ): void {
        $duplicate = Appointment::query()
            ->where('patient_id', $patientId)
            ->where('appointment_time_slot_id', $slotId)
            ->whereHas(
                'appointmentStatus',
                fn($query) =>
                $query->where('name', '!=', 'CANCELLED')
            )
            ->lockForUpdate()
            ->first();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'appointment_time_slot_id' =>
                'Bạn đã có lịch hẹn '
                    . $duplicate->appointment_code
                    . ' trong khung giờ này.',
            ]);
        }
    }

    private function generateAppointmentCode(): string
    {
        do {
            $code = 'LH'
                . now()->format('ymd')
                . strtoupper(Str::random(6));
        } while (
            Appointment::query()
            ->where('appointment_code', $code)
            ->exists()
        );

        return $code;
    }
}
