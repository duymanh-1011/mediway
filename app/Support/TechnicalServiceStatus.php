<?php

namespace App\Support;

use App\Models\ServiceOrderItem;

final class TechnicalServiceStatus
{
    /** @return array{label: string, tone: string} */
    public static function for(ServiceOrderItem $item): array
    {
        // Trạng thái dịch vụ và thanh toán là hai thông tin độc lập.
        $status = $item->serviceStatus;
        if ($status?->name === 'PENDING') {
            $payment = $item->payable?->paymentStatus;
            return match ($payment?->name) {
                'PAID' => ['label' => 'Đã thanh toán / Chờ thực hiện', 'tone' => 'paid'],
                'UNPAID' => ['label' => 'Chưa thanh toán / Chờ thực hiện', 'tone' => 'waiting'],
                'PENDING' => ['label' => 'Chờ xác nhận thanh toán / Chờ thực hiện', 'tone' => 'waiting'],
                'CANCELLED' => ['label' => 'Đã hủy khoản phải thu / Chờ thực hiện', 'tone' => 'cancelled'],
                null, '' => ['label' => 'Chưa có thông tin thanh toán / Chờ thực hiện', 'tone' => 'neutral'],
                // Không suy ra đã trả tiền từ cờ allows_service_execution.
                default => ['label' => (trim((string) $payment->description) ?: $payment->name).' / Chờ thực hiện', 'tone' => 'neutral'],
            };
        }

        return match ($status?->name) {
            'PROCESSING' => ['label' => 'Đang thực hiện', 'tone' => 'processing'],
            'COMPLETED' => ['label' => 'Đã hoàn thành', 'tone' => 'completed'],
            'CANCELLED' => ['label' => 'Đã hủy', 'tone' => 'cancelled'],
            default => ['label' => $status?->description ?: ($status?->name ?: 'Chưa cập nhật trạng thái'), 'tone' => 'neutral'],
        };
    }
}
