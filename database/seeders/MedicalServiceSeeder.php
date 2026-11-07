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
            ['DVXN004', 'XN',   'Tổng phân tích nước tiểu', 90000, 'lần', 30, 4],
            ['DVXN005', 'XN',   'Định lượng HbA1c',       160000, 'lần', 45, 5],
            ['DVXN006', 'XN',   'Định lượng creatinin',    70000, 'lần', 30, 6],
            ['DVXN007', 'XN',   'Xét nghiệm mỡ máu',      220000, 'lần', 45, 7],

            ['DVCD001', 'CDHA', 'X-quang ngực thẳng',     200000, 'lần', 20, 1],
            ['DVCD002', 'CDHA', 'X-quang xoang',          200000, 'lần', 20, 2],
            ['DVCD004', 'CDHA', 'X-quang cột sống thắt lưng', 280000, 'lần', 25, 3],
            ['DVCD005', 'CDHA', 'CT sọ não',             950000, 'lần', 30, 4],
            ['DVCD006', 'CDHA', 'MRI khớp gối',         1600000, 'lần', 45, 5],

            ['DVCD003', 'SA',   'Siêu âm ổ bụng',        300000, 'lần', 25, 1],
            ['DVSA001', 'SA',   'Siêu âm tuyến giáp',     260000, 'lần', 20, 2],
            ['DVSA002', 'SA',   'Siêu âm tim',           420000, 'lần', 35, 3],
            ['DVSA003', 'SA',   'Siêu âm mô mềm',        230000, 'lần', 20, 4],

            ['DVTDCN002', 'NS', 'Nội soi Tai Mũi Họng',  250000, 'lần', 20, 1],
            ['DVNS001', 'NS',   'Nội soi dạ dày',        680000, 'lần', 30, 2],
            ['DVNS002', 'NS',   'Nội soi đại tràng',     950000, 'lần', 45, 3],
            ['DVNS003', 'NS',   'Nội soi mũi xoang',     300000, 'lần', 20, 4],

            ['DVTDCN001', 'TDCN', 'Điện tâm đồ',          150000, 'lần', 15, 1],
            ['DVTDCN003', 'TDCN', 'Đo chức năng hô hấp',  250000, 'lần', 30, 2],
            ['DVTDCN004', 'TDCN', 'Đo thính lực đơn âm', 180000, 'lần', 25, 3],
            ['DVTDCN005', 'TDCN', 'Holter điện tâm đồ',  650000, 'lần', 40, 4],

            ['DVTT001', 'TT', 'Rửa tai',                  180000, 'lần', 20, 1],
            ['DVTT002', 'TT', 'Khí dung Tai Mũi Họng',   120000, 'lần', 20, 2],
            ['DVTT003', 'TT', 'Cắt chỉ vết thương',      110000, 'lần', 15, 3],
            ['DVTT004', 'TT', 'Lấy dị vật tai',          220000, 'lần', 20, 4],

            ['DVPHCN001', 'PHCN', 'Tập vận động trị liệu', 160000, 'buổi', 35, 1],
            ['DVPHCN002', 'PHCN', 'Điện xung trị liệu',   130000, 'buổi', 25, 2],
            ['DVPHCN003', 'PHCN', 'Siêu âm trị liệu',     150000, 'buổi', 25, 3],

            ['DVCSSK001', 'CSSK', 'Thay băng vết thương',  90000, 'lần', 20, 1],
            ['DVCSSK002', 'CSSK', 'Theo dõi dấu hiệu sinh tồn', 60000, 'lần', 15, 2],
            ['DVCSSK003', 'CSSK', 'Chăm sóc vết mổ ngoại trú', 140000, 'lần', 25, 3],
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
