<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Disease extends Model
{
    /** Common diagnoses appear first in the demo catalogue to make walkthroughs easier. */
    public const COMMON_DEMO_CODES = [
        'I10', 'E11.9', 'J00', 'J02.9', 'J18.9', 'J45.9', 'K21.9', 'K29.7',
        'N39.0', 'R50.9', 'J01.9', 'H60.9', 'I20.9', 'L20.9',
    ];

    protected $fillable = [
        'disease_code',
        'disease_group_id',
        'name',
        'alternative_name',
        'description',
        'common_symptoms',
        'professional_notes',
        'status',
    ];

    public function diseaseGroup()
    {
        return $this->belongsTo(
            DiseaseGroup::class,
            'disease_group_id'
        );
    }

    public function scopeCommonDemoFirst(Builder $query): Builder
    {
        $cases = [];
        foreach (self::COMMON_DEMO_CODES as $priority => $code) {
            $cases[] = 'WHEN ? THEN '.$priority;
        }

        return $query->orderByRaw(
            'CASE disease_code '.implode(' ', $cases).' ELSE '.count(self::COMMON_DEMO_CODES).' END',
            self::COMMON_DEMO_CODES
        );
    }
}
