<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payable extends Model
{
    protected $table = 'payables';

    protected $fillable = [
        'payable_code',
        'medical_visit_id',
        'service_order_item_id',
        'payment_type',
        'description',
        'amount',
        'payment_status_id',
        'due_at',
        'payment_qr_data',
        'generated_at',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_at' => 'datetime',
        'generated_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function paymentStatus(): BelongsTo
    {
        return $this->belongsTo(
            PaymentStatus::class,
            'payment_status_id'
        );
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(
            PaymentTransaction::class,
            'payable_id'
        );
    }
}
