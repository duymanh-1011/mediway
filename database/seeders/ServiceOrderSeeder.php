<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceOrderSeeder extends Seeder
{
    public function run(): void
    {
        $orders = [
            [
                'service_order_code' => 'CD000001',
                'visit_code' => 'LK000001',
                'doctor_code' => 'BS000001',
                'ordered_at' => '2026-10-15 08:26:00',
                'status' => 'COMPLETED',
                'reason' => 'Đánh giá xoang và niêm mạc TMH',
            ],
            [
                'service_order_code' => 'CD000002',
                'visit_code' => 'LK000002',
                'doctor_code' => 'BS000002',
                'ordered_at' => '2026-10-15 08:29:00',
                'status' => 'COMPLETED',
                'reason' => 'Đánh giá tim mạch và đường huyết',
            ],
            [
                'service_order_code' => 'CD000003',
                'visit_code' => 'LK000003',
                'doctor_code' => 'BS000003',
                'ordered_at' => '2026-10-15 08:36:00',
                'status' => 'COMPLETED',
                'reason' => 'Khảo sát ổ bụng',
            ],
            [
                'service_order_code' => 'CD000004',
                'visit_code' => 'LK000004',
                'doctor_code' => 'BS000001',
                'ordered_at' => '2026-10-15 08:46:00',
                'status' => 'ACTIVE',
                'reason' => 'Đánh giá viêm nhiễm',
            ],
        ];

        foreach ($orders as $order) {
            $medicalVisitId = DB::table('medical_visits')
                ->where('visit_code', $order['visit_code'])
                ->value('id');

            $doctorId = DB::table('doctors')
                ->where('doctor_code', $order['doctor_code'])
                ->value('id');

            DB::table('service_orders')->updateOrInsert(
                [
                    'service_order_code' => $order['service_order_code'],
                ],
                [
                    'medical_visit_id' => $medicalVisitId,
                    'ordered_by_doctor_id' => $doctorId,
                    'ordered_at' => $order['ordered_at'],
                    'reason' => $order['reason'],
                    'instructions' => null,
                    'status' => $order['status'],
                    'order_pdf_path' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
