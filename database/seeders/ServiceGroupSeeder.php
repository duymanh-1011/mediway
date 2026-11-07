<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceGroupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            ['XN', 'Xét nghiệm', 'Huyết học, sinh hóa và xét nghiệm nước tiểu.'],
            ['CDHA', 'Chẩn đoán hình ảnh', 'Chụp X-quang, CT và cộng hưởng từ.'],
            ['SA', 'Siêu âm', 'Siêu âm tổng quát và siêu âm chuyên khoa.'],
            ['NS', 'Nội soi', 'Nội soi tai mũi họng và đường tiêu hóa.'],
            ['TDCN', 'Thăm dò chức năng', 'Điện tim, hô hấp ký và các phép đo chức năng.'],
            ['TT', 'Thủ thuật điều trị', 'Các thủ thuật ngoại trú theo chỉ định.'],
            ['PHCN', 'Vật lý trị liệu và phục hồi chức năng', 'Các buổi tập và điều trị phục hồi chức năng.'],
            ['CSSK', 'Chăm sóc và theo dõi', 'Chăm sóc vết thương và theo dõi chỉ số sức khỏe.'],
        ];

        foreach ($groups as $index => [$code, $name, $description]) {
            DB::table('service_groups')->updateOrInsert(
                ['service_group_code' => $code],
                [
                    'name' => $name,
                    'description' => $description,
                    'display_order' => $index + 1,
                    'status' => 'ACTIVE',
                    'notes' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
