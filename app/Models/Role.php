<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $table = 'roles';

    protected $fillable = [
        'name',
        'description',
        'status',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function isSystemRole(): bool
    {
        return in_array($this->name, [
            'Administrator',
            'Doctor',
            'Employee',
            'Patient',
        ], true);
    }
}
