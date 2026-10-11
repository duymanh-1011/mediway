<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VisitConclusionSeeder extends Seeder
{
    public function run(): void
    {
        $conclusions = [
            [
                'visit_code' => 'LK000001',
                'doctor_code' => 'BS000001',
                'professional_conclusion' => 'Viêm xoang cấp, đáp ứng điều trị ngoại trú',
                'disposition' => 'OUTPATIENT',
                'has_follow_up' => true,
                'follow_up_date' => '2026-10-22',
                'concluded_at' => '2026-10-15 09:20:00',
            ],
            [
                'visit_code' => 'LK000002',
                'doctor_code' => 'BS000002',
                'professional_conclusion' => 'Tăng huyết áp nguyên phát',
                'disposition' => 'OUTPATIENT',
                'has_follow_up' => true,
                'follow_up_date' => '2026-10-29',
                'concluded_at' => '2026-10-15 09:15:00',
            ],
            [
                'visit_code' => 'LK000003',
                'doctor_code' => 'BS000003',
                'professional_conclusion' => 'Viêm dạ dày chưa biến chứng',
                'disposition' => 'OUTPATIENT',
                'has_follow_up' => false,
                'follow_up_date' => null,
                'concluded_at' => '2026-10-15 08:50:00',
            ],
        ];

        foreach ($conclusions as $data) {
            $medicalVisitId = DB::table('medical_visits')->where('visit_code', $data['visit_code'])->value('id');
            $doctorId = DB::table('doctors')->where('doctor_code', $data['doctor_code'])->value('id');

            DB::table('visit_conclusions')->updateOrInsert(
                ['medical_visit_id' => $medicalVisitId],
                [
                    'doctor_id' => $doctorId,
                    'professional_conclusion' => $data['professional_conclusion'],
                    'disposition' => $data['disposition'],
                    'has_follow_up' => $data['has_follow_up'],
                    'follow_up_date' => $data['follow_up_date'],
                    'concluded_at' => $data['concluded_at'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
