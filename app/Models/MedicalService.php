<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalService extends Model
{
    protected $fillable = [
        'service_code',
        'service_group_id',
        'name',
        'description',
        'price',
        'unit',
        'estimated_duration_minutes',
        'preparation_instructions',
        'precautions',
        'display_order',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'estimated_duration_minutes' => 'integer',
        'display_order' => 'integer',
    ];

    public function serviceGroup()
    {
        return $this->belongsTo(
            ServiceGroup::class,
            'service_group_id'
        );
    }
}