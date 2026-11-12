<?php

namespace App\Support\Portal;

class PatientClinicalRecords
{
    public static function restrict($query, string $table)
    {
        if ($table === 'prescriptions') {
            // Đơn thuốc của đúng bệnh nhân được hiển thị ngay sau khi lưu.
            return $query;
        }

        // Hiển thị kết quả đã lưu, giữ chặn các chỉ định bị hủy.
        return $query->whereHas('serviceOrderItem', fn ($item) => $item
            ->whereHas('serviceOrder', fn ($order) => $order->where('status', '!=', 'CANCELLED')));
    }
}
