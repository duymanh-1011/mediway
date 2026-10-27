<?php

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['otp' => ['required', 'string', 'regex:/^[0-9]{6}$/']]; }
    public function messages(): array { return ['otp.required' => 'Vui lòng nhập mã xác minh.', 'otp.regex' => 'Mã xác minh gồm 6 chữ số.']; }
}
