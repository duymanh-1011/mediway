<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreVisitConclusionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'has_follow_up' => $this->boolean('has_follow_up'),
        ]);
    }

    public function rules(): array
    {
        return [
            'professional_conclusion' => [
                'required',
                'string',
                'max:10000',
            ],

            'disposition' => [
                'nullable',
                'string',
                'max:120',
            ],

            'treatment_plan' => [
                'nullable',
                'string',
                'max:10000',
            ],

            'instructions' => [
                'nullable',
                'string',
                'max:10000',
            ],

            'has_follow_up' => [
                'required',
                'boolean',
            ],

            'follow_up_date' => [
                'nullable',
                'date',
                'required_if:has_follow_up,1',
            ],

            'follow_up_reason' => [
                'nullable',
                'string',
                'max:5000',
                'required_if:has_follow_up,1',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'professional_conclusion.required' => 'Vui lòng nhập kết luận chuyên môn.',

            'professional_conclusion.string' => 'Vui lòng nhập kết luận chuyên môn bằng văn bản.',

            'disposition.max' => 'Hướng xử trí không được vượt quá 120 ký tự.',

            'follow_up_date.required_if' => 'Vui lòng chọn ngày tái khám.',

            'follow_up_date.date' => 'Ngày tái khám chưa hợp lệ. Vui lòng chọn lại ngày bằng ô lịch.',

            'follow_up_reason.required_if' => 'Vui lòng nhập lý do tái khám.',

            'follow_up_reason.max' => 'Lý do tái khám không được vượt quá :max ký tự.',

            'notes.max' => 'Ghi chú không được vượt quá :max ký tự.',
        ];
    }
}
