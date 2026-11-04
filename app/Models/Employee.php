<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;

class Employee extends Model
{
    public function scopeActiveCashiers(Builder $query): void
    {
        $query->where('status', 'ACTIVE')->whereHas('detail', fn (Builder $detail) => $detail->where('employee_type', 'CASHIER'));
    }

    protected $table = 'employees';

    protected $fillable = [
        'user_id',
        'employee_code',
        'full_name',
        'date_of_birth',
        'gender',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function detail(): HasOne
    {
        return $this->hasOne(EmployeeDetail::class);
    }
}
