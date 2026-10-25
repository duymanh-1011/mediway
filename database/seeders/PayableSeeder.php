<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PayableSeeder extends Seeder
{
    public function run(): void
    {
        $payables = [
            [
                'payable_code' => 'PT000001',
                'visit_code' => 'LK000001',
                'service_order_code' => null,
                'service_code' => null,
                'payment_type' => 'EXAMINATION_FEE',
                'amount' => 200000,
                'payment_status' => 'PAID',
                'paid_at' => '2026-10-15 07:55:00',
            ],
            [
                'payable_code' => 'PT000002',
                'visit_code' => 'LK000002',
                'service_order_code' => null,
                'service_code' => null,
                'payment_type' => 'EXAMINATION_FEE',
                'amount' => 250000,
                'payment_status' => 'PAID',
                'paid_at' => '2026-10-15 07:55:00',
            ],
            [
                'payable_code' => 'PT000003',
                'visit_code' => 'LK000003',
                'service_order_code' => null,
                'service_code' => null,
                'payment_type' => 'EXAMINATION_FEE',
                'amount' => 200000,
                'payment_status' => 'PAID',
                'paid_at' => '2026-10-15 07:55:00',
            ],
            [
                'payable_code' => 'PT000004',
                'visit_code' => 'LK000004',
                'service_order_code' => null,
                'service_code' => null,
                'payment_type' => 'EXAMINATION_FEE',
                'amount' => 200000,
                'payment_status' => 'UNPAID',
                'paid_at' => null,
            ],
            [
                'payable_code' => 'PT000005',
                'visit_code' => 'LK000005',
                'service_order_code' => null,
                'service_code' => null,
                'payment_type' => 'EXAMINATION_FEE',
                'amount' => 250000,
                'payment_status' => 'UNPAID',
                'paid_at' => null,
            ],
            [
                'payable_code' => 'PT000006',
                'visit_code' => 'LK000001',
                'service_order_code' => 'CD000001',
                'service_code' => 'DVCD002',
                'payment_type' => 'SERVICE_FEE',
                'amount' => 200000,
                'payment_status' => 'PAID',
                'paid_at' => '2026-10-15 08:35:00',
            ],
            [
                'payable_code' => 'PT000007',
                'visit_code' => 'LK000001',
                'service_order_code' => 'CD000001',
                'service_code' => 'DVTDCN002',
                'payment_type' => 'SERVICE_FEE',
                'amount' => 250000,
                'payment_status' => 'PAID',
                'paid_at' => '2026-10-15 08:35:00',
            ],
            [
                'payable_code' => 'PT000008',
                'visit_code' => 'LK000002',
                'service_order_code' => 'CD000002',
                'service_code' => 'DVTDCN001',
                'payment_type' => 'SERVICE_FEE',
                'amount' => 150000,
                'payment_status' => 'PAID',
                'paid_at' => '2026-10-15 08:35:00',
            ],
            [
                'payable_code' => 'PT000009',
                'visit_code' => 'LK000002',
                'service_order_code' => 'CD000002',
                'service_code' => 'DVXN003',
                'payment_type' => 'SERVICE_FEE',
                'amount' => 50000,
                'payment_status' => 'PAID',
                'paid_at' => '2026-10-15 08:35:00',
            ],
            [
                'payable_code' => 'PT000010',
                'visit_code' => 'LK000003',
                'service_order_code' => 'CD000003',
                'service_code' => 'DVCD003',
                'payment_type' => 'SERVICE_FEE',
                'amount' => 300000,
                'payment_status' => 'PAID',
                'paid_at' => '2026-10-15 08:35:00',
            ],
            [
                'payable_code' => 'PT000011',
                'visit_code' => 'LK000004',
                'service_order_code' => 'CD000004',
                'service_code' => 'DVXN001',
                'payment_type' => 'SERVICE_FEE',
                'amount' => 120000,
                'payment_status' => 'UNPAID',
                'paid_at' => null,
            ],
            [
                'payable_code' => 'PT000012',
                'visit_code' => 'LK000004',
                'service_order_code' => 'CD000004',
                'service_code' => 'DVXN002',
                'payment_type' => 'SERVICE_FEE',
                'amount' => 150000,
                'payment_status' => 'UNPAID',
                'paid_at' => null,
            ],
        ];

        foreach ($payables as $payable) {
            $medicalVisitId = DB::table('medical_visits')
                ->where('visit_code', $payable['visit_code'])
                ->value('id');

            $paymentStatusId = DB::table('payment_statuses')
                ->where('name', $payable['payment_status'])
                ->value('id');

            $serviceOrderItemId = null;

            if ($payable['payment_type'] === 'SERVICE_FEE') {
                $serviceOrderItemId = DB::table('service_order_items as soi')
                    ->join(
                        'service_orders as so',
                        'so.id',
                        '=',
                        'soi.service_order_id'
                    )
                    ->join(
                        'medical_services as ms',
                        'ms.id',
                        '=',
                        'soi.medical_service_id'
                    )
                    ->where(
                        'so.service_order_code',
                        $payable['service_order_code']
                    )
                    ->where(
                        'ms.service_code',
                        $payable['service_code']
                    )
                    ->value('soi.id');
            }

            if (! $medicalVisitId || ! $paymentStatusId) {
                throw new RuntimeException(
                    'Thiếu dữ liệu FK cho '.
                        $payable['payable_code']
                );
            }

            if (
                $payable['payment_type'] === 'SERVICE_FEE'
                && ! $serviceOrderItemId
            ) {
                throw new RuntimeException(
                    'Không tìm thấy service_order_item cho '.
                        $payable['payable_code']
                );
            }

            DB::table('payables')->updateOrInsert(
                [
                    'payable_code' => $payable['payable_code'],
                ],
                [
                    'medical_visit_id' => $medicalVisitId,
                    'service_order_item_id' => $serviceOrderItemId,
                    'payment_type' => $payable['payment_type'],
                    'description' => $payable['payment_type'] === 'EXAMINATION_FEE'
                        ? 'Phí khám'
                        : 'Phí dịch vụ',
                    'amount' => $payable['amount'],
                    'payment_status_id' => $paymentStatusId,
                    'due_at' => null,
                    'payment_qr_data' => null,
                    'generated_at' => '2026-10-15 07:45:00',
                    'paid_at' => $payable['paid_at'],
                    'notes' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
