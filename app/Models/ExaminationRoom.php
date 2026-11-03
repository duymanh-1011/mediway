<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExaminationRoom extends Model
{
    protected $fillable = [
        'room_code',
        'specialty_id',
        'name',
        'room_number',
        'floor',
        'building',
        'location_instructions',
        'description',
        'status',
        'notes',
    ];
    public function specialty()
    {
        return $this->belongsTo(Specialty::class);
    }
}
