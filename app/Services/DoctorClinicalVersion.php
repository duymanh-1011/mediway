<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;

/** Nhận biết dữ liệu đã bị thay đổi sau khi mở biểu mẫu. */
final class DoctorClinicalVersion
{
    public static function of(?Model $record): string
    {
        if (! $record) return hash('sha256', 'chua-co-du-lieu');
        // Dùng toàn bộ giá trị lưu trong bản ghi để phát hiện cả các thay đổi cùng giây.
        $attributes = $record->getAttributes();
        ksort($attributes);
        return hash('sha256', json_encode($attributes, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }
}
