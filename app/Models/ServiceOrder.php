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
     * Phiếu chỉ định thuộc một lượt khám.
     *
     * Nếu model MedicalVisit của thành viên khác đã tồn tại
     * thì tự động sử dụng model đó.
     * Nếu chưa có thì dùng model tham chiếu tạm bên dưới.
     */
    public function medicalVisit(): BelongsTo
    {
        $relatedModel = class_exists('App\\Models\\MedicalVisit')
            ? 'App\\Models\\MedicalVisit'
            : ServiceOrderMedicalVisitReference::class;

        return $this->belongsTo(
            $relatedModel,
            'medical_visit_id'
        );
    }

    /**
     * Bác sĩ tạo phiếu chỉ định.
     *
     * Nếu model Doctor đã được merge thì tự dùng model thật.
     */
    public function orderedByDoctor(): BelongsTo
    {
        $relatedModel = class_exists('App\\Models\\Doctor')
            ? 'App\\Models\\Doctor'
            : ServiceOrderDoctorReference::class;

        return $this->belongsTo(
            $relatedModel,
            'ordered_by_doctor_id'
        );
    }

    /**
     * Các dịch vụ nằm trong phiếu chỉ định.
     */
    public function items(): HasMany
    {
        return $this->hasMany(
            ServiceOrderItem::class,
            'service_order_id'
        );
    }
}


/*
|--------------------------------------------------------------------------
| MODEL THAM CHIẾU TẠM
|--------------------------------------------------------------------------
|
| Hai class dưới đây chỉ dùng để ĐỌC dữ liệu khi model của thành viên khác
| chưa được merge vào branch.
|
| Khi App\Models\MedicalVisit hoặc App\Models\Doctor tồn tại,
| ServiceOrder sẽ tự động dùng model thật và bỏ qua các class này.
|
*/

class ServiceOrderMedicalVisitReference extends Model
{
    protected $table = 'medical_visits';

    public $timestamps = false;

    protected $guarded = [];
}


class ServiceOrderDoctorReference extends Model
{
    protected $table = 'doctors';

    public $timestamps = false;

    protected $guarded = [];
}
