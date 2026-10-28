<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VisitStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['name' => 'WAITING', 'description' => 'Đang chờ vào khám', 'process_order' => 1, 'allows_transition' => true, 'is_active' => true],
            ['name' => 'IN_EXAMINATION', 'description' => 'Đang được bác sĩ khám', 'process_order' => 2, 'allows_transition' => true, 'is_active' => true],
            ['name' => 'WAITING_FOR_SERVICE', 'description' => 'Đang chờ thực hiện dịch vụ', 'process_order' => 3, 'allows_transition' => true, 'is_active' => true],
            ['name' => 'RETURNING', 'description' => 'Quay lại bác sĩ sau dịch vụ', 'process_order' => 4, 'allows_transition' => true, 'is_active' => true],
            ['name' => 'COMPLETED', 'description' => 'Đã hoàn tất lượt khám', 'process_order' => 5, 'allows_transition' => false, 'is_active' => true],
        ];

        foreach ($statuses as $status) {
            DB::table('visit_statuses')->updateOrInsert(
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
