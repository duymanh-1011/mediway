<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ValidatesDoctorSpecialty;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDoctorScheduleRequest extends FormRequest
{
    use ValidatesDoctorSpecialty;

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
                Rule::exists('doctors', 'id')->where(fn (Builder $query): Builder => $this->input('status') === 'ACTIVE' ? $query->where('status', 'ACTIVE') : $query),
            ],

            'specialty_id' => [
                'required',
                'integer',
                Rule::exists('specialties', 'id')->where(fn (Builder $query): Builder => $this->input('status') === 'ACTIVE' ? $query->where('status', 'ACTIVE') : $query),
            ],

            'examination_room_id' => [
                'required',
                'integer',
                Rule::exists('examination_rooms', 'id')->where(fn (Builder $query): Builder => $this->input('status') === 'ACTIVE' ? $query->where('status', 'ACTIVE') : $query),
            ],

            'work_date' => [
                'required',
                'date',
            ],

            'start_time' => [
                'required',
                'date_format:H:i',
            ],

            'end_time' => [
                'required',
                'date_format:H:i',
                'after:start_time',
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
            'doctor_id.required' => 'Vui lòng chọn bác sĩ.',

            'doctor_id.exists' => 'Bác sĩ được chọn không tồn tại hoặc đã ngừng hoạt động.',

            'specialty_id.required' => 'Vui lòng chọn chuyên khoa.',

            'specialty_id.exists' => 'Chuyên khoa được chọn không tồn tại hoặc đã ngừng hoạt động.',

            'examination_room_id.required' => 'Vui lòng chọn phòng khám.',

            'examination_room_id.exists' => 'Phòng khám được chọn không tồn tại hoặc đã ngừng hoạt động.',

            'work_date.required' => 'Vui lòng chọn ngày làm việc.',

            'work_date.date' => 'Ngày làm việc chưa hợp lệ. Vui lòng chọn lại ngày bằng ô lịch.',

            'start_time.required' => 'Vui lòng nhập giờ bắt đầu.',

            'start_time.date_format' => 'Giờ bắt đầu chưa đúng. Vui lòng nhập theo dạng giờ:phút, ví dụ 08:30.',

            'end_time.required' => 'Vui lòng nhập giờ kết thúc.',

            'end_time.date_format' => 'Giờ kết thúc chưa đúng. Vui lòng nhập theo dạng giờ:phút, ví dụ 11:30.',

            'end_time.after' => 'Giờ kết thúc phải sau giờ bắt đầu.',

            'status.required' => 'Vui lòng chọn trạng thái.',

            'status.in' => 'Vui lòng chọn lại trạng thái lịch làm việc từ các lựa chọn có sẵn.',
        ];
    }
}
