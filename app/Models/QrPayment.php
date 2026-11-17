<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QrPayment extends Model
{
    public const BLOCKING = ['CREATING', 'PENDING', 'REVIEW'];

    protected $fillable = [
        'medical_visit_id', 'created_by_employee_id', 'provider', 'order_code', 'status',
        'amount', 'provider_link_id', 'provider_reference', 'qr_payload', 'expires_at', 'paid_at',
        'review_reference', 'review_amount', 'review_reason',
    ];

    protected $casts = [
        'amount' => 'integer',
        'order_code' => 'integer',
        'review_amount' => 'integer',
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function medicalVisit(): BelongsTo
    {
        return $this->belongsTo(MedicalVisit::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by_employee_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QrPaymentItem::class);
    }

    public function scopeBlocking(Builder $query): Builder
    {
        return $query->whereIn('status', self::BLOCKING);
    }

    public static function blocks(Payable $payable): bool
    {
        return self::supports($payable) && self::blocking()->where('medical_visit_id', $payable->medical_visit_id)
            ->whereHas('items', fn (Builder $items) => $items->where('payable_id', $payable->id))
            ->lockForUpdate()->first(['id']) !== null;
    }

    public static function supports(Payable $payable): bool
    {
        return in_array($payable->payment_type, ['EXAMINATION_FEE', 'SERVICE_FEE'], true);
    }

    public function displayStatus(): string
    {
        return match ($this->status) {
            'CREATING' => 'Chưa nhận được mã QR',
            'PENDING' => $this->expires_at->isPast() ? 'Mã QR đã hết hạn' : 'Chờ thanh toán',
            'PAID' => 'Đã thanh toán',
            'CANCELLED' => 'Đã hủy mã QR',
            'REVIEW' => 'Cần đối soát',
        };
    }
}
