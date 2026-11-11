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
        $now = self::now();

        return ($now->format('H:i:s') < config('doctor_schedules.shifts.AFTERNOON.start') ? $now : $now->addDay())->toDateString();
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
        $start = CarbonImmutable::parse($date.' '.$slot->start_time, $now->timezone);
        $end = CarbonImmutable::parse($date.' '.$slot->end_time, $now->timezone);
        $step = (int) config('doctor_schedules.slot_minutes');
        if ($start->lte($now) || ! $start->addMinutes($step)->equalTo($end)
            || $start->lt(CarbonImmutable::parse($date.' '.$schedule->start_time, $now->timezone))
            || $end->gt(CarbonImmutable::parse($date.' '.$schedule->end_time, $now->timezone))) {
            return false;
        }

        foreach (config('doctor_schedules.shifts') as $code => $hours) {
            if ($schedule->isStandardShift() && $schedule->shift_code !== $code) {
                continue;
            }
            $shiftStart = CarbonImmutable::parse($date.' '.$hours['start'], $now->timezone);
            $shiftEnd = CarbonImmutable::parse($date.' '.$hours['end'], $now->timezone);
            if ($start->gte($shiftStart) && $end->lte($shiftEnd) && $shiftStart->diffInSeconds($start) % ($step * 60) === 0) {
                return $date !== $now->toDateString() || ($code === 'AFTERNOON' && $now->lt($shiftStart));
            }
        }

        return false;
    }

    private static function now(): CarbonImmutable
    {
        return now(config('app.timezone'))->toImmutable();
    }
}
