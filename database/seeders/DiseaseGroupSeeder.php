<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DiseaseGroupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            ['NBHH', 'Hô hấp - Tai Mũi Họng'],
            ['NBTM', 'Tim mạch'],
            ['NBTH', 'Tiêu hóa'],
            ['NBCD', 'Chuyển hóa'],
            ['NBDL', 'Da liễu'],
        ];

        foreach ($groups as [$code, $name]) {
            DB::table('disease_groups')->updateOrInsert(
                ['disease_group_code' => $code],
                [
                    'name' => $name,
                    'description' => null,
                    'status' => 'ACTIVE',
                    'notes' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
