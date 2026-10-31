<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentStatus;
use App\Models\AppointmentTimeSlot;
use App\Models\AuditLog;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Patient;
use App\Support\Portal\AppointmentChangePolicy;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AppointmentChangeController extends Controller
{
    public function __construct(
        private readonly AppointmentChangePolicy $policy
    ) {}

    public function cancelForm(Appointment $appointment): View
    {
        $this->assertOwner($appointment);

        $appointment->load(
            'appointmentStatus',
            'appointmentTimeSlot.doctorSchedule'
        );

        $problem = $this->policy->problem($appointment, 'cancel');

        return view(
            'portal.appointments.cancel',
            compact('appointment', 'problem')
        );
    }

    public function cancel(
        Request $request,
        Appointment $appointment
    ): RedirectResponse {
        $this->assertOwner($appointment);

        $data = $request->validate([
            'cancellation_reason' => [
                'required',
                'string',
                'min:5',
                'max:2000'
            ],
        ]);

        $this->assertAllowed($appointment, 'cancel');

        DB::transaction(function () use ($appointment, $data): void {
            $locked = Appointment::query()
                ->whereKey($appointment->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertOwner($locked);
            $this->assertAllowed($locked, 'cancel');

            $cancelled = AppointmentStatus::query()
                ->where('name', 'CANCELLED')
                ->where('is_active', true)
                ->first();

            if (! $cancelled) {
                throw ValidationException::withMessages([
                    'appointment' => 'Chưa có trạng thái Đã hủy hoạt động.',
                ]);
            }

            $old = $this->snapshot($locked);

            $locked->update([
                'appointment_status_id' => $cancelled->id,
                'cancelled_at' => now(),
                'cancellation_reason' => $data['cancellation_reason'],
            ]);

            $this->writeAudit('CANCEL', $locked, $old);
        }, 3);

        return redirect()
            ->route('patient.appointments.show', $appointment)
            ->with('success', 'Hủy lịch hẹn thành công.');
    }

    public function rescheduleForm(Appointment $appointment): View
    {
        $this->assertOwner($appointment);

        $appointment->load(
            'appointmentStatus',
            'appointmentTimeSlot.doctorSchedule'
        );

        $problem = $this->policy->problem(
            $appointment,
            'reschedule'
        );

        $slots = AppointmentTimeSlot::query()
            ->with([
                'doctorSchedule.doctor',
                'doctorSchedule.specialty',
                'doctorSchedule.examinationRoom',
            ])
            ->where('status', 'ACTIVE')
            ->where('id', '!=', $appointment->appointment_time_slot_id)
            ->whereHas('doctorSchedule', function ($query) use ($appointment) {
                $query
                    ->where('doctor_id', $appointment->doctor_id)
                    ->where('specialty_id', $appointment->specialty_id)
                    ->where('status', 'ACTIVE')
                    ->whereDate('work_date', '>=', today()->toDateString())
                    ->whereDate(
                        'work_date',
                        '<=',
                        today()
                            ->addDays(Appointment::BOOKING_ADVANCE_DAYS)
                            ->toDateString()
                    );
            })
            ->get()
            ->filter(
                fn(AppointmentTimeSlot $slot) =>
                $this->validTarget($slot)
                    && $this->remainingCapacity($slot) > 0
            )
            ->sortBy(
                fn(AppointmentTimeSlot $slot) =>
                $slot->doctorSchedule->work_date->format('Y-m-d')
                    . ' ' . $slot->start_time
            )
            ->values();

        return view(
            'portal.appointments.reschedule',
            compact('appointment', 'problem', 'slots')
        );
    }

    public function reschedule(
        Request $request,
        Appointment $appointment
    ): RedirectResponse {
        $this->assertOwner($appointment);

        $data = $request->validate([
            'appointment_time_slot_id' => [
                'required',
                'integer',
                'exists:appointment_time_slots,id',
            ],
        ]);

        $this->assertAllowed($appointment, 'reschedule');

        $targetId = (int) $data['appointment_time_slot_id'];

        if ($targetId === (int) $appointment->appointment_time_slot_id) {
            throw ValidationException::withMessages([
                'appointment_time_slot_id' =>
                'Vui lòng chọn khung giờ khác.',
            ]);
        }

        $targetScheduleId = AppointmentTimeSlot::query()
            ->findOrFail($targetId)
            ->doctor_schedule_id;

        DB::transaction(function () use (
            $appointment,
            $targetId,
            $targetScheduleId
        ): void {
            // Khóa dữ liệu trước khi kiểm tra số chỗ.
            $schedule = DoctorSchedule::query()
                ->whereKey($targetScheduleId)
                ->lockForUpdate()
                ->firstOrFail();

            $slot = AppointmentTimeSlot::query()
                ->whereKey($targetId)
                ->lockForUpdate()
                ->firstOrFail();

            $doctor = Doctor::query()
                ->whereKey($schedule->doctor_id)
                ->lockForUpdate()
                ->firstOrFail();

            $schedule->setRelation('doctor', $doctor);
            $slot->setRelation('doctorSchedule', $schedule);

            $patient = Patient::query()
                ->whereKey(Auth::user()?->patient?->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($patient->status !== 'ACTIVE') {
                throw ValidationException::withMessages([
                    'appointment' =>
                    'Hồ sơ bệnh nhân không còn hoạt động.',
                ]);
            }

            $locked = Appointment::query()
                ->whereKey($appointment->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertOwner($locked);
            $this->assertAllowed($locked, 'reschedule');

            if ((int) $locked->appointment_time_slot_id === $targetId) {
                throw ValidationException::withMessages([
                    'appointment_time_slot_id' =>
                    'Lịch đã được đổi sang khung giờ này.',
                ]);
            }

            if (
                (int) $slot->doctor_schedule_id !== (int) $schedule->id
                || (int) $schedule->doctor_id !== (int) $locked->doctor_id
                || (int) $schedule->specialty_id !== (int) $locked->specialty_id
                || ! $this->validTarget($slot)
                || $this->remainingCapacity($slot) < 1
            ) {
                throw ValidationException::withMessages([
                    'appointment_time_slot_id' =>
                    'Khung giờ mới không còn hợp lệ hoặc đã hết chỗ.',
                ]);
            }

            $duplicate = Appointment::query()
                ->where('patient_id', $locked->patient_id)
                ->where('appointment_time_slot_id', $targetId)
                ->where('id', '!=', $locked->id)
                ->whereHas(
                    'appointmentStatus',
                    fn($query) =>
                    $query->where('name', '!=', 'CANCELLED')
                )
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'appointment_time_slot_id' =>
                    'Bạn đã có lịch khác ở khung giờ này.',
                ]);
            }

            $old = $this->snapshot($locked);

            $locked->update([
                'appointment_date' =>
                $schedule->work_date->format('Y-m-d'),
                'appointment_time_slot_id' => $slot->id,
                'doctor_id' => $schedule->doctor_id,
                'specialty_id' => $schedule->specialty_id,
                'examination_room_id' =>
                $schedule->examination_room_id,
                'checkin_token' => (string) Str::uuid(),
            ]);

            $this->writeAudit('UPDATE', $locked, $old);
        }, 3);

        return redirect()
            ->route('patient.appointments.show', $appointment)
            ->with(
                'success',
                'Đổi lịch thành công. Vui lòng kiểm tra ngày và giờ mới.'
            );
    }

    private function assertOwner(Appointment $appointment): void
    {
        $patient = Auth::user()?->patient;

        if (
            ! $patient
            || $patient->status !== 'ACTIVE'
            || (int) $appointment->patient_id !== (int) $patient->id
        ) {
            abort(404);
        }
    }

    private function assertAllowed(
        Appointment $appointment,
        string $action
    ): void {
        if ($message = $this->policy->problem($appointment, $action)) {
            throw ValidationException::withMessages([
                'appointment' => $message,
            ]);
        }
    }

    private function validTarget(AppointmentTimeSlot $slot): bool
    {
        $schedule = $slot->doctorSchedule;

        if (
            ! $schedule
            || $slot->status !== 'ACTIVE'
            || $schedule->status !== 'ACTIVE'
            || $schedule->doctor?->status !== 'ACTIVE'
            || $schedule->specialty?->status !== 'ACTIVE'
            || $schedule->examinationRoom?->status !== 'ACTIVE'
            || ! $schedule->doctor->canPracticeSpecialty(
                (int) $schedule->specialty_id
            )
            || ! $schedule->work_date->betweenIncluded(
                today(),
                today()->addDays(Appointment::BOOKING_ADVANCE_DAYS)
            )
            || $slot->start_time >= $slot->end_time
            || $slot->start_time < $schedule->start_time
            || $slot->end_time > $schedule->end_time
            || (int) $slot->max_patients < 1
        ) {
            return false;
        }

        return Carbon::parse(
            $schedule->work_date->format('Y-m-d')
                . ' ' . $slot->start_time
        )->isFuture();
    }

    private function remainingCapacity(
        AppointmentTimeSlot $slot
    ): int {
        $bookings = Appointment::query()
            ->where('appointment_time_slot_id', $slot->id)
            ->whereHas(
                'appointmentStatus',
                fn($query) =>
                $query->where('name', '!=', 'CANCELLED')
            )
            ->count();

        return (int) $slot->max_patients - $bookings;
    }

    private function snapshot(Appointment $appointment): array
    {
        return [
            'appointment_status_id' =>
            $appointment->appointment_status_id,
            'appointment_time_slot_id' =>
            $appointment->appointment_time_slot_id,
            'appointment_date' =>
            $appointment->appointment_date?->format('Y-m-d'),
            'doctor_id' => $appointment->doctor_id,
            'specialty_id' => $appointment->specialty_id,
            'examination_room_id' =>
            $appointment->examination_room_id,
            'cancelled_at' =>
            $appointment->cancelled_at?->toIso8601String(),
        ];
    }

    private function writeAudit(
        string $action,
        Appointment $appointment,
        array $old
    ): void {
        AuditLog::create([
            'user_id' => Auth::id(),
            'role_name_snapshot' => 'Patient',
            'action' => $action,
            'entity_type' => 'Appointment',
            'entity_id' => $appointment->id,
            'entity_business_code' =>
            $appointment->appointment_code,
            'old_values' => $old,
            'new_values' => $this->snapshot($appointment),
            'description' =>
            'Bệnh nhân '
                . ($action === 'CANCEL' ? 'hủy' : 'đổi')
                . ' lịch hẹn ' . $appointment->appointment_code,
            'ip_address' => request()->ip(),
            'user_agent' => Str::limit(
                (string) request()->userAgent(),
                500,
                ''
            ),
            'performed_at' => now(),
        ]);
    }
}
