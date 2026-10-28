<?php

namespace App\Models;

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
            ->whereHas('role', fn ($q) => $q->where('name', 'Doctor')->where('status', 'ACTIVE'))
            ->whereHas('doctor', fn ($q) => $q->where('status', 'ACTIVE'));
    }

    /** Must be called inside the service-result transaction, before saving uploads. */
    public static function lockResultVerifier(?int $id): void
    {
        if (! $id) {
            return;
        }
        $user = self::whereKey($id)->lockForUpdate()->first();
        $doctor = Doctor::where('user_id', $id)->lockForUpdate()->first();
        if (! $user || $user->status !== 'ACTIVE' || $doctor?->status !== 'ACTIVE'
            || $user->role?->name !== 'Doctor' || $user->role?->status !== 'ACTIVE') {
            throw ValidationException::withMessages(['verified_by_user_id' => 'Người xác nhận không còn đủ điều kiện hoạt động. Vui lòng chọn lại bác sĩ.']);
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
