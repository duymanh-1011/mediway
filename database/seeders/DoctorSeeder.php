<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DoctorSeeder extends Seeder
{
    public function run(bool $includeExtraAccounts = false): void
    {
        $doctors = [
            ['code' => 'BS000001', 'name' => 'Nguyễn Văn Bình', 'dob' => '1978-03-10', 'gender' => 'Nam', 'specialty' => 'CKTMH'],
            ['code' => 'BS000002', 'name' => 'Trần Quốc Khánh', 'dob' => '1980-07-21', 'gender' => 'Nam', 'specialty' => 'CKTM'],
            ['code' => 'BS000003', 'name' => 'Lê Minh Đức', 'dob' => '1984-12-02', 'gender' => 'Nam', 'specialty' => 'CKNOI'],
        ];

        if ($includeExtraAccounts) {
            $doctors = [...$doctors,
                ['code' => 'BS000004', 'name' => 'Phan Ngọc Linh', 'dob' => '1986-05-14', 'gender' => 'Nữ', 'specialty' => 'CKTH'],
                ['code' => 'BS000005', 'name' => 'Vũ Hoài Anh', 'dob' => '1988-08-09', 'gender' => 'Nữ', 'specialty' => 'CKDL'],
                ['code' => 'BS000006', 'name' => 'Đặng Minh Khoa', 'dob' => '1983-10-26', 'gender' => 'Nam', 'specialty' => 'CKTMH'],
            ];
        }

        foreach ($doctors as $doctor) {
            $userId = DB::table('users')
                ->where('business_code', $doctor['code'])
                ->value('id');

            DB::table('doctors')->updateOrInsert(
                ['doctor_code' => $doctor['code']],
                [
                    'user_id' => $userId,
                    'specialty_id' => DB::table('specialties')->where('specialty_code', $doctor['specialty'])->value('id'),
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
