<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MedicalServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            ['DVXN001', 'XN',   'Công thức máu',          120000, 'lần', 30, 1, 'Đo các chỉ số tế bào máu.'],
            ['DVXN002', 'XN',   'CRP',                    150000, 'lần', 40, 2, 'Định lượng protein phản ứng C trong máu.'],
            ['DVXN003', 'XN',   'Đường huyết',             50000, 'lần', 20, 3, 'Định lượng glucose trong máu.'],
            ['DVXN004', 'XN',   'Tổng phân tích nước tiểu', 90000, 'lần', 30, 4, 'Phân tích các chỉ số cơ bản trong mẫu nước tiểu.'],
            ['DVXN005', 'XN',   'Định lượng HbA1c',       160000, 'lần', 45, 5, 'Định lượng hemoglobin glycated (HbA1c) trong máu.'],
            ['DVXN006', 'XN',   'Định lượng creatinin',    70000, 'lần', 30, 6, 'Định lượng creatinin trong máu.'],
            ['DVXN007', 'XN',   'Xét nghiệm mỡ máu',      220000, 'lần', 45, 7, 'Đo các thành phần lipid trong máu.'],

            ['DVCD001', 'CDHA', 'X-quang ngực thẳng',     200000, 'lần', 20, 1, 'Chụp X-quang lồng ngực tư thế thẳng.'],
            ['DVCD002', 'CDHA', 'X-quang xoang',          200000, 'lần', 20, 2, 'Chụp X-quang vùng xoang cạnh mũi.'],
            ['DVCD004', 'CDHA', 'X-quang cột sống thắt lưng', 280000, 'lần', 25, 3, 'Chụp X-quang vùng cột sống thắt lưng.'],
            ['DVCD005', 'CDHA', 'CT sọ não',             950000, 'lần', 30, 4, 'Chụp cắt lớp vi tính vùng đầu.'],
            ['DVCD006', 'CDHA', 'MRI khớp gối',         1600000, 'lần', 45, 5, 'Chụp cộng hưởng từ khớp gối.'],

            ['DVCD003', 'SA',   'Siêu âm ổ bụng',        300000, 'lần', 25, 1, 'Khảo sát các cơ quan trong ổ bụng bằng siêu âm.'],
            ['DVSA001', 'SA',   'Siêu âm tuyến giáp',     260000, 'lần', 20, 2, 'Khảo sát tuyến giáp bằng siêu âm.'],
            ['DVSA002', 'SA',   'Siêu âm tim',           420000, 'lần', 35, 3, 'Khảo sát cấu trúc và hoạt động của tim bằng siêu âm.'],
            ['DVSA003', 'SA',   'Siêu âm mô mềm',        230000, 'lần', 20, 4, 'Khảo sát mô mềm tại vị trí được chỉ định.'],

            ['DVTDCN002', 'NS', 'Nội soi Tai Mũi Họng',  250000, 'lần', 20, 1, 'Quan sát vùng tai mũi họng bằng ống nội soi.'],
            ['DVNS001', 'NS',   'Nội soi dạ dày',        680000, 'lần', 30, 2, 'Quan sát đường tiêu hóa trên bằng ống nội soi.'],
            ['DVNS002', 'NS',   'Nội soi đại tràng',     950000, 'lần', 45, 3, 'Quan sát niêm mạc đại tràng bằng ống nội soi.'],
            ['DVNS003', 'NS',   'Nội soi mũi xoang',     300000, 'lần', 20, 4, 'Quan sát hốc mũi và các vùng dẫn lưu xoang.'],

            ['DVTDCN001', 'TDCN', 'Điện tâm đồ',          150000, 'lần', 15, 1, 'Ghi hoạt động điện của tim.'],
            ['DVTDCN003', 'TDCN', 'Đo chức năng hô hấp',  250000, 'lần', 30, 2, 'Đo các thông số chức năng hô hấp.'],
            ['DVTDCN004', 'TDCN', 'Đo thính lực đơn âm', 180000, 'lần', 25, 3, 'Đo ngưỡng nghe theo từng tần số.'],
            ['DVTDCN005', 'TDCN', 'Holter điện tâm đồ',  650000, 'lần', 40, 4, 'Ghi điện tim liên tục bằng thiết bị đeo.'],

            ['DVTT001', 'TT', 'Rửa tai',                  180000, 'lần', 20, 1, 'Làm sạch ống tai ngoài theo chỉ định.'],
            ['DVTT002', 'TT', 'Khí dung Tai Mũi Họng',   120000, 'lần', 20, 2, 'Thực hiện khí dung theo chỉ định chuyên khoa.'],
            ['DVTT003', 'TT', 'Cắt chỉ vết thương',      110000, 'lần', 15, 3, 'Tháo chỉ khâu vết thương theo chỉ định.'],
            ['DVTT004', 'TT', 'Lấy dị vật tai',          220000, 'lần', 20, 4, 'Lấy dị vật trong ống tai ngoài theo chỉ định.'],

            ['DVPHCN001', 'PHCN', 'Tập vận động trị liệu', 160000, 'buổi', 35, 1, 'Tập các động tác vận động theo kế hoạch phục hồi chức năng.'],
            ['DVPHCN002', 'PHCN', 'Điện xung trị liệu',   130000, 'buổi', 25, 2, 'Thực hiện điện xung theo kế hoạch điều trị.'],
            ['DVPHCN003', 'PHCN', 'Siêu âm trị liệu',     150000, 'buổi', 25, 3, 'Thực hiện siêu âm trị liệu tại vùng được chỉ định.'],

            ['DVCSSK001', 'CSSK', 'Thay băng vết thương',  90000, 'lần', 20, 1, 'Làm sạch và thay băng vết thương.'],
            ['DVCSSK002', 'CSSK', 'Theo dõi dấu hiệu sinh tồn', 60000, 'lần', 15, 2, 'Đo và ghi nhận các chỉ số sinh tồn.'],
            ['DVCSSK003', 'CSSK', 'Chăm sóc vết mổ ngoại trú', 140000, 'lần', 25, 3, 'Theo dõi và chăm sóc vết mổ trong lần khám ngoại trú.'],
        ];

        foreach ($services as $service) {
            [$code, $groupCode, $name, $price, $unit, $duration, $displayOrder, $description] = $service;

            $groupId = DB::table('service_groups')
                ->where('service_group_code', $groupCode)
                ->value('id');

            DB::table('medical_services')->updateOrInsert(
                ['service_code' => $code],
                [
                    'name' => $name,
                    'description' => $description,
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
