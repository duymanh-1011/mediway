<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            [
                'name' => 'UNPAID',
                'description' => 'Chưa thanh toán',
                'allows_service_execution' => false,
                'process_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'PENDING',
                'description' => 'Đang chờ xác nhận thanh toán',
                'allows_service_execution' => false,
                'process_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'PAID',
                'description' => 'Đã thanh toán',
                'allows_service_execution' => true,
                'process_order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'CANCELLED',
                'description' => 'Đã hủy khoản phải thu',
                'allows_service_execution' => false,
                'process_order' => 4,
                'is_active' => true,
            ],
        ];

        foreach ($statuses as $status) {
            DB::table('payment_statuses')->updateOrInsert(
                ['name' => $status['name']],
                array_merge($status, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
