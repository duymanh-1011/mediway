<?php

namespace App\Support\Portal;

use App\Models\AppointmentTimeSlot;
use App\Models\DoctorSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class PatientBookingWindow
{
    public static function firstDate(): string
    {
        return self::now()->toDateString();
    }

    public static function lastDate(): string
    {
        return self::now()->addDays(2)->toDateString();
    }

    public static function ensureAllows(DoctorSchedule $schedule, AppointmentTimeSlot $slot): void
    {
        if (! self::allows($schedule, $slot)) {
            throw ValidationException::withMessages([
                'appointment_time_slot_id' => 'Khung giờ nằm ngoài thời gian đặt lịch hoặc đã ngừng nhận lịch.',
            ]);
        }
    }

    public static function allows(DoctorSchedule $schedule, AppointmentTimeSlot $slot): bool
    {
        $now = self::now();
        $date = $schedule->work_date->toDateString();
        if ($date < $now->toDateString() || $date > $now->addDays(2)->toDateString()
            || $schedule->status !== 'ACTIVE' || $slot->status !== 'ACTIVE') {
            return false;
        }

        $window = self::slotWindow($schedule, $slot);

        return $window !== null && $now->lt($window['deadline']);
    }

    /** @return array{shift: string, deadline: CarbonImmutable}|null */
    public static function slotWindow(DoctorSchedule $schedule, AppointmentTimeSlot $slot): ?array
    {
        $date = $schedule->work_date->toDateString();
        $timezone = config('app.timezone');
        $start = CarbonImmutable::parse($date.' '.$slot->start_time, $timezone);
        $end = CarbonImmutable::parse($date.' '.$slot->end_time, $timezone);
        $scheduleStart = CarbonImmutable::parse($date.' '.$schedule->start_time, $timezone);
        $scheduleEnd = CarbonImmutable::parse($date.' '.$schedule->end_time, $timezone);
        $step = (int) config('doctor_schedules.slot_minutes');
        if ($step < 1 || ! $start->addMinutes($step)->equalTo($end)
            || $start->lt($scheduleStart) || $end->gt($scheduleEnd)) {
            return null;
        }

        $shifts = $schedule->isStandardShift()
            ? [$schedule->shift_code => ['start' => $schedule->start_time, 'end' => $schedule->end_time]]
            : config('doctor_schedules.shifts');

        foreach ($shifts as $code => $hours) {
            $shiftStart = CarbonImmutable::parse($date.' '.$hours['start'], $timezone);
            $shiftEnd = CarbonImmutable::parse($date.' '.$hours['end'], $timezone);
            if ($start->gte($shiftStart) && $end->lte($shiftEnd) && $shiftStart->diffInSeconds($start) % ($step * 60) === 0) {
                return ['shift' => $code, 'deadline' => $code === 'AFTERNOON' ? $shiftStart : $shiftStart->startOfDay()];
            }
        }

        return null;
    }

    private static function now(): CarbonImmutable
    {
        return now(config('app.timezone'))->toImmutable();
    }
}
