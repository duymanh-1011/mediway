<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AppointmentSeeder extends Seeder
{
    public function run(): void
    {
        $appointments = [
            [
                'code' => 'LH000001',
                'patient' => 'BN000001',
                'doctor' => 'BS000001',
                'specialty' => 'CKTMH',
                'room' => 'PKTMH01',
                'slot_start' => '08:00:00',
                'slot_end' => '08:30:00',
                'status' => 'COMPLETED',
                'token' => 'CHECKIN-LH000001-DEMO',
                'booked_at' => '2026-10-10 09:00:00',
            ],
            [
                'code' => 'LH000002',
                'patient' => 'BN000002',
                'doctor' => 'BS000002',
                'specialty' => 'CKTM',
                'room' => 'PKTM01',
                'slot_start' => '08:00:00',
                'slot_end' => '08:30:00',
                'status' => 'COMPLETED',
                'token' => 'CHECKIN-LH000002-DEMO',
                'booked_at' => '2026-10-10 09:10:00',
            ],
            [
                'code' => 'LH000003',
                'patient' => 'BN000003',
                'doctor' => 'BS000003',
                'specialty' => 'CKNOI',
                'room' => 'PKNOI01',
                'slot_start' => '08:00:00',
                'slot_end' => '08:30:00',
                'status' => 'COMPLETED',
                'token' => 'CHECKIN-LH000003-DEMO',
                'booked_at' => '2026-10-10 09:20:00',
            ],
            [
                'code' => 'LH000004',
                'patient' => 'BN000004',
                'doctor' => 'BS000001',
                'specialty' => 'CKTMH',
                'room' => 'PKTMH01',
                'slot_start' => '08:30:00',
                'slot_end' => '09:00:00',
                'status' => 'IN_EXAMINATION',
                'token' => 'CHECKIN-LH000004-DEMO',
                'booked_at' => '2026-10-11 10:00:00',
            ],
            [
                'code' => 'LH000005',
                'patient' => 'BN000005',
                'doctor' => 'BS000002',
                'specialty' => 'CKTM',
                'room' => 'PKTM01',
                'slot_start' => '08:30:00',
                'slot_end' => '09:00:00',
                'status' => 'IN_EXAMINATION',
                'token' => 'CHECKIN-LH000005-DEMO',
                'booked_at' => '2026-10-11 10:15:00',
            ],
            [
                'code' => 'LH000006',
                'patient' => 'BN000001',
                'doctor' => 'BS000003',
                'specialty' => 'CKNOI',
                'room' => 'PKNOI01',
                'slot_start' => '08:30:00',
                'slot_end' => '09:00:00',
                'status' => 'CANCELLED',
                'token' => 'CHECKIN-LH000006-DEMO',
                'booked_at' => '2026-10-11 10:30:00',
            ],
        ];

        foreach ($appointments as $appointment) {
            $patientId = DB::table('patients')
                ->where('patient_code', $appointment['patient'])
                ->value('id');

            $doctorId = DB::table('doctors')
                ->where('doctor_code', $appointment['doctor'])
                ->value('id');

            $specialtyId = DB::table('specialties')
                ->where('specialty_code', $appointment['specialty'])
                ->value('id');

            $roomId = DB::table('examination_rooms')
                ->where('room_code', $appointment['room'])
                ->value('id');

            $scheduleId = DB::table('doctor_schedules')
                ->where('doctor_id', $doctorId)
                ->where('work_date', '2026-10-15')
                ->where('start_time', '08:00:00')
                ->where('end_time', '11:00:00')
                ->value('id');

            $slotId = DB::table('appointment_time_slots')
                ->where('doctor_schedule_id', $scheduleId)
                ->where('start_time', $appointment['slot_start'])
                ->where('end_time', $appointment['slot_end'])
                ->value('id');

            $statusId = DB::table('appointment_statuses')
                ->where('name', $appointment['status'])
                ->value('id');

            DB::table('appointments')->updateOrInsert(
                ['appointment_code' => $appointment['code']],
                [
                    'patient_id' => $patientId,
                    'doctor_id' => $doctorId,
                    'specialty_id' => $specialtyId,
                    'examination_room_id' => $roomId,

                    'appointment_date' => '2026-10-15',
                    'appointment_time_slot_id' => $slotId,
                    'appointment_status_id' => $statusId,

                    'checkin_token' => $appointment['token'],

                    'reason_for_visit' => null,
                    'patient_notes' => null,

                    'booked_at' => $appointment['booked_at'],

                    'cancelled_at' => $appointment['status'] === 'CANCELLED'
                        ? '2026-10-14 15:00:00'
                        : null,

                    'cancellation_reason' => $appointment['status'] === 'CANCELLED'
                        ? 'Bệnh nhân hủy lịch'
                        : null,

                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
