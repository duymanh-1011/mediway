<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceOrder extends Model
{
    protected $table = 'service_orders';

    protected $fillable = [
        'service_order_code',
        'medical_visit_id',
        'ordered_by_doctor_id',
        'ordered_at',
        'reason',
        'instructions',
        'status',
        'order_pdf_path',
    ];

    protected $casts = [
        'ordered_at' => 'datetime',
    ];

    /**
     * Lượt khám của phiếu chỉ định.
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
     * Bác sĩ tạo phiếu chỉ định.
     *
     * ordered_by_doctor_id -> doctors.id
     */
    public function orderedByDoctor(): BelongsTo
    {
        return $this->belongsTo(
            Doctor::class,
            'ordered_by_doctor_id'
        );
    }

    /**
     * Các dịch vụ trong phiếu chỉ định.
     */
    public function items(): HasMany
    {
        return $this->hasMany(
            ServiceOrderItem::class,
            'service_order_id'
        );
    }
}
