<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PersonalHealthRecordSeeder extends Seeder
{
    public function run(): void
    {
        $records = [
            ['BN000001', 170, 65,   22.49, 120, 78, 72, '2026-10-01 08:00:00'],
            ['BN000001', 170, 65.5, 22.66, 118, 76, 70, '2026-10-15 07:30:00'],

            ['BN000002', 158, 58,   23.23, 138, 88, 82, '2026-10-01 08:00:00'],
            ['BN000002', 158, 58.2, 23.31, 136, 86, 80, '2026-10-15 07:30:00'],

            ['BN000003', 168, 70,   24.80, 128, 82, 76, '2026-10-01 08:00:00'],
            ['BN000003', 168, 69.8, 24.73, 126, 80, 74, '2026-10-15 07:30:00'],

            ['BN000004', 162, 52,   19.81, 110, 70, 68, '2026-10-01 08:00:00'],
            ['BN000004', 162, 52.1, 19.85, 112, 72, 70, '2026-10-15 07:30:00'],

            ['BN000005', 175, 78,   25.47, 145, 92, 88, '2026-10-01 08:00:00'],
            ['BN000005', 175, 77.8, 25.40, 142, 90, 84, '2026-10-15 07:30:00'],
        ];

        foreach ($records as $record) {
            [
                $code,
                $height,
                $weight,
                $bmi,
                $systolic,
                $diastolic,
                $heartRate,
                $recordedAt
            ] = $record;

            $patientId = DB::table('patients')
                ->where('patient_code', $code)
                ->value('id');

            DB::table('personal_health_records')->updateOrInsert(
                [
                    'patient_id' => $patientId,
                    'recorded_at' => $recordedAt,
                ],
                [
                    'patient_code' => $code,
                    'height_cm' => $height,
                    'weight_kg' => $weight,
                    'bmi' => $bmi,
                    'systolic_blood_pressure' => $systolic,
                    'diastolic_blood_pressure' => $diastolic,
                    'heart_rate' => $heartRate,
                    'body_temperature' => null,
                    'spo2' => null,
                    'respiratory_rate' => null,
                    'blood_glucose' => null,
                    'notes' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
