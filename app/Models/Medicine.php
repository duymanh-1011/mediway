<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Medicine extends Model
{
    protected $fillable = [
        'medicine_code',
        'name',
        'active_ingredient',
        'strength',
        'dosage_form',
        'unit',
        'route_of_administration',
        'manufacturer',
        'country_of_origin',
        'description',
        'indications',
        'contraindications',
        'warnings',
        'common_side_effects',
        'storage_instructions',
        'status',
        'notes',
    ];
}
