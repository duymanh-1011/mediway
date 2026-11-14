<?php

namespace Tests\Fixtures\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DoctorScheduleSeeder extends Seeder
{
    public const BOOKABLE_DOCTORS = [
        'BS000004' => ['CKTH', 'PKTH01'],
        'BS000005' => ['CKDL', 'PKDL01'],
        'BS000006' => ['CKTMH', 'PKTMH01'],
    ];

    public static function bookingDates(): array
    {
        return [today()->addDay()->toDateString(), today()->addDays(3)->toDateString()];
    }

    public function run(bool $includePracticeData = false): void
    {
        $schedules = [
            ['BS000001', 'CKTMH', 'PKTMH01', '2026-10-15'],
            ['BS000002', 'CKTM',  'PKTM01', '2026-10-15'],
            ['BS000003', 'CKNOI', 'PKNOI01', '2026-10-15'],
        ];
        foreach ($includePracticeData ? self::bookingDates() : [] as $date) {
            foreach (self::BOOKABLE_DOCTORS as $doctorCode => [$specialty, $room]) {
                $schedules[] = [$doctorCode, $specialty, $room, $date];
            }
        }

        foreach ($schedules as [$doctorCode, $specialtyCode, $roomCode, $date]) {
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
                    'work_date' => $date,
                    'start_time' => '08:00:00',
                    'end_time' => '11:00:00',
                ],
                [
                    'specialty_id' => $specialtyId,
                    'examination_room_id' => $roomId,
                    'status' => 'ACTIVE',
                    'notes' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
