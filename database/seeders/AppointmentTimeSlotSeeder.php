<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AppointmentTimeSlotSeeder extends Seeder
{
    public function run(): void
    {
        $doctorCodes = [
            'BS000001',
            'BS000002',
            'BS000003',
        ];

        $slots = [
            ['08:00:00', '08:30:00'],
            ['08:30:00', '09:00:00'],
        ];

        foreach ($doctorCodes as $doctorCode) {
            $doctorId = DB::table('doctors')
                ->where('doctor_code', $doctorCode)
                ->value('id');

            $scheduleId = DB::table('doctor_schedules')
                ->where('doctor_id', $doctorId)
                ->where('work_date', '2026-10-15')
                ->where('start_time', '08:00:00')
                ->where('end_time', '11:00:00')
                ->value('id');

            foreach ($slots as [$startTime, $endTime]) {
                DB::table('appointment_time_slots')->updateOrInsert(
                    [
                        'doctor_schedule_id' => $scheduleId,
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
