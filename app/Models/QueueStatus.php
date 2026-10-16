<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QueueStatus extends Model
{
    protected $table = 'queue_statuses';

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

    public function queues(): HasMany
    {
        return $this->hasMany(
            ExaminationRoomQueue::class,
            'queue_status_id'
        );
    }
}
