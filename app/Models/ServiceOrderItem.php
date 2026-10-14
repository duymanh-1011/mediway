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
     * Chi tiết thuộc phiếu chỉ định nào.
     */
    public function serviceOrder(): BelongsTo
    {
        return $this->belongsTo(
            ServiceOrder::class,
            'service_order_id'
        );
    }

    /**
     * Dịch vụ y tế được chỉ định.
     *
     * Nếu model MedicalService của thành viên khác đã tồn tại
     * thì tự động dùng model thật.
     */
    public function medicalService(): BelongsTo
    {
        $relatedModel = class_exists('App\\Models\\MedicalService')
            ? 'App\\Models\\MedicalService'
            : ServiceOrderMedicalServiceReference::class;

        return $this->belongsTo(
            $relatedModel,
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
     * Có thể NULL.
     * Nếu model Employee đã được merge thì tự động dùng model thật.
     */
    public function performer(): BelongsTo
    {
        $relatedModel = class_exists('App\\Models\\Employee')
            ? 'App\\Models\\Employee'
            : ServiceOrderEmployeeReference::class;

        return $this->belongsTo(
            $relatedModel,
            'performed_by_employee_id'
        );
    }

    /**
     * Một chi tiết chỉ định có tối đa một kết quả.
     */
    public function result(): HasOne
    {
        return $this->hasOne(
            ServiceResult::class,
            'service_order_item_id'
        );
    }
}


/*
|--------------------------------------------------------------------------
| MODEL THAM CHIẾU TẠM
|--------------------------------------------------------------------------
|
| Chỉ dùng để đọc dữ liệu trước khi các model của thành viên khác được merge.
|
*/

class ServiceOrderMedicalServiceReference extends Model
{
    protected $table = 'medical_services';

    public $timestamps = false;

    protected $guarded = [];
}


class ServiceOrderEmployeeReference extends Model
{
    protected $table = 'employees';

    public $timestamps = false;

    protected $guarded = [];
}
