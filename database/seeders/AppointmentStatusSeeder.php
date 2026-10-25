<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AppointmentStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['BOOKED',         'Đã đặt lịch, chưa check-in', 1, true,  true],
            ['CHECKED_IN',     'Đã check-in tại bệnh viện',  2, true,  true],
            ['IN_EXAMINATION', 'Đang khám',                   3, true,  true],
            ['COMPLETED',      'Đã hoàn thành lịch hẹn',      4, false, true],
            ['CANCELLED',      'Đã hủy',                      5, false, true],
        ];

        foreach ($statuses as [$name, $description, $order, $allowsTransition, $active]) {
            DB::table('appointment_statuses')->updateOrInsert(
                ['name' => $name],
                [
                    'description' => $description,
                    'process_order' => $order,
                    'allows_transition' => $allowsTransition,
                    'is_active' => $active,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
