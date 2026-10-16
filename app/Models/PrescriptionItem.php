<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrescriptionItem extends Model
{
    protected $table = 'prescription_items';

    protected $fillable = [
        'prescription_id',
        'medicine_id',
        'quantity',
        'usage_unit',
        'dose_per_use',
        'times_per_day',
        'duration_days',
        'administration_time',
        'usage_method',
        'usage_instructions',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'times_per_day' => 'decimal:2',
        'duration_days' => 'integer',
    ];

    /**
     * Đơn thuốc chứa thuốc này.
     */
    public function prescription(): BelongsTo
    {
        return $this->belongsTo(
            Prescription::class,
            'prescription_id'
        );
    }

    /**
     * Thuốc.
     *
     * medicine_id -> medicines.id
     */
    public function medicine(): BelongsTo
    {
        return $this->belongsTo(
            Medicine::class,
            'medicine_id'
        );
    }
}
