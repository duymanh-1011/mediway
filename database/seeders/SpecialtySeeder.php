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
                'specialty_code' => 'CKDK',
                'name' => 'Đa khoa',
                'description' => 'Khám ban đầu và tư vấn chuyên khoa phù hợp.',
                'pre_visit_instructions' => 'Mang theo hồ sơ khám và kết quả trước đây nếu có',
            ],
            [
                'specialty_code' => 'CKTMH',
                'name' => 'Tai Mũi Họng',
                'description' => 'Khám các vấn đề về tai, mũi, xoang và họng.',
                'pre_visit_instructions' => 'Mang theo đơn thuốc và kết quả khám cũ nếu có',
            ],
            [
                'specialty_code' => 'CKTM',
                'name' => 'Tim mạch',
                'description' => 'Khám và theo dõi các vấn đề về tim và mạch máu.',
                'pre_visit_instructions' => 'Nghỉ ngơi trước khi đo huyết áp',
            ],
            [
                'specialty_code' => 'CKNOI',
                'name' => 'Nội tổng quát',
                'description' => 'Khám nội khoa tổng quát và theo dõi bệnh mạn tính.',
                'pre_visit_instructions' => 'Nhịn ăn nếu có chỉ định xét nghiệm',
            ],
            [
                'specialty_code' => 'CKTH',
                'name' => 'Tiêu hóa',
                'description' => 'Khám các vấn đề về dạ dày, đường ruột và gan mật.',
                'pre_visit_instructions' => 'Có thể cần nhịn ăn theo chỉ định',
            ],
            [
                'specialty_code' => 'CKDL',
                'name' => 'Da liễu',
                'description' => 'Khám các vấn đề về da, tóc và móng.',
                'pre_visit_instructions' => 'Không bôi thuốc mới trước khi khám nếu không cần thiết',
            ],
        ];

        foreach ($specialties as $specialty) {
            DB::table('specialties')->updateOrInsert(
                ['specialty_code' => $specialty['specialty_code']],
                [
                    'name' => $specialty['name'],
                    'is_general' => $specialty['specialty_code'] === 'CKDK',
                    'description' => $specialty['description'],
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
