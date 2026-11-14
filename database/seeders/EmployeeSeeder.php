<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $employees = [
            ['code' => 'NV000001', 'name' => 'Nguyễn Thị Lan', 'dob' => '1997-04-18', 'gender' => 'Nữ'],
            ['code' => 'NV000002', 'name' => 'Phạm Tuấn Anh', 'dob' => '1994-11-06', 'gender' => 'Nam'],
            ['code' => 'NV000003', 'name' => 'Vũ Minh Trang', 'dob' => '1996-08-22', 'gender' => 'Nữ'],
            ['code' => 'NV000004', 'name' => 'Hoàng Thu Hương', 'dob' => '1993-02-15', 'gender' => 'Nữ'],
        ];

        foreach ($employees as $employee) {
            $userId = DB::table('users')
                ->where('business_code', $employee['code'])
                ->value('id');

            DB::table('employees')->updateOrInsert(
                ['employee_code' => $employee['code']],
                [
                    'user_id' => $userId,
                    'full_name' => $employee['name'],
                    'date_of_birth' => $employee['dob'],
                    'gender' => $employee['gender'],
                    'status' => 'ACTIVE',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
