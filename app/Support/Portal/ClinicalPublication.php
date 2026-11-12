<?php

namespace App\Support\Portal;

class ClinicalPublication
{
    public static function restrict($query, string $table)
    {
        if ($table === 'prescriptions') {
            return $query->whereHas('medicalVisit.status', fn ($status) => $status->where('name', 'COMPLETED'));
        }

        // Trạng thái thực hiện áp dụng cho cả kết quả cũ chưa có cờ công bố.
        return $query->whereHas('serviceOrderItem', fn ($item) => $item
            ->whereHas('serviceStatus', fn ($status) => $status->where('name', 'COMPLETED'))
            ->whereHas('serviceOrder', fn ($order) => $order->where('status', '!=', 'CANCELLED')));
    }
}
