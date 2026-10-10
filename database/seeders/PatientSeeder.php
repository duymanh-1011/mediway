<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PatientSeeder extends Seeder
{
    public function run(): void
    {
        $patients = [
            ['code' => 'BN000001', 'name' => 'Nguyễn Minh An', 'dob' => '1998-04-12', 'gender' => 'Nam'],
            ['code' => 'BN000002', 'name' => 'Trần Thu Hà', 'dob' => '1985-09-23', 'gender' => 'Nữ'],
            ['code' => 'BN000003', 'name' => 'Lê Hoàng Nam', 'dob' => '1972-01-05', 'gender' => 'Nam'],
            ['code' => 'BN000004', 'name' => 'Phạm Ngọc Mai', 'dob' => '2001-11-18', 'gender' => 'Nữ'],
            ['code' => 'BN000005', 'name' => 'Đỗ Quang Huy', 'dob' => '1990-06-30', 'gender' => 'Nam'],
        ];

        foreach ($patients as $patient) {
            $userId = DB::table('users')
                ->where('business_code', $patient['code'])
                ->value('id');

            DB::table('patients')->updateOrInsert(
                ['patient_code' => $patient['code']],
                [
                    'user_id' => $userId,
                    'full_name' => $patient['name'],
                    'date_of_birth' => $patient['dob'],
                    'gender' => $patient['gender'],
                    'status' => 'ACTIVE',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}