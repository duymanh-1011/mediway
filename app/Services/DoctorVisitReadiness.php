<?php

namespace App\Services;

use App\Models\MedicalVisit;
use App\Models\ServiceOrderItem;

/** Kiểm tra nội dung chuyên môn; không tự hoàn tất lượt khám hay hàng đợi. */
final class DoctorVisitReadiness
{
    /**
     * @return array{ready: bool, reasons: array<string, string>}
     *
     * Bên gọi phải tự kiểm tra bác sĩ, quyền, trạng thái và khóa dữ liệu
     * trong giao dịch trước khi sử dụng kết quả để hoàn tất lượt khám.
     */
    public function check(MedicalVisit $visit): array
    {
        $reasons = [];

        // Dùng cùng điều kiện với chức năng hoàn tất lượt khám của Admin.
        if (! $visit->diagnoses()->exists()) {
            $reasons['diagnosis'] = 'Chưa có chẩn đoán.';
        }
        if (! $visit->conclusion()->exists()) {
            $reasons['conclusion'] = 'Chưa có kết luận khám.';
        }
        if (ServiceOrderItem::whereHas('serviceOrder', fn ($query) => $query->where('medical_visit_id', $visit->id))->unresolved()->exists()) {
            $reasons['service'] = 'Còn dịch vụ cần chờ xử lý.';
        }

        return ['ready' => $reasons === [], 'reasons' => $reasons];
    }
}
