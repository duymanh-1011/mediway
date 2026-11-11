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
use App\Services\AppointmentOverlap;
use App\Support\Portal\PatientBookingWindow;
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

        if (! $slot) {
            return redirect()
                ->route('patient.doctors.index')
                ->withErrors([
                    'appointment_time_slot_id' =>
                    'Khung giờ đã chọn không còn nhận lịch. Vui lòng chọn khung giờ khác.',
                ]);
        }

        // GET luôn hiển thị trang xác nhận.
        // KHÔNG chuyển sang M09 tại bước này.
        $patient->load(['user', 'detail']);

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
        $patient->load(['user', 'detail']);

        // Chỉ kiểm tra hồ sơ khi bệnh nhân
        // bấm XÁC NHẬN ĐẶT LỊCH.
        if ($missing = $profileGate->missingFields($patient)) {
            throw ValidationException::withMessages(['profile' => $missing])
                ->redirectTo(route('patient.appointments.create', $slotId));
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
            // Match the schedule -> slot -> doctor -> patient lock order used
            // by rescheduling, so concurrent actions do not invert locks.
            $lockedSlot = $this->lockBookableSlot(
                (int) $slot->id,
                (int) $slot->doctor_schedule_id
            );

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
                throw ValidationException::withMessages(['profile' => $missing])
                    ->redirectTo(route('patient.appointments.create', $slotId));
            }

            // Không cho bệnh nhân đặt trùng suất.
            AppointmentOverlap::ensureAvailable($lockedPatient->id, $lockedSlot);

            $schedule = $lockedSlot->doctorSchedule;
            PatientBookingWindow::ensureAllows($schedule, $lockedSlot);

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
            ->with(['doctorSchedule.doctor.specialty', 'doctorSchedule.specialty', 'doctorSchedule.examinationRoom'])
            ->find($slotId);

        if (! $slot || ! $slot->doctorSchedule) {
            return null;
        }

        $schedule = $slot->doctorSchedule;

        if (
            $slot->status !== 'ACTIVE'
            || $schedule->status !== 'ACTIVE'
            || $schedule->doctor?->status !== 'ACTIVE'
            || $schedule->specialty?->status !== 'ACTIVE'
            || $schedule->examinationRoom?->status !== 'ACTIVE'
            || ! $schedule->doctor->canPracticeSpecialty((int) $schedule->specialty_id)
            || ! PatientBookingWindow::allows($schedule, $slot)
        ) {
            return null;
        }

        return $slot;
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
            (int) $slot->doctor_schedule_id !== (int) $schedule->id
            || ! PatientBookingWindow::allows($schedule, $slot)
            || $doctor->status !== 'ACTIVE'
            || $schedule->specialty?->status !== 'ACTIVE'
            || $schedule->examinationRoom?->status !== 'ACTIVE'
        ) {
            throw ValidationException::withMessages([
                'appointment_time_slot_id' =>
                'Khung giờ nằm ngoài thời gian đặt lịch hoặc đã ngừng nhận lịch.',
            ]);
        }

        return $slot;
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
