<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DoctorScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $schedules = [
            ['BS000001', 'CKTMH', 'PKTMH01'],
            ['BS000002', 'CKTM',  'PKTM01'],
            ['BS000003', 'CKNOI', 'PKNOI01'],
        ];

        foreach ($schedules as [$doctorCode, $specialtyCode, $roomCode]) {
            $doctorId = DB::table('doctors')
                ->where('doctor_code', $doctorCode)
                ->value('id');

            $specialtyId = DB::table('specialties')
                ->where('specialty_code', $specialtyCode)
                ->value('id');

            $roomId = DB::table('examination_rooms')
                ->where('room_code', $roomCode)
                ->value('id');

            DB::table('doctor_schedules')->updateOrInsert(
                [
                    'doctor_id' => $doctorId,
                    'work_date' => '2026-10-15',
                    'start_time' => '08:00:00',
                    'end_time' => '11:00:00',
                ],
                [
                    'specialty_id' => $specialtyId,
                    'examination_room_id' => $roomId,
                    'status' => 'OPEN',
                    'notes' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}