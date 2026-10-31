<?php
namespace App\Support\Portal;
use App\Models\Appointment;
use Carbon\Carbon;
class AppointmentChangePolicy
{
    public function enabled(string $action): bool
    {
        if (
            ! in_array($action, ['cancel', 'reschedule'], true)
            || config('patient_appointments.self_service_approved') !== true
        ) {
            return false;
        }
        $value = config("patient_appointments.{$action}_cutoff_minutes");
        return (is_int($value) && $value >= 0)
            || (is_string($value) && ctype_digit($value));
    }
    public function problem(Appointment $appointment, string $action): ?string
    {
        if (! $this->enabled($action)) {
            return 'Chức năng đang chờ phê duyệt chính sách D05.';
        }
        $appointment->loadMissing(
            'appointmentStatus',
            'appointmentTimeSlot.doctorSchedule'
        );
        if (
            $appointment->appointmentStatus?->name !== 'BOOKED'
            || $appointment->cancelled_at !== null
            || $appointment->medicalVisit()->exists()
        ) {
            return 'Lịch đã hủy, đã tiếp nhận hoặc không ở trạng thái cho phép.';
        }
        $slot = $appointment->appointmentTimeSlot;
        $schedule = $slot?->doctorSchedule;
        if (! $slot || ! $schedule) {
            return 'Không tìm thấy thông tin ca khám.';
        }
        $start = Carbon::parse(
            $schedule->work_date->format('Y-m-d')
            . ' ' . $slot->start_time
        );
        $minutes = (int) config("patient_appointments.{$action}_cutoff_minutes");
        if (now()->greaterThanOrEqualTo(
            $start->copy()->subMinutes($minutes)
        )) {
            return 'Đã quá hạn hủy hoặc đổi lịch.';
        }
        return null;
    }
}
