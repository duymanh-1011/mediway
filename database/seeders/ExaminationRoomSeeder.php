<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExaminationRoomSeeder extends Seeder
{
    public function run(): void
    {
        $rooms = [
            ['code' => 'PKTMH01', 'name' => 'Phòng khám Tai Mũi Họng 01', 'number' => '201', 'floor' => '2', 'building' => 'Nhà A'],
            ['code' => 'PKTM01',  'name' => 'Phòng khám Tim mạch 01',      'number' => '301', 'floor' => '3', 'building' => 'Nhà A'],
            ['code' => 'PKNOI01', 'name' => 'Phòng khám Nội 01',          'number' => '101', 'floor' => '1', 'building' => 'Nhà B'],
            ['code' => 'PKTH01',  'name' => 'Phòng khám Tiêu hóa 01',     'number' => '102', 'floor' => '1', 'building' => 'Nhà B'],
            ['code' => 'PKDL01',  'name' => 'Phòng khám Da liễu 01',      'number' => '103', 'floor' => '1', 'building' => 'Nhà B'],
        ];

        foreach ($rooms as $room) {
            DB::table('examination_rooms')->updateOrInsert(
                ['room_code' => $room['code']],
                [
                    'name' => $room['name'],
                    'room_number' => $room['number'],
                    'floor' => $room['floor'],
                    'building' => $room['building'],
                    'location_instructions' => null,
                    'description' => null,
                    'status' => 'ACTIVE',
                    'notes' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
