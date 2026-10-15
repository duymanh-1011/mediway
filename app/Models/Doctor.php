<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    protected $fillable = [
        'user_id',
        'doctor_code',
        'full_name',
        'date_of_birth',
        'gender',
        'status',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    public function schedules()
    {
        return $this->hasMany(
            DoctorSchedule::class,
            'doctor_id'
        );
    }
}