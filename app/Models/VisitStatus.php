<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VisitStatus extends Model
{
    protected $table = 'visit_statuses';

    protected $fillable = [
        'name',
        'description',
        'process_order',
        'allows_transition',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'process_order' => 'integer',
            'allows_transition' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function medicalVisits(): HasMany
    {
        return $this->hasMany(
            MedicalVisit::class,
            'visit_status_id'
        );
    }
}
