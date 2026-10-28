<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PaymentTransactionSeeder extends Seeder
{
    public function run(): void
    {
        $transactions = [
            [
                'transaction_code' => 'GD000001',
                'payable_code' => 'PT000001',
                'amount' => 200000,
                'payment_method' => 'QR',
                'transaction_result' => 'SUCCESS',
                'employee_code' => null,
                'started_at' => '2026-10-15 07:55:00',
                'confirmed_at' => '2026-10-15 07:55:00',
            ],
            [
                'transaction_code' => 'GD000002',
                'payable_code' => 'PT000002',
                'amount' => 250000,
                'payment_method' => 'QR',
                'transaction_result' => 'SUCCESS',
                'employee_code' => null,
                'started_at' => '2026-10-15 07:55:00',
                'confirmed_at' => '2026-10-15 07:55:00',
            ],
            [
                'transaction_code' => 'GD000003',
                'payable_code' => 'PT000003',
                'amount' => 200000,
                'payment_method' => 'CASH',
                'transaction_result' => 'SUCCESS',
                'employee_code' => 'NV000004',
                'started_at' => '2026-10-15 07:55:00',
                'confirmed_at' => '2026-10-15 07:55:00',
            ],
            [
                'transaction_code' => 'GD000004',
                'payable_code' => 'PT000006',
                'amount' => 200000,
                'payment_method' => 'QR',
                'transaction_result' => 'SUCCESS',
                'employee_code' => null,
                'started_at' => '2026-10-15 08:35:00',
                'confirmed_at' => '2026-10-15 08:35:00',
            ],
            [
                'transaction_code' => 'GD000005',
                'payable_code' => 'PT000007',
                'amount' => 250000,
                'payment_method' => 'QR',
                'transaction_result' => 'SUCCESS',
                'employee_code' => null,
                'started_at' => '2026-10-15 08:35:00',
                'confirmed_at' => '2026-10-15 08:35:00',
            ],
            [
                'transaction_code' => 'GD000006',
                'payable_code' => 'PT000008',
                'amount' => 150000,
                'payment_method' => 'CASH',
                'transaction_result' => 'SUCCESS',
                'employee_code' => 'NV000004',
                'started_at' => '2026-10-15 08:35:00',
                'confirmed_at' => '2026-10-15 08:35:00',
            ],
            [
                'transaction_code' => 'GD000007',
                'payable_code' => 'PT000009',
                'amount' => 50000,
                'payment_method' => 'QR',
                'transaction_result' => 'SUCCESS',
                'employee_code' => null,
                'started_at' => '2026-10-15 08:35:00',
                'confirmed_at' => '2026-10-15 08:35:00',
            ],
            [
                'transaction_code' => 'GD000008',
                'payable_code' => 'PT000010',
                'amount' => 300000,
                'payment_method' => 'QR',
                'transaction_result' => 'SUCCESS',
                'employee_code' => null,
                'started_at' => '2026-10-15 08:35:00',
                'confirmed_at' => '2026-10-15 08:35:00',
            ],
            [
                'transaction_code' => 'GD000009',
                'payable_code' => 'PT000011',
                'amount' => 120000,
                'payment_method' => 'QR',
                'transaction_result' => 'FAILED',
                'employee_code' => null,
                'started_at' => '2026-10-15 08:48:00',
                'confirmed_at' => '2026-10-15 08:49:00',
            ],
        ];

        foreach ($transactions as $transaction) {
            $payableId = DB::table('payables')
                ->where('payable_code', $transaction['payable_code'])
                ->value('id');

            if (! $payableId) {
                throw new RuntimeException(
                    'Không tìm thấy payable '.
                        $transaction['payable_code']
                );
            }

            $employeeId = null;

            if ($transaction['employee_code'] !== null) {
                $employeeId = DB::table('employees')
                    ->where(
                        'employee_code',
                        $transaction['employee_code']
                    )
                    ->value('id');

                if (! $employeeId) {
                    throw new RuntimeException(
                        'Không tìm thấy employee '.
                            $transaction['employee_code']
                    );
                }
            }

            DB::table('payment_transactions')->updateOrInsert(
                [
                    'transaction_code' => $transaction['transaction_code'],
                ],
                [
                    'payable_id' => $payableId,
                    'amount' => $transaction['amount'],
                    'payment_method' => $transaction['payment_method'],
                    'transaction_result' => $transaction['transaction_result'],
                    'provider_transaction_code' => null,
                    'started_at' => $transaction['started_at'],

                    'paid_at' => $transaction['transaction_result'] === 'SUCCESS'
                        ? $transaction['confirmed_at']
                        : null,

                    'confirmed_at' => $transaction['confirmed_at'],
                    'collected_by_employee_id' => $employeeId,
                    'payment_content' => null,
                    'failure_reason' => null,
                    'notes' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
