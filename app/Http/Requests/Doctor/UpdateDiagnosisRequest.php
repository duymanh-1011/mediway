<?php

namespace App\Http\Requests\Doctor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDiagnosisRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'disease_id' => ['required', 'integer', Rule::exists('diseases', 'id')],
            'diagnosis_type' => ['required', Rule::in(['PRIMARY', 'SECONDARY', 'PRELIMINARY', 'CONFIRMED'])],
            'severity' => ['nullable', 'string', 'max:40'],
            'diagnosis_description' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'record_version' => ['required', 'string', 'size:64'],
        ];
    }
}
