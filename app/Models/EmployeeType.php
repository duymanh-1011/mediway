<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeType extends Model
{
    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['code', 'name', 'display_order'];

    public function employeeDetails(): HasMany
    {
        return $this->hasMany(EmployeeDetail::class, 'employee_type', 'code');
    }
}
