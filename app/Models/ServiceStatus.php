<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceStatus extends Model
{
    protected $table = 'service_statuses';

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
