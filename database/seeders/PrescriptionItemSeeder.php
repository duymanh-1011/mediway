<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PrescriptionItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'prescription_code' => 'DT000001',
                'medicine_code' => 'TH002',
                'quantity' => 14,
                'usage_unit' => 'viên',
                'dose_per_use' => '1 viên',
                'times_per_day' => 2,
                'duration_days' => 7,
                'usage_method' => 'Uống sau ăn',
            ],
            [
                'prescription_code' => 'DT000001',
                'medicine_code' => 'TH003',
                'quantity' => 7,
                'usage_unit' => 'viên',
                'dose_per_use' => '1 viên',
                'times_per_day' => 1,
                'duration_days' => 7,
                'usage_method' => 'Uống',
            ],
            [
                'prescription_code' => 'DT000001',
                'medicine_code' => 'TH004',
                'quantity' => 1,
                'usage_unit' => 'chai',
                'dose_per_use' => '2 nhát mỗi bên',
                'times_per_day' => 3,
                'duration_days' => 7,
                'usage_method' => 'Xịt mũi',
            ],
            [
                'prescription_code' => 'DT000002',
                'medicine_code' => 'TH005',
                'quantity' => 14,
                'usage_unit' => 'viên',
                'dose_per_use' => '1 viên',
                'times_per_day' => 1,
                'duration_days' => 14,
                'usage_method' => 'Uống',
            ],
            [
                'prescription_code' => 'DT000003',
                'medicine_code' => 'TH006',
                'quantity' => 14,
                'usage_unit' => 'viên',
                'dose_per_use' => '1 viên',
                'times_per_day' => 2,
                'duration_days' => 7,
                'usage_method' => 'Uống trước ăn 30 phút',
            ],
            [
                'prescription_code' => 'DT000003',
                'medicine_code' => 'TH007',
                'quantity' => 14,
                'usage_unit' => 'gói',
                'dose_per_use' => '1 gói',
                'times_per_day' => 2,
                'duration_days' => 7,
                'usage_method' => 'Uống',
            ],
        ];

        foreach ($items as $item) {
            $prescriptionId = DB::table('prescriptions')
                ->where('prescription_code', $item['prescription_code'])
                ->value('id');

            $medicineId = DB::table('medicines')
                ->where('medicine_code', $item['medicine_code'])
                ->value('id');

            if (! $prescriptionId || ! $medicineId) {
                throw new RuntimeException(
                    'Thiếu dữ liệu FK cho '.
                        $item['prescription_code'].' / '.
                        $item['medicine_code']
                );
            }

            DB::table('prescription_items')->updateOrInsert(
                [
                    'prescription_id' => $prescriptionId,
                    'medicine_id' => $medicineId,
                ],
                [
                    'quantity' => $item['quantity'],
                    'usage_unit' => $item['usage_unit'],
                    'dose_per_use' => $item['dose_per_use'],
                    'times_per_day' => $item['times_per_day'],
                    'duration_days' => $item['duration_days'],
                    'administration_time' => null,
                    'usage_method' => $item['usage_method'],
                    'usage_instructions' => null,
                    'notes' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
