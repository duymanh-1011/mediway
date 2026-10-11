<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VisitDiagnosisSeeder extends Seeder
{
    public function run(): void
    {
        $diagnoses = [
            [
                'visit_code' => 'LK000001',
                'disease_code' => 'J01.9',
                'diagnosis_type' => 'PRIMARY',
                'severity' => 'MODERATE',
                'diagnosis_description' => 'Viêm xoang cấp',
                'diagnosed_at' => '2026-10-15 08:25:00',
            ],
            [
                'visit_code' => 'LK000001',
                'disease_code' => 'J00',
                'diagnosis_type' => 'SECONDARY',
                'severity' => 'MILD',
                'diagnosis_description' => 'Viêm mũi họng kèm theo',
                'diagnosed_at' => '2026-10-15 08:25:00',
            ],
            [
                'visit_code' => 'LK000002',
                'disease_code' => 'I10',
                'diagnosis_type' => 'PRIMARY',
                'severity' => 'MODERATE',
                'diagnosis_description' => 'Tăng huyết áp nguyên phát',
                'diagnosed_at' => '2026-10-15 08:28:00',
            ],
            [
                'visit_code' => 'LK000003',
                'disease_code' => 'K29.7',
                'diagnosis_type' => 'PRIMARY',
                'severity' => 'MILD',
                'diagnosis_description' => 'Viêm dạ dày',
                'diagnosed_at' => '2026-10-15 08:35:00',
            ],
            [
                'visit_code' => 'LK000004',
                'disease_code' => 'J02.9',
                'diagnosis_type' => 'PRELIMINARY',
                'severity' => 'MILD',
                'diagnosis_description' => 'Theo dõi viêm họng cấp',
                'diagnosed_at' => '2026-10-15 08:45:00',
            ],
            [
                'visit_code' => 'LK000005',
                'disease_code' => 'I20.9',
                'diagnosis_type' => 'PRELIMINARY',
                'severity' => 'MODERATE',
                'diagnosis_description' => 'Cần làm rõ nguyên nhân đau ngực',
                'diagnosed_at' => '2026-10-15 08:50:00',
            ],
        ];

        foreach ($diagnoses as $data) {
            $medicalVisitId = DB::table('medical_visits')->where('visit_code', $data['visit_code'])->value('id');
            $diseaseId = DB::table('diseases')->where('disease_code', $data['disease_code'])->value('id');

            DB::table('visit_diagnoses')->updateOrInsert(
                [
                    'medical_visit_id' => $medicalVisitId,
                    'disease_id' => $diseaseId,
                    'diagnosis_type' => $data['diagnosis_type'],
                ],
                [
                    'severity' => $data['severity'],
                    'diagnosis_description' => $data['diagnosis_description'],
                    'diagnosed_at' => $data['diagnosed_at'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
