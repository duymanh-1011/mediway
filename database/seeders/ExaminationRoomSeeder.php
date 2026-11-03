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

        // Explicit mapping for sample data only; existing databases are never inferred from room names.
        $sampleSpecialties = ['PKTMH01' => 'CKTMH', 'PKTM01' => 'CKTM', 'PKNOI01' => 'CKNOI', 'PKTH01' => 'CKTH', 'PKDL01' => 'CKDL'];
        foreach ($rooms as $room) {
            $existing = DB::table('examination_rooms')->where('room_code', $room['code'])->first();
            DB::table('examination_rooms')->updateOrInsert(
                ['room_code' => $room['code']],
                [
                    'name' => $room['name'],
                    'specialty_id' => $existing ? $existing->specialty_id : DB::table('specialties')->where('specialty_code', $sampleSpecialties[$room['code']])->value('id'),
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
