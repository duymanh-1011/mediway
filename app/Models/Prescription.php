<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function hasPdf(): bool
    {
        return app(ClinicalDocument::class)->exists($this->prescription_pdf_path, 'prescriptions/'.$this->id, 'pdf');
    }

    /**
     * Lượt khám của đơn thuốc.
     *
     * medical_visit_id -> medical_visits.id
     */
    public function medicalVisit(): BelongsTo
    {
        return $this->belongsTo(
            MedicalVisit::class,
            'medical_visit_id'
        );
    }

    /**
     * Bác sĩ kê đơn.
     *
     * doctor_id -> doctors.id
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(
            Doctor::class,
            'doctor_id'
        );
    }

    /**
     * Các thuốc trong đơn.
     */
    public function items(): HasMany
    {
        return $this->hasMany(
            PrescriptionItem::class,
            'prescription_id'
        );
    }
    public function payable(): HasOne
    {
        return $this->hasOne(Payable::class)->latestOfMany();
    }

    public function pricedTotal(): ?string
    {
        $items = $this->items;
        if ($items->isEmpty() || $items->contains(fn ($item) => $item->line_total === null)) return null;
        $cents = $items->sum(fn ($item) => (int) str_replace('.', '', $item->line_total));
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
