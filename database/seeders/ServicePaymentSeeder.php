<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ServicePaymentSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ServiceStatusSeeder::class,
            PaymentStatusSeeder::class,
            ServiceOrderSeeder::class,
            ServiceOrderItemSeeder::class,
            ServiceResultSeeder::class,
            PrescriptionSeeder::class,
            PrescriptionItemSeeder::class,
            PayableSeeder::class,
            PaymentTransactionSeeder::class,
            AuditLogSeeder::class,
        ]);
    }
}
