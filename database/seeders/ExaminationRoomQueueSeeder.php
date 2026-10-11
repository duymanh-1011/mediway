<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExaminationRoomQueueSeeder extends Seeder
{
    public function run(): void
    {
        $queues = [
            [
                'visit_code' => 'LK000001',
                'queue_status' => 'COMPLETED',
                'queue_number' => 1,
                'queue_type' => 'INITIAL_EXAM',
                'queued_at' => '2026-10-15 07:50:00',
                'called_at' => '2026-10-15 08:03:00',
                'service_started_at' => '2026-10-15 08:05:00',
                'completed_at' => '2026-10-15 08:25:00',
                'call_count' => 1,
            ],
            [
                'visit_code' => 'LK000001',
                'queue_status' => 'COMPLETED',
                'queue_number' => 4,
                'queue_type' => 'RETURN_AFTER_SERVICE',
                'queued_at' => '2026-10-15 09:00:00',
                'called_at' => '2026-10-15 09:10:00',
                'service_started_at' => '2026-10-15 09:12:00',
                'completed_at' => '2026-10-15 09:20:00',
                'call_count' => 1,
            ],
            [
                'visit_code' => 'LK000002',
                'queue_status' => 'COMPLETED',
                'queue_number' => 2,
                'queue_type' => 'INITIAL_EXAM',
                'queued_at' => '2026-10-15 07:55:00',
                'called_at' => '2026-10-15 08:08:00',
                'service_started_at' => '2026-10-15 08:10:00',
                'completed_at' => '2026-10-15 08:28:00',
                'call_count' => 1,
            ],
            [
                'visit_code' => 'LK000002',
                'queue_status' => 'COMPLETED',
                'queue_number' => 5,
                'queue_type' => 'RETURN_AFTER_SERVICE',
                'queued_at' => '2026-10-15 09:00:00',
                'called_at' => '2026-10-15 09:08:00',
                'service_started_at' => '2026-10-15 09:10:00',
                'completed_at' => '2026-10-15 09:15:00',
                'call_count' => 1,
            ],
            [
                'visit_code' => 'LK000003',
                'queue_status' => 'COMPLETED',
                'queue_number' => 3,
                'queue_type' => 'INITIAL_EXAM',
                'queued_at' => '2026-10-15 08:00:00',
                'called_at' => '2026-10-15 08:13:00',
                'service_started_at' => '2026-10-15 08:15:00',
                'completed_at' => '2026-10-15 08:50:00',
                'call_count' => 1,
            ],
            [
                'visit_code' => 'LK000004',
                'queue_status' => 'IN_SERVICE',
                'queue_number' => 6,
                'queue_type' => 'INITIAL_EXAM',
                'queued_at' => '2026-10-15 08:25:00',
                'called_at' => '2026-10-15 08:33:00',
                'service_started_at' => '2026-10-15 08:35:00',
                'completed_at' => null,
                'call_count' => 1,
            ],
            [
                'visit_code' => 'LK000005',
                'queue_status' => 'IN_SERVICE',
                'queue_number' => 7,
                'queue_type' => 'INITIAL_EXAM',
                'queued_at' => '2026-10-15 08:20:00',
                'called_at' => '2026-10-15 08:38:00',
                'service_started_at' => '2026-10-15 08:40:00',
                'completed_at' => null,
                'call_count' => 1,
            ],
        ];

        foreach ($queues as $data) {
            $medicalVisitId = DB::table('medical_visits')->where('visit_code', $data['visit_code'])->value('id');
            $queueStatusId = DB::table('queue_statuses')->where('name', $data['queue_status'])->value('id');

            DB::table('examination_room_queues')->updateOrInsert(
                [
                    'medical_visit_id' => $medicalVisitId,
                    'queue_type' => $data['queue_type'],
                    'queued_at' => $data['queued_at'],
                ],
                [
                    'queue_status_id' => $queueStatusId,
                    'queue_number' => $data['queue_number'],
                    'called_at' => $data['called_at'],
                    'service_started_at' => $data['service_started_at'],
                    'completed_at' => $data['completed_at'],
                    'call_count' => $data['call_count'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
