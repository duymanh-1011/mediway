<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    protected $table = 'payment_transactions';

    protected $fillable = [
        'transaction_code',
        'payable_id',
        'amount',
        'payment_method',
        'transaction_result',
        'provider_transaction_code',
        'started_at',
        'paid_at',
        'confirmed_at',
        'collected_by_employee_id',
        'payment_content',
        'failure_reason',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'started_at' => 'datetime',
        'paid_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public function payable(): BelongsTo
    {
        return $this->belongsTo(
            Payable::class,
            'payable_id'
        );
    }
}
