<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ServiceOrderItem extends Model
{
    protected $table = 'service_order_items';

    protected $fillable = [
        'service_order_id',
        'medical_service_id',
        'service_status_id',
        'price_at_order',
        'started_at',
        'completed_at',
        'performed_by_employee_id',
        'execution_notes',
    ];

    protected $casts = [
        'price_at_order' => 'decimal:2',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Phiếu chỉ định chứa dịch vụ này.
     */
    public function serviceOrder(): BelongsTo
    {
        return $this->belongsTo(
            ServiceOrder::class,
            'service_order_id'
        );
    }

    /**
     * Dịch vụ y tế.
     *
     * medical_service_id -> medical_services.id
     */
    public function medicalService(): BelongsTo
    {
        return $this->belongsTo(
            MedicalService::class,
            'medical_service_id'
        );
    }

    /**
     * Trạng thái thực hiện dịch vụ.
     */
    public function serviceStatus(): BelongsTo
    {
        return $this->belongsTo(
            ServiceStatus::class,
            'service_status_id'
        );
    }

    /**
     * Nhân viên thực hiện dịch vụ.
     *
     * performed_by_employee_id có thể NULL.
     */
    public function performer(): BelongsTo
    {
        return $this->belongsTo(
            Employee::class,
            'performed_by_employee_id'
        );
    }

    /**
     * Kết quả của dịch vụ.
     *
     * service_results.service_order_item_id là UNIQUE.
     */
    public function result(): HasOne
    {
        return $this->hasOne(
            ServiceResult::class,
            'service_order_item_id'
        );
    }

    /**
     * Khoản phải thanh toán của dịch vụ.
     *
     * payables.service_order_item_id là UNIQUE và có thể NULL.
     */
    public function payable(): HasOne
    {
        return $this->hasOne(
            Payable::class,
            'service_order_item_id'
        );
    }
}
