<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DiseaseSeeder extends Seeder
{
    public function run(): void
    {
        $diseases = [
            ['J00',   'NBHH', 'Viêm mũi họng cấp',             'Cảm lạnh thông thường'],
            ['J01.9', 'NBHH', 'Viêm xoang cấp',                null],
            ['J02.9', 'NBHH', 'Viêm họng cấp',                 null],
            ['H60.9', 'NBHH', 'Viêm tai ngoài',                null],

            ['I10',   'NBTM', 'Tăng huyết áp nguyên phát',     'Tăng huyết áp'],
            ['I20.9', 'NBTM', 'Đau thắt ngực',                 null],

            ['K21.9', 'NBTH', 'Trào ngược dạ dày thực quản',   'GERD'],
            ['K29.7', 'NBTH', 'Viêm dạ dày',                   null],

            ['E11.9', 'NBCD', 'Đái tháo đường típ 2',          null],
            ['L20.9', 'NBDL', 'Viêm da cơ địa',                null],
        ];

        foreach ($diseases as [$code, $groupCode, $name, $alternativeName]) {
            $groupId = DB::table('disease_groups')
                ->where('disease_group_code', $groupCode)
                ->value('id');

            DB::table('diseases')->updateOrInsert(
                ['disease_code' => $code],
                [
                    'disease_group_id' => $groupId,
                    'name' => $name,
                    'alternative_name' => $alternativeName,
                    'description' => null,
                    'common_symptoms' => null,
                    'professional_notes' => null,
                    'status' => 'ACTIVE',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}