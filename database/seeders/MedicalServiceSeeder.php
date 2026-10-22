<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MedicalServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            ['DVXN001', 'XN',   'Công thức máu',          120000, 'lần', 30, 1],
            ['DVXN002', 'XN',   'CRP',                    150000, 'lần', 40, 2],
            ['DVXN003', 'XN',   'Đường huyết',             50000, 'lần', 20, 3],

            ['DVCD001', 'CDHA', 'X-quang ngực thẳng',     200000, 'lần', 20, 1],
            ['DVCD002', 'CDHA', 'X-quang xoang',          200000, 'lần', 20, 2],
            ['DVCD003', 'CDHA', 'Siêu âm ổ bụng',         300000, 'lần', 25, 3],

            ['DVTDCN001', 'TDCN', 'Điện tâm đồ',          150000, 'lần', 15, 1],
            ['DVTDCN002', 'TDCN', 'Nội soi Tai Mũi Họng', 250000, 'lần', 20, 2],

            ['DVTT001', 'TT', 'Rửa tai',                  180000, 'lần', 20, 1],
            ['DVTT002', 'TT', 'Khí dung Tai Mũi Họng',   120000, 'lần', 20, 2],
        ];

        foreach ($services as $service) {
            [$code, $groupCode, $name, $price, $unit, $duration, $displayOrder] = $service;

            $groupId = DB::table('service_groups')
                ->where('service_group_code', $groupCode)
                ->value('id');

            DB::table('medical_services')->updateOrInsert(
                ['service_code' => $code],
                [
                    'name' => $name,
                    'description' => null,
                    'price' => $price,
                    'unit' => $unit,
                    'estimated_duration_minutes' => $duration,
                    'preparation_instructions' => null,
                    'precautions' => null,
                    'display_order' => $displayOrder,
                    'status' => 'ACTIVE',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            if (! $groupId) {
                throw new \RuntimeException('Không tìm thấy nhóm dịch vụ: '.$groupCode);
            }
            DB::table('service_group_medical_service')->updateOrInsert([
                'service_group_id' => $groupId,
                'medical_service_id' => DB::table('medical_services')->where('service_code', $code)->value('id'),
            ], ['created_at' => now(), 'updated_at' => now()]);
        }
    }
}
