<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $employees = [
            ['code' => 'NV000001', 'name' => 'Nguyễn Thị Lan'],
            ['code' => 'NV000002', 'name' => 'Phạm Tuấn Anh'],
            ['code' => 'NV000003', 'name' => 'Vũ Minh Trang'],
            ['code' => 'NV000004', 'name' => 'Hoàng Thu Hương'],
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
                    'date_of_birth' => null,
                    'gender' => null,
                    'status' => 'ACTIVE',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}