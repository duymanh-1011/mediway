<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class DoctorShiftHours
{
    public function current(bool $lock = false): array
    {
        $hours = DB::table('doctor_shift_settings')->where('id', 1)
            ->when($lock, fn ($query) => $query->sharedLock())->value('hours');

        $defaults = config('doctor_schedules.shifts');

        return $hours === null ? $defaults : array_replace_recursive($defaults, json_decode($hours, true, flags: JSON_THROW_ON_ERROR));
    }

    public function update(array $input): void
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

        DB::table('doctor_shift_settings')->where('id', 1)->update(['hours' => json_encode($hours, JSON_THROW_ON_ERROR)]);
    }

    private function minutes(string $time): int
    {
        [$hour, $minute] = explode(':', $time);

        return (int) $hour * 60 + (int) $minute;
    }
}
