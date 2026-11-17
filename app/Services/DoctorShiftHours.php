<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\DoctorSchedule;
use App\Models\MedicalVisit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class DoctorShiftHours
{
    public function __construct(private DoctorScheduleSlots $slots) {}

    public function current(bool $lock = false): array
    {
        $hours = DB::table('doctor_shift_settings')->where('id', 1)
            ->when($lock, fn ($query) => $query->sharedLock())->value('hours');

        $defaults = config('doctor_schedules.shifts');

        return $hours === null ? $defaults : array_replace_recursive($defaults, json_decode($hours, true, flags: JSON_THROW_ON_ERROR));
    }

    public function update(array $input): array
    {
        $times = Validator::make($input, [
            'morning_start' => 'required|date_format:H:i',
            'morning_end' => 'required|date_format:H:i',
            'afternoon_start' => 'required|date_format:H:i',
            'afternoon_end' => 'required|date_format:H:i',
        ], ['required' => 'Vui lòng nhập đủ giờ bắt đầu và kết thúc.', 'date_format' => 'Nhập giờ theo định dạng HH:mm.'])->validate();

        $step = (int) config('doctor_schedules.slot_minutes');
        $hours = [];
        foreach (['morning' => 'MORNING', 'afternoon' => 'AFTERNOON'] as $key => $code) {
            $start = $times[$key.'_start'];
            $end = $times[$key.'_end'];
            $duration = $this->minutes($end) - $this->minutes($start);

            if ($duration < $step || $duration % $step !== 0) {
                throw ValidationException::withMessages([$key.'_end' => "Giờ kết thúc phải sau giờ bắt đầu và thời lượng ca phải chia hết cho {$step} phút."]);
            }

            $hours[$code] = ['start' => $start.':00', 'end' => $end.':00'];
        }

        if ($times['morning_end'] > $times['afternoon_start']) {
            throw ValidationException::withMessages(['afternoon_start' => 'Ca chiều phải bắt đầu từ lúc ca sáng kết thúc trở đi.']);
        }

        return DB::transaction(function () use ($hours): array {
            $settings = DB::table('doctor_shift_settings')->where('id', 1);
            $settings->lockForUpdate()->first();

            $calendar = DoctorSchedule::where('work_date', '>=', today()->toDateString())
                ->with('doctor:id,full_name')
                ->withExists(['appointmentTimeSlots as has_open_appointments' => fn ($slots) => $slots
                    ->whereHas('appointments', fn ($appointments) => $appointments->where(fn ($booking) => $booking
                        ->whereHas('appointmentStatus', fn ($status) => $status->whereNotIn('name', ['CANCELLED', 'COMPLETED']))
                        ->orWhereHas('medicalVisit.status', fn ($status) => $status->whereNotIn('name', ['CANCELLED', 'COMPLETED']))))])
                ->orderBy('work_date')->orderBy('id')->lockForUpdate()->get()->groupBy(fn (DoctorSchedule $shift) => $shift->work_date->toDateString());

            $updated = 0;
            $skipped = [];
            foreach ($calendar as $day) {
                $plans = $this->planDay($day, $hours, $skipped);
                foreach ($plans as [$shift, $start, $end, $obsoleteSlots]) {
                    if ($obsoleteSlots !== []) {
                        $shift->appointmentTimeSlots()->whereKey($obsoleteSlots)->delete();
                    }
                    $shift->update(['start_time' => $start, 'end_time' => $end]);
                    if ($shift->status === 'ACTIVE') {
                        $this->slots->generate($shift);
                    }
                    $updated++;
                }
            }

            $settings->update(['hours' => json_encode($hours, JSON_THROW_ON_ERROR)]);

            return ['updated' => $updated, 'skipped' => $skipped];
        }, 5);
    }

    private function planDay(Collection $day, array $hours, array &$skipped): array
    {
        $plans = [];
        foreach ($day as $shift) {
            $time = $hours[$shift->shift_code] ?? null;
            if (! $time || ($shift->start_time === $time['start'] && $shift->end_time === $time['end'])) {
                continue;
            }
            $obsoleteSlots = $this->slots->obsoleteSlotIds($shift, $time['start'], $time['end']);
            if ($obsoleteSlots !== [] && Appointment::whereIn('appointment_time_slot_id', $obsoleteSlots)->lockForUpdate()->first(['id'])) {
                $skipped[] = $this->skip($shift, 'Có lịch hẹn gắn với khung giờ cần thay đổi.');
                continue;
            }
            if ($this->hasWalkInOutsideHours($shift, $time['start'], $time['end'])) {
                $skipped[] = $this->skip($shift, 'Có lượt khám trực tiếp đang xử lý ngoài giờ mới.');
                continue;
            }

            $plans[$shift->id] = [$shift, $time['start'], $time['end'], $obsoleteSlots];
        }

        // Compare the planned hours of both shifts. A skipped shift falls back to its original hours.
        do {
            $conflicts = [];
            foreach ($plans as $id => [$shift, $start, $end]) {
                if (! $this->occupiesTime($shift)) {
                    continue;
                }
                foreach ($day as $other) {
                    if ($id === $other->id || ! $this->occupiesTime($other)
                        || ($shift->doctor_id !== $other->doctor_id && ($shift->examination_room_id === null || $shift->examination_room_id !== $other->examination_room_id))) {
                        continue;
                    }
                    if ($start < ($plans[$other->id][2] ?? $other->end_time) && $end > ($plans[$other->id][1] ?? $other->start_time)) {
                        $conflicts[$id] = $this->skip($shift, 'Giờ mới trùng lịch bác sĩ hoặc phòng khám.');
                        break;
                    }
                }
            }
            foreach ($conflicts as $id => $notice) {
                unset($plans[$id]);
                $skipped[] = $notice;
            }
        } while ($conflicts !== []);

        return $plans;
    }

    private function hasWalkInOutsideHours(DoctorSchedule $shift, string $start, string $end): bool
    {
        if ($shift->status !== 'ACTIVE' || $shift->work_date->toDateString() !== today()->toDateString()
            || ($start <= $shift->start_time && $end >= $shift->end_time)) {
            return false;
        }

        return MedicalVisit::where('doctor_id', $shift->doctor_id)->where('examination_room_id', $shift->examination_room_id)
            ->where('reception_source', 'WALK_IN')->whereDate('checked_in_at', $shift->work_date->toDateString())
            ->whereTime('checked_in_at', '>=', $shift->start_time)->whereTime('checked_in_at', '<', $shift->end_time)
            ->where(fn ($time) => $time->whereTime('checked_in_at', '<', $start)->orWhereTime('checked_in_at', '>=', $end))
            ->whereHas('status', fn ($status) => $status->whereNotIn('name', ['CANCELLED', 'COMPLETED']))
            ->lockForUpdate()->first(['id']) !== null;
    }

    private function occupiesTime(DoctorSchedule $shift): bool
    {
        return $shift->status === 'ACTIVE' || $shift->has_open_appointments;
    }

    private function skip(DoctorSchedule $shift, string $reason): array
    {
        return [
            'date' => $shift->work_date->format('d/m/Y'),
            'doctor' => $shift->doctor->full_name,
            'shift' => $shift->shift_code === 'MORNING' ? 'Ca sáng' : 'Ca chiều',
            'reason' => $reason,
        ];
    }

    private function minutes(string $time): int
    {
        [$hour, $minute] = explode(':', $time);

        return (int) $hour * 60 + (int) $minute;
    }
}
