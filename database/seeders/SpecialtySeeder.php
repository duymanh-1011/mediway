<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SpecialtySeeder extends Seeder
{
    public function run(): void
    {
        $specialties = [
            [
                'specialty_code' => 'CKTMH',
                'name' => 'Tai Mũi Họng',
                'pre_visit_instructions' => 'Mang theo đơn thuốc và kết quả khám cũ nếu có',
            ],
            [
                'specialty_code' => 'CKTM',
                'name' => 'Tim mạch',
                'pre_visit_instructions' => 'Nghỉ ngơi trước khi đo huyết áp',
            ],
            [
                'specialty_code' => 'CKNOI',
                'name' => 'Nội tổng quát',
                'pre_visit_instructions' => 'Nhịn ăn nếu có chỉ định xét nghiệm',
            ],
            [
                'specialty_code' => 'CKTH',
                'name' => 'Tiêu hóa',
                'pre_visit_instructions' => 'Có thể cần nhịn ăn theo chỉ định',
            ],
            [
                'specialty_code' => 'CKDL',
                'name' => 'Da liễu',
                'pre_visit_instructions' => 'Không bôi thuốc mới trước khi khám nếu không cần thiết',
            ],
        ];

        foreach ($specialties as $specialty) {
            DB::table('specialties')->updateOrInsert(
                ['specialty_code' => $specialty['specialty_code']],
                [
                    'name' => $specialty['name'],
                    'description' => null,
                    'image_path' => null,
                    'pre_visit_instructions' => $specialty['pre_visit_instructions'],
                    'status' => 'ACTIVE',
                    'notes' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}