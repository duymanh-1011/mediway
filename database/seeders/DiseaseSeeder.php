<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DiseaseSeeder extends Seeder
{
    public function run(): void
    {
        $diseases = [
            // Các mã phổ biến được đưa lên đầu để tiện chạy thử luồng khám và kê đơn.
            ['I10',   'ICD09', 'Bệnh tăng huyết áp vô căn (nguyên phát)', 'Tăng huyết áp'],
            ['E11.9', 'ICD04', 'Bệnh đái tháo đường típ 2, không kèm biến chứng', 'Đái tháo đường típ 2'],
            ['J00',   'ICD10', 'Viêm mũi họng cấp tính [cảm thường]', 'Cảm lạnh thông thường'],
            ['J02.9', 'ICD10', 'Viêm họng cấp tính, không xác định', null],
            ['J18.9', 'ICD10', 'Viêm phổi, không xác định vi sinh vật', null],
            ['J45.9', 'ICD10', 'Hen phế quản, không xác định', null],
            ['K21.9', 'ICD11', 'Bệnh trào ngược dạ dày-thực quản không kèm viêm thực quản', 'GERD'],
            ['K29.7', 'ICD11', 'Viêm dạ dày, không xác định', null],
            ['N39.0', 'ICD14', 'Nhiễm khuẩn đường tiết niệu, không xác định vị trí', null],
            ['R50.9', 'ICD18', 'Sốt, không xác định', null],
            ['J01.9', 'ICD10', 'Viêm xoang cấp tính, không xác định', null],
            ['H60.9', 'ICD08', 'Viêm tai ngoài, không xác định', null],
            ['I20.9', 'ICD09', 'Đau thắt ngực, không xác định', null],
            ['L20.9', 'ICD12', 'Viêm da cơ địa, không xác định', null],
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
                    'description' => 'Mã và tên theo ICD-10 ban hành kèm Thông tư 06/2026/TT-BYT. Nhân viên y tế cần chọn mã phù hợp với chẩn đoán thực tế.',
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
