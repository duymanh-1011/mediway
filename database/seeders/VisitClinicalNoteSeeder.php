<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VisitClinicalNoteSeeder extends Seeder
{
    public function run(): void
    {
        $notes = [
            [
                'visit_code' => 'LK000001',
                'reason_for_visit' => 'Viêm mũi, đau vùng mặt',
                'current_symptoms' => 'Nghẹt mũi, chảy mũi, đau vùng xoang',
                'initial_assessment' => 'Theo dõi viêm xoang cấp',
                'recorded_at' => '2026-10-15 08:20:00',
            ],
            [
                'visit_code' => 'LK000002',
                'reason_for_visit' => 'Đau đầu, hồi hộp',
                'current_symptoms' => 'Đau đầu nhẹ, tim đập nhanh',
                'initial_assessment' => 'Theo dõi tăng huyết áp',
                'recorded_at' => '2026-10-15 08:25:00',
            ],
            [
                'visit_code' => 'LK000003',
                'reason_for_visit' => 'Đau thượng vị',
                'current_symptoms' => 'Đau âm ỉ sau ăn',
                'initial_assessment' => 'Theo dõi viêm dạ dày',
                'recorded_at' => '2026-10-15 08:30:00',
            ],
            [
                'visit_code' => 'LK000004',
                'reason_for_visit' => 'Đau họng, sốt nhẹ',
                'current_symptoms' => 'Đau rát họng, ho khan',
                'initial_assessment' => 'Theo dõi viêm họng cấp',
                'recorded_at' => '2026-10-15 08:45:00',
            ],
            [
                'visit_code' => 'LK000005',
                'reason_for_visit' => 'Đau ngực thoáng qua',
                'current_symptoms' => 'Đau ngực khi gắng sức',
                'initial_assessment' => 'Cần loại trừ đau thắt ngực',
                'recorded_at' => '2026-10-15 08:50:00',
            ],
        ];

        foreach ($notes as $data) {
            $medicalVisitId = DB::table('medical_visits')->where('visit_code', $data['visit_code'])->value('id');

            DB::table('visit_clinical_notes')->updateOrInsert(
                ['medical_visit_id' => $medicalVisitId],
                [
                    'reason_for_visit' => $data['reason_for_visit'],
                    'current_symptoms' => $data['current_symptoms'],
                    'initial_assessment' => $data['initial_assessment'],
                    'recorded_at' => $data['recorded_at'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
