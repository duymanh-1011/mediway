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

    public function resultTemplates(): BelongsToMany
    {
        return $this->belongsToMany(
            ServiceResultTemplate::class,
            'medical_service_result_templates',
            'medical_service_id',
            'service_result_template_id'
        )->withTimestamps();
    }

    /** Return active templates assigned to this service and valid on the supplied date. */
    public function resultTemplatesAt(string $date): \Illuminate\Support\Collection
    {
        return $this->resultTemplates()
            ->where('service_result_templates.is_active', true)
            ->where(fn ($query) => $query->whereNull('service_result_templates.effective_from')
                ->orWhereDate('service_result_templates.effective_from', '<=', $date))
            ->where(fn ($query) => $query->whereNull('service_result_templates.effective_until')
                ->orWhereDate('service_result_templates.effective_until', '>=', $date))
            ->orderByDesc('service_result_templates.effective_from')
            ->get();
    }
}
