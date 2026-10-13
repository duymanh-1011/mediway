<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentStatus extends Model
{
    protected $table = 'payment_statuses';

    protected $fillable = [
        'description',
        'allows_service_execution',
        'process_order',
        'is_active',
    ];

    protected $casts = [
        'allows_service_execution' => 'boolean',
        'process_order' => 'integer',
        'is_active' => 'boolean',
    ];
}
