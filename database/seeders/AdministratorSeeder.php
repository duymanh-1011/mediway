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
                'email' => SeededContact::email('Quản trị viên Mediway'),
                'date_of_birth' => '1989-07-12',
                'gender' => 'Nam',
                'avatar_path' => null,
                'address' => 'Số 25 phố Liễu Giai, phường Ngọc Hà, Hà Nội',
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
