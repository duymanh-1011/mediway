<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(bool $includeExtraAccounts = false): void
    {
        $users = [
            ['business_code' => 'QT000001', 'phone' => '0901000001', 'role' => 'Administrator'],

            ['business_code' => 'BN000001', 'phone' => '0902000001', 'role' => 'Patient'],
            ['business_code' => 'BN000002', 'phone' => '0902000002', 'role' => 'Patient'],
            ['business_code' => 'BN000003', 'phone' => '0902000003', 'role' => 'Patient'],
            ['business_code' => 'BN000004', 'phone' => '0902000004', 'role' => 'Patient'],
            ['business_code' => 'BN000005', 'phone' => '0902000005', 'role' => 'Patient'],

            ['business_code' => 'BS000001', 'phone' => '0903000001', 'role' => 'Doctor'],
            ['business_code' => 'BS000002', 'phone' => '0903000002', 'role' => 'Doctor'],
            ['business_code' => 'BS000003', 'phone' => '0903000003', 'role' => 'Doctor'],

            ['business_code' => 'NV000001', 'phone' => '0904000001', 'role' => 'Employee'],
            ['business_code' => 'NV000002', 'phone' => '0904000002', 'role' => 'Employee'],
            ['business_code' => 'NV000003', 'phone' => '0904000003', 'role' => 'Employee'],
            ['business_code' => 'NV000004', 'phone' => '0904000004', 'role' => 'Employee'],
        ];

        if ($includeExtraAccounts) {
            $users = [...$users,
                ['business_code' => 'BN000006', 'phone' => '0902000006', 'role' => 'Patient'],
                ['business_code' => 'BN000007', 'phone' => '0902000007', 'role' => 'Patient'],
                ['business_code' => 'BN000008', 'phone' => '0902000008', 'role' => 'Patient'],
                ['business_code' => 'BN000009', 'phone' => '0902000009', 'role' => 'Patient'],
                ['business_code' => 'BN000010', 'phone' => '0902000010', 'role' => 'Patient'],
                ['business_code' => 'BS000004', 'phone' => '0903000004', 'role' => 'Doctor'],
                ['business_code' => 'BS000005', 'phone' => '0903000005', 'role' => 'Doctor'],
                ['business_code' => 'BS000006', 'phone' => '0903000006', 'role' => 'Doctor'],
            ];
        }

        foreach ($users as $user) {
            $roleId = DB::table('roles')
                ->where('name', $user['role'])
                ->value('id');

            DB::table('users')->updateOrInsert(
                ['business_code' => $user['business_code']],
                [
                    'phone' => $user['phone'],
                    'password' => Hash::make('Mediway@123'),
                    'role_id' => $roleId,
                    'status' => 'ACTIVE',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
