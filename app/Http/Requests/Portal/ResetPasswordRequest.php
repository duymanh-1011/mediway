<?php

namespace App\Http\Requests\Portal;

use App\Rules\PasswordByteLimit;
use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['password' => ['required', 'string', 'min:8', 'max:72', new PasswordByteLimit, 'confirmed']]; }
    public function messages(): array { return ['password.required' => 'Vui lòng nhập mật khẩu mới.', 'password.min' => 'Mật khẩu cần có ít nhất 8 ký tự.', 'password.confirmed' => 'Xác nhận mật khẩu chưa khớp.']; }
}
