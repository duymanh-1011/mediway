<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DoctorSeeder extends Seeder
{
    public function run(): void
    {
        $doctors = [
            ['code' => 'BS000001', 'name' => 'Nguyễn Văn Bình', 'dob' => '1978-03-10', 'gender' => 'Nam'],
            ['code' => 'BS000002', 'name' => 'Trần Quốc Khánh', 'dob' => '1980-07-21', 'gender' => 'Nam'],
            ['code' => 'BS000003', 'name' => 'Lê Minh Đức', 'dob' => '1984-12-02', 'gender' => 'Nam'],
        ];

        foreach ($doctors as $doctor) {
            $userId = DB::table('users')
                ->where('business_code', $doctor['code'])
                ->value('id');

            DB::table('doctors')->updateOrInsert(
                ['doctor_code' => $doctor['code']],
                [
                    'user_id' => $userId,
                    'full_name' => $doctor['name'],
                    'date_of_birth' => $doctor['dob'],
                    'gender' => $doctor['gender'],
                    'status' => 'ACTIVE',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}