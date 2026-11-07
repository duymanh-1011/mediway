<?php

namespace App\Http\Requests\Admin;

use App\Models\ClinicalDocument;
use App\Models\ServiceResult;
use App\Rules\ActiveResultVerifier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateServiceResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'result_summary' => [
                'nullable',
                'string',
            ],

            'result_conclusion' => [
                'nullable',
                'string',
            ],

            'result_parameters' => [
                'nullable',
                'json',
            ],

            'result_pdf' => ClinicalDocument::pdfRules(),
            'result_image' => ClinicalDocument::imageRules(),
            'result_pdf_path' => ['prohibited'],
            'result_image_path' => ['prohibited'],

            'verified_by_user_id' => [
                'nullable',
                'integer',
                new ActiveResultVerifier(ServiceResult::whereKey($this->route('id'))->value('verified_by_user_id')),
            ],

            'notes' => [
                'nullable',
                'string',
            ],

            'confirm_update' => [
                'accepted',
            ],
        ];
    }

    /**
     * @return array<\Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $result = ServiceResult::find($this->route('id'));
            $values = $this->only(['result_summary', 'result_conclusion']);
            $values['result_parameters'] = $result?->result_parameters;
            $values['result_pdf_path'] = $result?->result_pdf_path;
            $values['result_image_path'] = $result?->result_image_path;
            $hasUpload = collect(array_keys(ClinicalDocument::RESULT_FILES))->contains(
                fn (string $field): bool => $this->hasFile($field) && ! $validator->errors()->has($field)
            );
            if (! $hasUpload && ! ServiceResult::hasContentValues($values, $result?->service_order_item_id)) {
                $validator->errors()->add('result_summary', 'Kết quả phải có ít nhất nội dung tóm tắt, kết luận hoặc tệp PDF/ảnh đính kèm.');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'result_parameters.json' => 'Thông số kết quả phải là JSON hợp lệ.',

            'verified_by_user_id.integer' => 'Vui lòng chọn người xác nhận từ danh sách kỹ thuật viên.',

            'verified_by_user_id.exists' => 'Người xác nhận phải là kỹ thuật viên hợp lệ.',

            'confirm_update.accepted' => 'Vui lòng đánh dấu ô xác nhận nội dung kết quả trước khi cập nhật.',
        ];
    }
}
