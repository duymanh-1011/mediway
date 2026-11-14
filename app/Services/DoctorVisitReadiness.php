<?php

namespace App\Services;

use App\Models\MedicalVisit;
use App\Models\ServiceOrderItem;

/** Kiểm tra nội dung chuyên môn; không tự hoàn tất lượt khám hay hàng đợi. */
final class DoctorVisitReadiness
{
    /** Các thông tin tối thiểu phải được lưu trước khi ghi kết luận. */
    public function conclusionPrerequisites(MedicalVisit $visit): array
    {
        $visit->loadMissing(['clinicalNote', 'diagnoses']);
        $note = $visit->clinicalNote;
        $fields = ['reason_for_visit', 'current_symptoms', 'present_illness_history',
            'relevant_medical_history', 'physical_examination', 'initial_assessment', 'notes'];
        $reasons = [];

        // Cùng tiêu chí với biểu mẫu khám: ít nhất một trường có nội dung.
        if (! $note || ! collect($fields)->contains(fn ($field) => filled($note->{$field}))) {
            $reasons['clinical_note'] = 'Vui lòng lưu ít nhất một nội dung khám trước khi lưu kết luận.';
        }
        if ($visit->diagnoses->isEmpty()) {
            $reasons['diagnosis'] = 'Vui lòng thêm ít nhất một chẩn đoán trước khi lưu kết luận.';
        }
        return $reasons;
    }

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
