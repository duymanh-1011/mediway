<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    /**
     * Bảng audit_logs có created_at nhưng không có updated_at.
     */
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'role_name_snapshot',
        'action',
        'entity_type',
        'entity_id',
        'entity_business_code',
        'old_values',
        'new_values',
        'description',
        'ip_address',
        'user_agent',
        'performed_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'performed_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * Tài khoản thực hiện thao tác.
     *
     * user_id có thể NULL đối với thao tác hệ thống.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }
}
