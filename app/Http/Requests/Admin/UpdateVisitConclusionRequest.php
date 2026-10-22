<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVisitConclusionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'doctor_id' => [
                'required',
                'integer',
                Rule::exists(
                    'doctors',
                    'id'
                ),
            ],

            'professional_conclusion' => [
                'required',
                'string',
                'max:5000',
            ],

            'disposition' => [
                'nullable',
                'string',
                'max:120',
            ],

            'treatment_plan' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'instructions' => [
                'nullable',
                'string',
                'max:5000',
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
                'max:2000',
                'required_if:has_follow_up,1',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'concluded_at' => [
                'nullable',
                'date',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'doctor_id.required' => 'Vui lòng chọn bác sĩ kết luận.',

            'doctor_id.exists' => 'Bác sĩ được chọn không tồn tại.',

            'professional_conclusion.required' => 'Vui lòng nhập kết luận chuyên môn.',

            'professional_conclusion.max' => 'Kết luận chuyên môn không được vượt quá 5000 ký tự.',

            'disposition.max' => 'Hướng xử trí không được vượt quá 120 ký tự.',

            'treatment_plan.max' => 'Kế hoạch điều trị không được vượt quá 5000 ký tự.',

            'instructions.max' => 'Dặn dò không được vượt quá 5000 ký tự.',

            'has_follow_up.required' => 'Vui lòng chọn có tái khám hay không.',

            'has_follow_up.boolean' => 'Giá trị tái khám không hợp lệ.',

            'follow_up_date.required_if' => 'Vui lòng chọn ngày tái khám.',

            'follow_up_date.date' => 'Ngày tái khám không hợp lệ.',

            'follow_up_reason.required_if' => 'Vui lòng nhập lý do tái khám.',

            'follow_up_reason.max' => 'Lý do tái khám không được vượt quá 2000 ký tự.',

            'notes.max' => 'Ghi chú không được vượt quá 5000 ký tự.',

            'concluded_at.date' => 'Thời điểm kết luận không hợp lệ.',
        ];
    }
}
