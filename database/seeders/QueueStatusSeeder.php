<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QueueStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['name' => 'WAITING', 'description' => 'Đang chờ', 'process_order' => 1, 'allows_transition' => true, 'is_active' => true],
            ['name' => 'CALLED', 'description' => 'Đã được gọi', 'process_order' => 2, 'allows_transition' => true, 'is_active' => true],
            ['name' => 'IN_SERVICE', 'description' => 'Đang phục vụ', 'process_order' => 3, 'allows_transition' => true, 'is_active' => true],
            ['name' => 'COMPLETED', 'description' => 'Đã hoàn thành lượt chờ', 'process_order' => 4, 'allows_transition' => false, 'is_active' => true],
            ['name' => 'SKIPPED', 'description' => 'Bỏ qua/vắng mặt', 'process_order' => 5, 'allows_transition' => true, 'is_active' => true],
        ];

        foreach ($statuses as $status) {
            DB::table('queue_statuses')->updateOrInsert(
                ['name' => $status['name']],
                [
                    'description' => $status['description'],
                    'process_order' => $status['process_order'],
                    'allows_transition' => $status['allows_transition'],
                    'is_active' => $status['is_active'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
