<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function medicalServices()
    {
        return $this->hasMany(
            MedicalService::class,
            'service_group_id'
        );
    }
}