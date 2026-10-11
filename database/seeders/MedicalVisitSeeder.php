<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MedicalVisitSeeder extends Seeder
{
    public function run(): void
    {
        $visits = [
            [
                'visit_code' => 'LK000001',
                'appointment_code' => 'LH000001',
                'patient_code' => 'BN000001',
                'doctor_code' => 'BS000001',
                'specialty_code' => 'CKTMH',
                'room_code' => 'PKTMH01',
                'visit_status' => 'COMPLETED',
                'checked_in_at' => '2026-10-15 07:50:00',
                'reception_source' => 'KIOSK',
            ],
            [
                'visit_code' => 'LK000002',
                'appointment_code' => 'LH000002',
                'patient_code' => 'BN000002',
                'doctor_code' => 'BS000002',
                'specialty_code' => 'CKTM',
                'room_code' => 'PKTM01',
                'visit_status' => 'COMPLETED',
                'checked_in_at' => '2026-10-15 07:55:00',
                'reception_source' => 'KIOSK',
            ],
            [
                'visit_code' => 'LK000003',
                'appointment_code' => 'LH000003',
                'patient_code' => 'BN000003',
                'doctor_code' => 'BS000003',
                'specialty_code' => 'CKNOI',
                'room_code' => 'PKNOI01',
                'visit_status' => 'COMPLETED',
                'checked_in_at' => '2026-10-15 08:00:00',
                'reception_source' => 'KIOSK',
            ],
            [
                'visit_code' => 'LK000004',
                'appointment_code' => 'LH000004',
                'patient_code' => 'BN000004',
                'doctor_code' => 'BS000001',
                'specialty_code' => 'CKTMH',
                'room_code' => 'PKTMH01',
                'visit_status' => 'WAITING_FOR_SERVICE',
                'checked_in_at' => '2026-10-15 08:25:00',
                'reception_source' => 'KIOSK',
            ],
            [
                'visit_code' => 'LK000005',
                'appointment_code' => 'LH000005',
                'patient_code' => 'BN000005',
                'doctor_code' => 'BS000002',
                'specialty_code' => 'CKTM',
                'room_code' => 'PKTM01',
                'visit_status' => 'IN_EXAMINATION',
                'checked_in_at' => '2026-10-15 08:20:00',
                'reception_source' => 'KIOSK',
            ],
        ];

        foreach ($visits as $data) {
            $appointmentId = DB::table('appointments')->where('appointment_code', $data['appointment_code'])->value('id');
            $patientId = DB::table('patients')->where('patient_code', $data['patient_code'])->value('id');
            $doctorId = DB::table('doctors')->where('doctor_code', $data['doctor_code'])->value('id');
            $specialtyId = DB::table('specialties')->where('specialty_code', $data['specialty_code'])->value('id');
            $roomId = DB::table('examination_rooms')->where('room_code', $data['room_code'])->value('id');
            $visitStatusId = DB::table('visit_statuses')->where('name', $data['visit_status'])->value('id');

            DB::table('medical_visits')->updateOrInsert(
                ['visit_code' => $data['visit_code']],
                [
                    'appointment_id' => $appointmentId,
                    'patient_id' => $patientId,
                    'doctor_id' => $doctorId,
                    'specialty_id' => $specialtyId,
                    'examination_room_id' => $roomId,
                    'visit_status_id' => $visitStatusId,
                    'checked_in_at' => $data['checked_in_at'],
                    'reception_source' => $data['reception_source'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
