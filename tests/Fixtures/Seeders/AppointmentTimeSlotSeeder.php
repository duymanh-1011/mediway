<?php

namespace Tests\Fixtures\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AppointmentTimeSlotSeeder extends Seeder
{
    public function run(bool $includePracticeData = false): void
    {
        $dates = $includePracticeData ? DoctorScheduleSeeder::bookingDates() : [];
        $schedules = DB::table('doctor_schedules')
            ->join('doctors', 'doctors.id', '=', 'doctor_schedules.doctor_id')
            ->where('doctor_schedules.start_time', '08:00:00')
            ->where('doctor_schedules.end_time', '11:00:00')
            ->where(function ($query) use ($dates) {
                $query->where(fn ($legacy) => $legacy->where('work_date', '2026-10-15')
                    ->whereIn('doctor_code', ['BS000001', 'BS000002', 'BS000003']))
                    ->orWhere(fn ($future) => $future->whereIn('work_date', $dates)
                        ->whereIn('doctor_code', array_keys(DoctorScheduleSeeder::BOOKABLE_DOCTORS)));
            })
            ->get(['doctor_schedules.id', 'doctor_schedules.work_date']);

        foreach ($schedules as $schedule) {
            $slots = [['08:00:00', '08:30:00'], ['08:30:00', '09:00:00']];
            if (in_array($schedule->work_date, $dates, true)) {
                $slots = [...$slots, ['09:00:00', '09:30:00'], ['09:30:00', '10:00:00']];
            }
            foreach ($slots as [$startTime, $endTime]) {
                DB::table('appointment_time_slots')->updateOrInsert(
                    [
                        'doctor_schedule_id' => $schedule->id,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                    ],
                    [
                        'max_patients' => 3,
                        'status' => 'ACTIVE',
                        'notes' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }
}
