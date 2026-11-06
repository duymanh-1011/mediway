<?php

namespace App\Services;

use App\Models\Payable;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class AppointmentPaymentDateGuard
{
    private const TIMEZONE = 'Asia/Ho_Chi_Minh';

    /**
     * Trả về lý do không thể thanh toán.
     *
     * null = vượt qua kiểm tra ngày hẹn.
     *
     * Không thay thế các kiểm tra:
     * quyền, trạng thái, số tiền, chống thu trùng.
     */
    public function unavailableReason(Payable $payable): ?string
    {
        $payable->loadMissing('medicalVisit.appointment');

        $visit = $payable->medicalVisit;

        if (! $visit) {
            return 'Không xác định được lượt khám của khoản thu.'
                . ' Vui lòng liên hệ bộ phận tiếp đón.';
        }

        /*
         * WALK_IN:
         *
         * Không có Appointment nên không thể dùng
         * appointment_date.
         *
         * Giữ nguyên cơ chế hiện hữu trong giai đoạn
         * này, chờ nhóm chốt chính sách riêng.
         */
        if (! $visit->appointment_id) {
            return null;
        }

        $appointment = $visit->appointment;

        if (! $appointment || ! $appointment->appointment_date) {
            return 'Không xác định được ngày hẹn khám.'
                . ' Vui lòng kiểm tra lại lịch hẹn.';
        }

        /*
         * appointment_date là cột DATE đã cast
         * trong model Appointment.
         *
         * Chỉ so sánh ngày địa phương Việt Nam.
         */
        $scheduledDate = $appointment
            ->appointment_date
            ->format('Y-m-d');

        $today = CarbonImmutable::now(
            self::TIMEZONE
        )->format('Y-m-d');

        if ($scheduledDate === $today) {
            return null;
        }

        $displayDate = $appointment
            ->appointment_date
            ->format('d/m/Y');

        if ($scheduledDate > $today) {
            return 'Chưa đến ngày hẹn khám '
                . $displayDate
                . '. Chỉ được thanh toán trong đúng ngày hẹn.';
        }

        return 'Đã quá ngày hẹn khám '
            . $displayDate
            . '. Không thể ghi nhận thanh toán cho lịch này.';
    }

    /**
     * Backend bắt buộc từ chối khoản sai ngày.
     */
    public function assertCanPay(Payable $payable): void
    {
        $reason = $this->unavailableReason($payable);

        if ($reason !== null) {
            throw ValidationException::withMessages([
                'payment_date' => $reason,
            ]);
        }
    }
}
