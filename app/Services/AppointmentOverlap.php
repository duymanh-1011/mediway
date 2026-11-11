<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AppointmentTimeSlot;
use Illuminate\Validation\ValidationException;

class AppointmentOverlap
{
    /** Called after locking the patient's row, including when two targets are different slots. */
    public static function ensureAvailable(int $patientId, AppointmentTimeSlot $slot, ?int $ignore = null, string $field = 'appointment_time_slot_id'): void
    {
        $start = strlen($slot->start_time) === 5 ? $slot->start_time.':00' : $slot->start_time;
        $end = strlen($slot->end_time) === 5 ? $slot->end_time.':00' : $slot->end_time;
        $overlap = Appointment::where('patient_id', $patientId)
            ->whereDate('appointment_date', $slot->doctorSchedule->work_date->toDateString())
            ->when($ignore !== null, fn ($query) => $query->whereKeyNot($ignore))
            ->whereHas('appointmentStatus', fn ($status) => $status->where('name', '!=', 'CANCELLED'))
            ->whereHas('appointmentTimeSlot', fn ($target) => $target->whereTime('start_time', '<', $end)->whereTime('end_time', '>', $start))
            ->lockForUpdate()->first(['id', 'appointment_code']);
        if ($overlap) {
            throw ValidationException::withMessages([$field => 'Bệnh nhân đã có lịch hẹn '.$overlap->appointment_code.' trùng giờ.']);
        }
    }
}
