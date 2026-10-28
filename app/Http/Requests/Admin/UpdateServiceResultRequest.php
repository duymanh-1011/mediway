<?php

namespace App\Http\Requests\Admin;

use App\Models\ClinicalDocument;
use App\Models\ClinicalTimeline;
use App\Models\ServiceResult;
use App\Rules\ActiveResultVerifier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateServiceResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /*
        |--------------------------------------------------------------------------
        | Lấy role Doctor
        |--------------------------------------------------------------------------
        */
        $doctorRoleId = DB::table('roles')
            ->where('name', 'Doctor')
            ->value('id');

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
                new ActiveResultVerifier,

                Rule::exists('users', 'id')
                    ->where(function ($query) use ($doctorRoleId) {
                        $query->where(
                            'role_id',
                            $doctorRoleId
                        );
                    }),
            ],

            'resulted_at' => [
                'nullable',
                'date',
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
            if (! $validator->errors()->has('resulted_at')) {
                ClinicalTimeline::assertEventTime($this->input('resulted_at'), $result?->serviceOrderItem?->started_at, 'resulted_at', 'Thời điểm trả kết quả');
            }
            $values = $this->only(['result_summary', 'result_conclusion']);
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

            'verified_by_user_id.integer' => 'Vui lòng chọn người xác nhận từ danh sách bác sĩ.',

            'verified_by_user_id.exists' => 'Người xác nhận phải là bác sĩ hợp lệ.',

            'resulted_at.date' => 'Thời điểm trả kết quả chưa hợp lệ. Vui lòng chọn đủ ngày và giờ.',

            'confirm_update.accepted' => 'Vui lòng đánh dấu ô xác nhận nội dung kết quả trước khi cập nhật.',
        ];
    }
}
