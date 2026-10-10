<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdministratorSeeder extends Seeder
{
    public function run(): void
    {
        $userId = DB::table('users')
            ->where('business_code', 'QT000001')
            ->value('id');

        DB::table('administrators')->updateOrInsert(
            ['user_id' => $userId],
            [
                'full_name' => 'Quản trị viên Mediway',
                'email' => 'admin@mediway.test',
                'date_of_birth' => null,
                'gender' => null,
                'avatar_path' => null,
                'address' => null,
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
