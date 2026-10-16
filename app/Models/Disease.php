<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Disease extends Model
{
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
}