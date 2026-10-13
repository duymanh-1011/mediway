<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExaminationRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $examinationRoom =
            $this->route('examination_room');

        return [
            'room_code' => [
                'required',
                'string',
                'max:30',

                Rule::unique(
                    'examination_rooms',
                    'room_code'
                )->ignore($examinationRoom->id),
            ],

            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'room_number' => [
                'required',
                'string',
                'max:50',
            ],

            'floor' => [
                'nullable',
                'string',
                'max:50',
            ],

            'building' => [
                'nullable',
                'string',
                'max:150',
            ],

            'location_instructions' => [
                'nullable',
                'string',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'status' => [
                'required',
                'in:ACTIVE,INACTIVE',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'room_code.required' =>
                'Vui lòng nhập mã phòng khám.',

            'room_code.max' =>
                'Mã phòng khám không được vượt quá 30 ký tự.',

            'room_code.unique' =>
                'Mã phòng khám đã tồn tại.',

            'name.required' =>
                'Vui lòng nhập tên phòng khám.',

            'name.max' =>
                'Tên phòng khám không được vượt quá 150 ký tự.',

            'room_number.required' =>
                'Vui lòng nhập số phòng.',

            'room_number.max' =>
                'Số phòng không được vượt quá 50 ký tự.',

            'floor.max' =>
                'Thông tin tầng không được vượt quá 50 ký tự.',

            'building.max' =>
                'Tên tòa nhà không được vượt quá 150 ký tự.',

            'status.required' =>
                'Vui lòng chọn trạng thái.',

            'status.in' =>
                'Trạng thái phòng khám không hợp lệ.',
        ];
    }
}