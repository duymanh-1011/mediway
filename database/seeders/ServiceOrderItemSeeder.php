<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ServiceOrderItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'service_order_code' => 'CD000001',
                'service_code' => 'DVCD002',
                'service_status' => 'COMPLETED',
                'price_at_order' => 200000,
                'employee_code' => 'NV000003',
                'started_at' => '2026-10-15 08:40:00',
                'completed_at' => '2026-10-15 08:50:00',
            ],
            [
                'service_order_code' => 'CD000001',
                'service_code' => 'DVTDCN002',
                'service_status' => 'COMPLETED',
                'price_at_order' => 250000,
                'employee_code' => null,
                'started_at' => '2026-10-15 08:52:00',
                'completed_at' => '2026-10-15 08:58:00',
            ],
            [
                'service_order_code' => 'CD000002',
                'service_code' => 'DVTDCN001',
                'service_status' => 'COMPLETED',
                'price_at_order' => 150000,
                'employee_code' => 'NV000001',
                'started_at' => '2026-10-15 08:40:00',
                'completed_at' => '2026-10-15 08:50:00',
            ],
            [
                'service_order_code' => 'CD000002',
                'service_code' => 'DVXN003',
                'service_status' => 'COMPLETED',
                'price_at_order' => 50000,
                'employee_code' => 'NV000002',
                'started_at' => '2026-10-15 08:45:00',
                'completed_at' => '2026-10-15 08:55:00',
            ],
            [
                'service_order_code' => 'CD000003',
                'service_code' => 'DVCD003',
                'service_status' => 'COMPLETED',
                'price_at_order' => 300000,
                'employee_code' => 'NV000003',
                'started_at' => '2026-10-15 08:40:00',
                'completed_at' => '2026-10-15 08:47:00',
            ],
            [
                'service_order_code' => 'CD000004',
                'service_code' => 'DVXN001',
                'service_status' => 'PENDING',
                'price_at_order' => 120000,
                'employee_code' => null,
                'started_at' => null,
                'completed_at' => null,
            ],
            [
                'service_order_code' => 'CD000004',
                'service_code' => 'DVXN002',
                'service_status' => 'PENDING',
                'price_at_order' => 150000,
                'employee_code' => null,
                'started_at' => null,
                'completed_at' => null,
            ],
        ];

        foreach ($items as $item) {
            $serviceOrderId = DB::table('service_orders')
                ->where('service_order_code', $item['service_order_code'])
                ->value('id');

            $medicalServiceId = DB::table('medical_services')
                ->where('service_code', $item['service_code'])
                ->value('id');

            $serviceStatusId = DB::table('service_statuses')
                ->where('name', $item['service_status'])
                ->value('id');

            $employeeId = null;

            if ($item['employee_code'] !== null) {
                $employeeId = DB::table('employees')
                    ->where('employee_code', $item['employee_code'])
                    ->value('id');
            }

            if (! $serviceOrderId || ! $medicalServiceId || ! $serviceStatusId) {
                throw new RuntimeException(
                    'Thiếu dữ liệu FK cho '.
                        $item['service_order_code'].' / '.
                        $item['service_code']
                );
            }

            DB::table('service_order_items')->updateOrInsert(
                [
                    'service_order_id' => $serviceOrderId,
                    'medical_service_id' => $medicalServiceId,
                ],
                [
                    'service_status_id' => $serviceStatusId,
                    'price_at_order' => $item['price_at_order'],
                    'started_at' => $item['started_at'],
                    'completed_at' => $item['completed_at'],
                    'performed_by_employee_id' => $employeeId,
                    'execution_notes' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
