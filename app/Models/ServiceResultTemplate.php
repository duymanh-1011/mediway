<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ServiceResultTemplate extends Model
{
    protected $fillable = ['name', 'description', 'fields', 'is_active', 'effective_from', 'effective_until'];

    protected $casts = [
        'fields' => 'array',
        'is_active' => 'boolean',
        'effective_from' => 'date',
        'effective_until' => 'date',
    ];

    public function medicalServices(): BelongsToMany
    {
        return $this->belongsToMany(
            MedicalService::class,
            'medical_service_result_templates',
            'service_result_template_id',
            'medical_service_id'
        )->withTimestamps();
    }

    public function versionFingerprint(): string
    {
        return hash('sha256', json_encode([
            $this->id,
            $this->name,
            $this->fields,
            $this->effective_from?->toDateString(),
            $this->effective_until?->toDateString(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }
}
