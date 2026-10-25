<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ActiveResultVerifier implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_scalar($value) || ! User::eligibleResultVerifier()->whereKey($value)->exists()) {
            $fail('Vui lòng chọn bác sĩ có tài khoản và hồ sơ đang hoạt động để xác nhận kết quả.');
        }
    }
}
