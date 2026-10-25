<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Administrator extends Model
{
    protected $fillable = [
        'user_id',
        'full_name',
        'email',
        'date_of_birth',
        'gender',
        'avatar_path',
        'address',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
