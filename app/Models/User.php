<?php

namespace App\Models;

use App\Support\EmployeeAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Validation\ValidationException;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'business_code',
        'phone',
        'password',
        'must_change_password',
        'role_id',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'must_change_password' => 'boolean',
        ];
    }

    public function canAccessAdmin(): bool
    {
        $this->load(['role', 'administrator']);

        return $this->status === 'ACTIVE'
            && $this->role?->name === 'Administrator'
            && $this->role?->status === 'ACTIVE'
            && $this->administrator?->status === 'ACTIVE';
    }

    public function canAccessPortal(): bool
    {
        $this->loadMissing(['role', 'patient']);

        return $this->status === 'ACTIVE'
            && $this->role?->name === 'Patient'
            && $this->role?->status === 'ACTIVE'
            && $this->patient?->status === 'ACTIVE';
    }

    public function scopeEligibleResultVerifier(Builder $query): Builder
    {
        return $query->where('status', 'ACTIVE')
            ->whereHas('role', fn ($q) => $q->where('name', 'Employee')->where('status', 'ACTIVE'))
            ->whereHas('employee', fn ($q) => $q->where('status', 'ACTIVE')
                ->whereHas('detail', fn ($detail) => $detail->whereIn('employee_type', EmployeeAccess::TECHNICIAN_CODES)));
    }

    /** Must be called inside the service-result transaction, before saving uploads. */
    public static function lockResultVerifier(?int $id): void
    {
        if (! $id) {
            return;
        }
        $user = self::whereKey($id)->lockForUpdate()->first();
        $employee = Employee::where('user_id', $id)->lockForUpdate()->first();
        $detail = $employee?->detail()->lockForUpdate()->first();
        if (! $user || $user->status !== 'ACTIVE' || $employee?->status !== 'ACTIVE'
            || $user->role?->name !== 'Employee' || $user->role?->status !== 'ACTIVE'
            || ! in_array($detail?->employee_type, EmployeeAccess::TECHNICIAN_CODES, true)) {
            throw ValidationException::withMessages(['verified_by_user_id' => 'Vui lòng chọn kỹ thuật viên đang hoạt động.']);
        }
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function administrator()
    {
        return $this->hasOne(Administrator::class);
    }

    public function patient()
    {
        return $this->hasOne(Patient::class);
    }

    public function doctor()
    {
        return $this->hasOne(Doctor::class);
    }

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }
}
