<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorDetail extends Model
{
    protected $table = 'doctor_details';

    protected $fillable = [
        'doctor_id',
        'citizen_id_number',
        'citizen_id_issue_date',
        'citizen_id_issue_place',
        'email',
        'hometown',
        'permanent_address',
        'current_address',
        'nationality',
        'ethnicity',
        'education_level',
        'professional_qualification',
        'academic_title_degree',
        'professional_title',
        'current_position',
        'current_workplace',
        'work_start_date',
        'years_of_experience',
        'specialized_fields',
        'professional_skills',
        'languages',
        'practice_license_number',
        'practice_license_issue_date',
        'practice_license_issue_place',
        'practice_license_expiry_date',
        'practice_scope',
        'professional_bio',
        'avatar_path',
        'citizen_id_front_path',
        'citizen_id_back_path',
        'practice_license_file_path',
        'profile_pdf_path',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'citizen_id_issue_date' => 'date',
            'work_start_date' => 'date',
            'practice_license_issue_date' => 'date',
            'practice_license_expiry_date' => 'date',
        ];
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}
