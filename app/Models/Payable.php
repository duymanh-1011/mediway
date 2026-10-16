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

    /**
     * Lượt khám phát sinh khoản phải thanh toán.
     *
     * medical_visit_id -> medical_visits.id
     */
    public function medicalVisit(): BelongsTo
    {
        return $this->belongsTo(
            MedicalVisit::class,
            'medical_visit_id'
        );
    }

    /**
     * Dịch vụ phát sinh khoản phải thanh toán.
     *
     * Có thể NULL đối với EXAMINATION_FEE.
     */
    public function serviceOrderItem(): BelongsTo
    {
        return $this->belongsTo(
            ServiceOrderItem::class,
            'service_order_item_id'
        );
    }

    /**
     * Trạng thái thanh toán.
     */
    public function paymentStatus(): BelongsTo
    {
        return $this->belongsTo(
            PaymentStatus::class,
            'payment_status_id'
        );
    }

    /**
     * Các giao dịch thanh toán của khoản phải thu.
     *
     * Một payable có thể có nhiều transaction,
     * bao gồm cả giao dịch FAILED rồi retry.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(
            PaymentTransaction::class,
            'payable_id'
        );
    }
}
