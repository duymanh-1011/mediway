<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiseaseGroup extends Model
{
    protected $fillable = [
        'disease_group_code',
        'name',
        'description',
        'status',
        'notes',
    ];
}
