<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceGroupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            ['code' => 'XN',   'name' => 'Xét nghiệm',          'order' => 1],
            ['code' => 'CDHA', 'name' => 'Chẩn đoán hình ảnh', 'order' => 2],
            ['code' => 'TDCN', 'name' => 'Thăm dò chức năng',  'order' => 3],
            ['code' => 'TT',   'name' => 'Thủ thuật',           'order' => 4],
        ];

        foreach ($groups as $group) {
            DB::table('service_groups')->updateOrInsert(
                ['service_group_code' => $group['code']],
                [
                    'name' => $group['name'],
                    'description' => null,
                    'display_order' => $group['order'],
                    'status' => 'ACTIVE',
                    'notes' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}