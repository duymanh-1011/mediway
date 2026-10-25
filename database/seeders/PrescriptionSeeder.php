<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PrescriptionSeeder extends Seeder
{
    public function run(): void
    {
        $prescriptions = [
            [
                'prescription_code' => 'DT000001',
                'visit_code' => 'LK000001',
                'doctor_code' => 'BS000001',
                'prescribed_at' => '2026-10-15 09:16:00',
                'status' => 'ACTIVE',
                'general_instructions' => 'Uống đủ nước, vệ sinh mũi hằng ngày',
            ],
            [
                'prescription_code' => 'DT000002',
                'visit_code' => 'LK000002',
                'doctor_code' => 'BS000002',
                'prescribed_at' => '2026-10-15 09:12:00',
                'status' => 'ACTIVE',
                'general_instructions' => 'Theo dõi huyết áp tại nhà',
            ],
            [
                'prescription_code' => 'DT000003',
                'visit_code' => 'LK000003',
                'doctor_code' => 'BS000003',
                'prescribed_at' => '2026-10-15 08:47:00',
                'status' => 'ACTIVE',
                'general_instructions' => 'Ăn đúng giờ, hạn chế đồ cay',
            ],
        ];

        foreach ($prescriptions as $prescription) {
            $medicalVisitId = DB::table('medical_visits')
                ->where('visit_code', $prescription['visit_code'])
                ->value('id');

            $doctorId = DB::table('doctors')
                ->where('doctor_code', $prescription['doctor_code'])
                ->value('id');

            if (! $medicalVisitId || ! $doctorId) {
                throw new RuntimeException(
                    'Thiếu dữ liệu FK cho đơn thuốc '.
                        $prescription['prescription_code']
                );
            }

            DB::table('prescriptions')->updateOrInsert(
                [
                    'prescription_code' => $prescription['prescription_code'],
                ],
                [
                    'medical_visit_id' => $medicalVisitId,
                    'doctor_id' => $doctorId,
                    'prescribed_at' => $prescription['prescribed_at'],
                    'general_instructions' => $prescription['general_instructions'],
                    'doctor_notes' => null,
                    'status' => $prescription['status'],
                    'prescription_pdf_path' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
