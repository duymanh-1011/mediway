<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientDetail extends Model
{
    protected $table = 'patient_details';

    protected $fillable = [
        'patient_id',
        'citizen_id_number',
        'citizen_id_issue_date',
        'citizen_id_issue_place',
        'email',
        'hometown',
        'permanent_address',
        'current_address',
        'nationality',
        'ethnicity',
        'occupation',
        'blood_type',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
        'avatar_path',
        'citizen_id_front_path',
        'citizen_id_back_path',
        'profile_pdf_path',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'citizen_id_issue_date' => 'date',
        ];
    }

    /**
     * Bệnh nhân sở hữu hồ sơ chi tiết.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}