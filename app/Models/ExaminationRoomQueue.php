<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExaminationRoomQueue extends Model
{
    protected $table = 'examination_room_queues';

    protected $fillable = [
        'medical_visit_id',
        'queue_status_id',
        'queue_number',
        'queue_type',
        'queued_at',
        'called_at',
        'service_started_at',
        'completed_at',
        'call_count',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'queued_at' => 'datetime',
            'called_at' => 'datetime',
            'service_started_at' => 'datetime',
            'completed_at' => 'datetime',
            'call_count' => 'integer',
        ];
    }

    public function medicalVisit(): BelongsTo
    {
        return $this->belongsTo(
            MedicalVisit::class,
            'medical_visit_id'
        );
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(
            QueueStatus::class,
            'queue_status_id'
        );
    }
}
