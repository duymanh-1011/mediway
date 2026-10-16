<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeDetail extends Model
{
    protected $table = 'employee_details';

    protected $fillable = [
        'employee_id',
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
        'department',
        'position',
        'employee_type',
        'hire_date',
        'termination_date',
        'years_of_service',
        'primary_shift',
        'work_location',
        'job_description',
        'professional_skills',
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
            'hire_date' => 'date',
            'termination_date' => 'date',
            'years_of_service' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
