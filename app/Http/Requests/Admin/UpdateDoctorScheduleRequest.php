<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ValidatesDoctorSpecialty;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDoctorScheduleRequest extends FormRequest
{
    use ValidatesDoctorSpecialty;

    public function authorize(): bool
    {
        return true;
    }

    // Giữ nguyên lịch cũ nếu chọn lịch cũ; ca chuẩn lấy giờ từ máy chủ.
    protected function prepareForValidation(): void
    {
        $shift = $this->input('shift');
        $schedule = $this->route('doctor_schedule');
        if ($shift === 'LEGACY' && $schedule instanceof \App\Models\DoctorSchedule) {
            $this->merge(['start_time' => substr($schedule->start_time, 0, 5), 'end_time' => substr($schedule->end_time, 0, 5)]);
        } elseif (isset(\App\Services\DoctorShiftService::SHIFTS[$shift])) {
            [$start, $end] = \App\Services\DoctorShiftService::SHIFTS[$shift];
            $this->merge(['start_time' => $start, 'end_time' => $end]);
        }
    }

    public function rules(): array
    {
        return [
            'shift' => ['required', Rule::in(['MORNING', 'AFTERNOON', 'LEGACY'])],
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
            'shift.required' => 'Vui lòng chọn giữ giờ hiện tại hoặc chọn ca làm việc.',
            'shift.in' => 'Ca làm việc không hợp lệ.',
            'doctor_id.required' => 'Vui lòng chọn bác sĩ.',

            'doctor_id.exists' => 'Bác sĩ được chọn không tồn tại hoặc đã ngừng hoạt động.',

            'specialty_id.required' => 'Vui lòng chọn chuyên khoa.',

            'specialty_id.exists' => 'Chuyên khoa được chọn không tồn tại hoặc đã ngừng hoạt động.',

            'examination_room_id.required' => 'Vui lòng chọn phòng khám.',

            'examination_room_id.exists' => 'Phòng khám được chọn không tồn tại hoặc đã ngừng hoạt động.',

            'work_date.required' => 'Vui lòng chọn ngày làm việc.',

            'start_time.required' => 'Vui lòng nhập giờ bắt đầu.',

            'end_time.required' => 'Vui lòng nhập giờ kết thúc.',

            'end_time.after' => 'Giờ kết thúc phải sau giờ bắt đầu.',

            'status.required' => 'Vui lòng chọn trạng thái.',

            'status.in' => 'Vui lòng chọn lại trạng thái lịch làm việc từ các lựa chọn có sẵn.',
        ];
    }
}
