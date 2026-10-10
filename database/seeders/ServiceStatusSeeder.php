<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            [
                'name' => 'PENDING',
                'description' => 'Chờ thanh toán/chờ thực hiện',
                'process_order' => 1,
                'allows_transition' => true,
                'is_active' => true,
            ],
            [
                'name' => 'PROCESSING',
                'description' => 'Đang thực hiện',
                'process_order' => 2,
                'allows_transition' => true,
                'is_active' => true,
            ],
            [
                'name' => 'COMPLETED',
                'description' => 'Đã hoàn thành',
                'process_order' => 3,
                'allows_transition' => false,
                'is_active' => true,
            ],
            [
                'name' => 'CANCELLED',
                'description' => 'Đã hủy',
                'process_order' => 4,
                'allows_transition' => false,
                'is_active' => true,
            ],
        ];

        foreach ($statuses as $status) {
            DB::table('service_statuses')->updateOrInsert(
                ['name' => $status['name']],
                array_merge($status, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
