<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Patient',
                'description' => 'Bệnh nhân',
                'status' => 'ACTIVE',
            ],
            [
                'name' => 'Doctor',
                'description' => 'Bác sĩ',
                'status' => 'ACTIVE',
            ],
            [
                'name' => 'Employee',
                'description' => 'Nhân viên',
                'status' => 'ACTIVE',
            ],
            [
                'name' => 'Administrator',
                'description' => 'Quản trị viên',
                'status' => 'ACTIVE',
            ],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['name' => $role['name']],
                [
                    'description' => $role['description'],
                    'status' => $role['status'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}