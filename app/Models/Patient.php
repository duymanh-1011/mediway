<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Patient extends Model
{
    protected $table = 'patients';

    protected $fillable = [
        'user_id',
        'patient_code',
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

    /**
     * Tài khoản đăng nhập của bệnh nhân.
     * Một số bệnh nhân có thể chưa có tài khoản.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Hồ sơ thông tin chi tiết.
     */
    public function detail(): HasOne
    {
        return $this->hasOne(PatientDetail::class);
    }
}