<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceResult extends Model
{
    protected $table = 'service_results';

    protected $fillable = [
        'service_order_item_id',
        'result_summary',
        'result_conclusion',
        'result_parameters',
        'result_pdf_path',
        'result_image_path',
        'verified_by_user_id',
        'resulted_at',
        'notes',
    ];

    protected $casts = [
        'result_parameters' => 'array',
        'resulted_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Quan hệ
    |--------------------------------------------------------------------------
    */

    /**
     * Kết quả thuộc chi tiết chỉ định nào.
     *
     * service_order_item_id là FK + UNIQUE,
     * nên mỗi item chỉ có tối đa một kết quả.
     */
    public function serviceOrderItem(): BelongsTo
    {
        return $this->belongsTo(
            ServiceOrderItem::class,
            'service_order_item_id'
        );
    }

    /**
     * Người xác nhận kết quả.
     *
     * Có thể NULL.
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'verified_by_user_id'
        );
    }
}
