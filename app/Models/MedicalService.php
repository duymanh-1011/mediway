<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedicalService extends Model
{
    protected $fillable = [
        'service_code',

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

    /**
     * Các nhóm dịch vụ mà dịch vụ này thuộc về.
     */
    public function serviceGroups(): BelongsToMany
    {
        return $this->belongsToMany(
            ServiceGroup::class,
            'service_group_medical_service',
            'medical_service_id',
            'service_group_id'
        )->withTimestamps();
    }

    /**
     * Các lần dịch vụ được chỉ định.
     */
    public function serviceOrderItems(): HasMany
    {
        return $this->hasMany(
            ServiceOrderItem::class,
            'medical_service_id'
        );
    }
}
