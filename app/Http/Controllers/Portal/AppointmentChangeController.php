<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentStatus;
use App\Models\AuditLog;
use App\Support\Portal\AppointmentChangePolicy;
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
            'Bệnh nhân hủy lịch hẹn ' . $appointment->appointment_code,
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
