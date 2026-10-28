<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ServiceGroup extends Model
{
    protected $fillable = [
        'service_group_code',
        'name',
        'description',
        'display_order',
        'status',
        'notes',
    ];

    protected $casts = [
        'display_order' => 'integer',
    ];

    /**
     * Các dịch vụ thuộc nhóm này.
     */
    public function medicalServices(): BelongsToMany
    {
        return $this->belongsToMany(
            MedicalService::class,
            'service_group_medical_service',
            'service_group_id',
            'medical_service_id'
        )->withTimestamps();
    }
}
