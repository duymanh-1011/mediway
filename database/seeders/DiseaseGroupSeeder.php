<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DiseaseGroupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            ['NBHH', 'Hô hấp - Tai Mũi Họng', 'Các bệnh đường hô hấp và tai mũi họng.'],
            ['NBTM', 'Tim mạch', 'Các bệnh về tim, mạch máu và huyết áp.'],
            ['NBTH', 'Tiêu hóa', 'Các bệnh về thực quản, dạ dày và đường ruột.'],
            ['NBCD', 'Chuyển hóa', 'Các bệnh liên quan đến rối loạn chuyển hóa.'],
            ['NBDL', 'Da liễu', 'Các bệnh về da, tóc và móng.'],
        ];

        foreach ($groups as [$code, $name, $description]) {
            DB::table('disease_groups')->updateOrInsert(
                ['disease_group_code' => $code],
                [
                    'name' => $name,
                    'description' => $description,
                    'status' => 'ACTIVE',
                    'notes' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
