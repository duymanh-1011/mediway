<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PasswordByteLimit implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && (strlen($value) > 72 || str_contains($value, "\0"))) {
            $fail('Mật khẩu vượt giới hạn cho phép hoặc chứa ký tự không hợp lệ. Vui lòng rút ngắn mật khẩu, nhất là khi dùng ký tự có dấu hoặc biểu tượng.');
        }
    }
}
