<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    protected $fillable = [
        'user_id',
        'patient_code',
        'full_name',
        'date_of_birth',
        'gender',
        'status',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    public function appointments()
    {
        return $this->hasMany(
            Appointment::class,
            'patient_id'
        );
    }
}