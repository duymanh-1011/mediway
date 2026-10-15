<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prescription extends Model
{
    protected $table = 'prescriptions';

    protected $fillable = [
        'prescription_code',
        'medical_visit_id',
        'doctor_id',
        'prescribed_at',
        'general_instructions',
        'doctor_notes',
        'status',
        'prescription_pdf_path',
    ];

    protected $casts = [
        'prescribed_at' => 'datetime',
    ];

    /**
     * Các thuốc nằm trong đơn.
     *
     * Chỉ quan hệ với bảng thuộc phần của Tạo,
     * không phụ thuộc model của thành viên khác.
     */
    public function items(): HasMany
    {
        return $this->hasMany(
            PrescriptionItem::class,
            'prescription_id'
        );
    }
}
