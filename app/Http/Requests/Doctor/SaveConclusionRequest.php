<?php

namespace App\Http\Requests\Doctor;

use App\Http\Requests\Admin\StoreVisitConclusionRequest;
use Illuminate\Validation\Rule;

class SaveConclusionRequest extends StoreVisitConclusionRequest
{
    public function rules(): array
    {
        // Khi chọn tái khám, bắt buộc có ngày và lý do dù giá trị đã được chuyển sang boolean.
        return [...parent::rules(),
            'follow_up_date' => ['nullable', 'date', Rule::requiredIf(fn () => $this->boolean('has_follow_up'))],
            'follow_up_reason' => ['nullable', 'string', 'max:5000', Rule::requiredIf(fn () => $this->boolean('has_follow_up'))],
        ];
    }

    public function messages(): array
    {
        return [...parent::messages(),
            'follow_up_date.required' => 'Vui lòng chọn ngày tái khám.',
            'follow_up_reason.required' => 'Vui lòng nhập lý do tái khám.',
        ];
    }
}
