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
     * Chi tiết thuốc thuộc đơn thuốc nào.
     */
    public function prescription(): BelongsTo
    {
        return $this->belongsTo(
            Prescription::class,
            'prescription_id'
        );
    }
}
