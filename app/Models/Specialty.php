<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Specialty extends Model
{
    protected $fillable = [
        'specialty_code',
        'name',
        'description',
        'image_path',
        'pre_visit_instructions',
        'status',
        'notes',
    ];
}