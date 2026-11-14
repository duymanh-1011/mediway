<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExaminationRoomSeeder extends Seeder
{
    public function run(): void
    {
        $rooms = [
            ['PKTMH01', 'Phòng khám Tai Mũi Họng 01', '201', '2', 'Nhà A', 'CKTMH', 'Phòng khám ngoại trú chuyên khoa Tai Mũi Họng.'],
            ['PKTM01', 'Phòng khám Tim mạch 01', '301', '3', 'Nhà A', 'CKTM', 'Phòng khám và tái khám chuyên khoa Tim mạch.'],
            ['PKNOI01', 'Phòng khám Nội 01', '101', '1', 'Nhà B', 'CKNOI', 'Phòng khám ngoại trú chuyên khoa Nội tổng quát.'],
            ['PKTH01', 'Phòng khám Tiêu hóa 01', '102', '1', 'Nhà B', 'CKTH', 'Phòng khám ngoại trú chuyên khoa Tiêu hóa.'],
            ['PKDL01', 'Phòng khám Da liễu 01', '103', '1', 'Nhà B', 'CKDL', 'Phòng khám và tái khám chuyên khoa Da liễu.'],
        ];

        foreach ($rooms as [$code, $name, $number, $floor, $building, $specialty, $description]) {
            $existing = DB::table('examination_rooms')->where('room_code', $code)->first();
            DB::table('examination_rooms')->updateOrInsert(
                ['room_code' => $code],
                [
                    'name' => $name,
                    'specialty_id' => $existing ? $existing->specialty_id : DB::table('specialties')->where('specialty_code', $specialty)->value('id'),
                    'room_number' => $number,
                    'floor' => $floor,
                    'building' => $building,
                    'location_instructions' => $existing?->location_instructions ?? "Đến {$building}, tầng {$floor}, phòng {$number}.",
                    'description' => $existing?->description ?? $description,
                    'status' => 'ACTIVE',
                    'notes' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
