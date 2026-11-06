<?php

namespace App\Support\Payments;

use App\Models\Payable;

class ReceiptTypeResolver
{
    public function resolve(Payable $payable): ?string
    {
        return match ($payable->payment_type) {
            'EXAMINATION_FEE' => 'exam',
            'SERVICE_FEE' => 'service',
            'MEDICINE_FEE' => 'prescription',
            default => null,
        };
    }
}
