<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentStatus extends Model
{
    protected $fillable = [
        'description',
        'process_order',
        'allows_transition',
        'is_active',
    ];

    protected $casts = [
        'process_order' => 'integer',
        'allows_transition' => 'boolean',
        'is_active' => 'boolean',
    ];
}