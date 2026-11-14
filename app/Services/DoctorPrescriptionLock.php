<?php

namespace App\Services;

use App\Models\MedicalVisit;
use Illuminate\Validation\ValidationException;

final class DoctorPrescriptionLock
{
    public const MESSAGE = 'Đã tạo đơn thuốc. Bác sĩ không thể thay đổi nội dung khám, chẩn đoán hoặc chỉ định thêm dịch vụ.';
    public const CONCLUSION_MESSAGE = 'Đã lưu kết luận khám. Bác sĩ không thể chỉnh sửa hồ sơ khám hoặc kết luận trong trang này.';

    public static function assertConclusionUnlocked(MedicalVisit $visit): void
    {
        // Kết luận đã lưu khóa các bước trước, kể cả khi đơn thuốc chưa được tạo.
        if ($visit->conclusion()->exists()) {
            throw ValidationException::withMessages(['conclusion_lock' => self::CONCLUSION_MESSAGE]);
        }
    }

    public static function assertUnlocked(MedicalVisit $visit): void
    {
        self::assertConclusionUnlocked($visit);
        // Kiểm tra cả đơn cũ đã hủy; không tự mở lại hồ sơ sau khi từng tạo đơn.
        if ($visit->prescription()->exists()) {
            throw ValidationException::withMessages(['prescription_lock' => self::MESSAGE]);
        }
    }
}
