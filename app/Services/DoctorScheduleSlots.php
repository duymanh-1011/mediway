<?php

namespace App\Services;

use App\Models\DoctorSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class DoctorScheduleSlots
{
    /** The caller holds the schedule lock within the save transaction. */
    public function generate(DoctorSchedule $schedule, bool $preview = false): void
    {
        $start = CarbonImmutable::createFromFormat('!H:i:s', $schedule->start_time);
        $end = CarbonImmutable::createFromFormat('!H:i:s', $schedule->end_time);
        $step = (int) config('doctor_schedules.slot_minutes');
        if ($step < 1) {
            throw new \LogicException('Độ dài khung đăng ký phải lớn hơn 0.');
        }
        $expected = [];
        for ($at = $start; $at->lt($end); $at = $at->addMinutes($step)) {
            $next = $at->addMinutes($step);
            if ($next->gt($end)) {
                throw new \LogicException('Giờ ca phải chia hết theo độ dài khung đăng ký.');
            }
            $expected[$at->format('H:i:s')] = $next->format('H:i:s');
        }

        foreach ($schedule->appointmentTimeSlots()->when(! $preview, fn ($query) => $query->lockForUpdate())->get(['id', 'start_time', 'end_time']) as $slot) {
            if (($expected[$slot->start_time] ?? null) !== $slot->end_time) {
                throw ValidationException::withMessages(['shift' => 'Ca có khung giờ cũ không khớp. Cần giữ khung gốc và xử lý trước khi sinh thêm.']);
            }
            unset($expected[$slot->start_time]);
        }
        if ($preview) {
            return;
        }
        foreach ($expected as $startTime => $endTime) {
            $schedule->appointmentTimeSlots()->create([
                'start_time' => $startTime, 'end_time' => $endTime, 'max_patients' => null, 'status' => 'ACTIVE',
            ]);
        }
    }
}
