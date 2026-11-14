<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceResultTemplate extends Model
{
    protected $fillable = ['name', 'description', 'fields', 'is_active'];

    protected $casts = ['fields' => 'array', 'is_active' => 'boolean'];

    public function medicalServices(): HasMany
    {
        return $this->hasMany(MedicalService::class, 'service_result_template_id');
    }
}
