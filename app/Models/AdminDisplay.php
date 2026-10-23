<?php

namespace App\Models;

final class AdminDisplay
{
    public const EMPLOYEE_TYPES = [
        'CASHIER' => 'Nhân viên thu ngân', 'IMAGING_TECHNICIAN' => 'Kỹ thuật viên chẩn đoán hình ảnh',
        'LAB_TECHNICIAN' => 'Kỹ thuật viên xét nghiệm', 'NURSE' => 'Điều dưỡng',
    ];

    public const LABELS = [
        'ACTIVE' => 'Đang hoạt động', 'INACTIVE' => 'Ngừng hoạt động', 'LOCKED' => 'Đã khóa',
        'ALLOWED' => 'Cho phép', 'DENIED' => 'Không cho phép', 'YES' => 'Có', 'NO' => 'Không',
        'VERIFIED' => 'Đã xác nhận', 'UNVERIFIED' => 'Chưa xác nhận',
        'LINKED' => 'Đã liên kết', 'UNLINKED' => 'Chưa liên kết',
        'SYSTEM' => 'Hệ thống', 'CUSTOM' => 'Tùy chỉnh', 'DRAFT' => 'Bản nháp',
        'PENDING' => 'Chờ xử lý', 'PROCESSING' => 'Đang thực hiện', 'COMPLETED' => 'Đã hoàn thành',
        'CANCELLED' => 'Đã hủy', 'PAID' => 'Đã thanh toán', 'UNPAID' => 'Chưa thanh toán',
        'WAITING' => 'Đang chờ', 'CALLED' => 'Đã gọi', 'IN_SERVICE' => 'Đang phục vụ', 'SKIPPED' => 'Đã bỏ qua',
        'RETURN_AFTER_SERVICE' => 'Quay lại sau dịch vụ', 'INITIAL_EXAM' => 'Khám ban đầu',
        'IN_EXAMINATION' => 'Đang khám', 'WAITING_FOR_SERVICE' => 'Chờ thực hiện dịch vụ', 'RETURNING' => 'Quay lại bác sĩ',
        'BOOKED' => 'Đã đặt lịch', 'CHECKED_IN' => 'Đã tiếp nhận',
        'Administrator' => 'Quản trị viên', 'Doctor' => 'Bác sĩ', 'Employee' => 'Nhân viên', 'Patient' => 'Bệnh nhân',
        'MALE' => 'Nam', 'FEMALE' => 'Nữ', 'OTHER' => 'Khác',
        'EXAMINATION_FEE' => 'Phí khám', 'SERVICE_FEE' => 'Phí dịch vụ', 'MEDICINE_FEE' => 'Tiền thuốc',
        'SUCCESS' => 'Thành công', 'FAILED' => 'Thất bại', 'CASH' => 'Tiền mặt', 'BANK_TRANSFER' => 'Chuyển khoản',
        'QR' => 'Mã QR', 'CARD' => 'Thẻ', 'COUNTER' => 'Tại quầy', 'ONLINE' => 'Trực tuyến', 'WALK_IN' => 'Khám trực tiếp',
        'KIOSK' => 'Máy tự phục vụ', 'Kiosk' => 'Máy tự phục vụ', 'kiosk' => 'máy tự phục vụ',
        'Check-in' => 'Tiếp nhận', 'check-in' => 'tiếp nhận',
        'OUTPATIENT' => 'Điều trị ngoại trú', 'INPATIENT' => 'Điều trị nội trú', 'TRANSFER' => 'Chuyển viện',
        'PRIMARY' => 'Chẩn đoán chính', 'SECONDARY' => 'Chẩn đoán phụ', 'PRELIMINARY' => 'Chẩn đoán sơ bộ', 'CONFIRMED' => 'Chẩn đoán xác định',
        'MILD' => 'Nhẹ', 'MODERATE' => 'Trung bình', 'SEVERE' => 'Nặng',
        'CREATE' => 'Tạo mới', 'UPDATE' => 'Cập nhật', 'DELETE' => 'Xóa', 'STATUS_CHANGE' => 'Đổi trạng thái',
        'CHECK_IN' => 'Tiếp nhận', 'CANCEL' => 'Hủy', 'PRINT' => 'In',
        'LOGIN' => 'Đăng nhập', 'LOGOUT' => 'Đăng xuất',
        'users' => 'Tài khoản', 'User' => 'Tài khoản', 'roles' => 'Vai trò',
        'appointments' => 'Lịch hẹn', 'Appointment' => 'Lịch hẹn',
        'medical_visits' => 'Lượt khám', 'MedicalVisit' => 'Lượt khám',
        'examination_room_queues' => 'Hàng đợi phòng khám',
        'visit_clinical_notes' => 'Nội dung khám', 'visit_diagnoses' => 'Chẩn đoán', 'visit_conclusions' => 'Kết luận',
        'service_orders' => 'Chỉ định dịch vụ', 'service_order_items' => 'Dòng chỉ định dịch vụ', 'service_results' => 'Kết quả dịch vụ',
        'payables' => 'Khoản phải thanh toán', 'payment_transactions' => 'Giao dịch thanh toán',
        'prescriptions' => 'Đơn thuốc', 'prescription_items' => 'Chi tiết đơn thuốc',
        'patients' => 'Bệnh nhân', 'doctors' => 'Bác sĩ', 'employees' => 'Nhân viên',
        'medical_services' => 'Dịch vụ y tế', 'MedicalService' => 'Dịch vụ y tế',
        'specialties' => 'Chuyên khoa', 'examination_rooms' => 'Phòng khám', 'service_groups' => 'Nhóm dịch vụ',
        'disease_groups' => 'Nhóm bệnh', 'diseases' => 'Bệnh', 'medicines' => 'Thuốc',
    ];

    public static function label(?string $code, ?string $context = null): string
    {
        if ($context === 'service' && $code === 'PENDING') {
            return 'Chờ thực hiện';
        }
        if ($context === 'payment' && $code === 'PENDING') {
            return 'Chờ xác nhận thanh toán';
        }

        return self::EMPLOYEE_TYPES[$code ?? ''] ?? self::LABELS[$code ?? ''] ?? ($code ?: '—');
    }

    public static function employeeTypeCode(string $label): string
    {
        $code = array_search(trim($label), self::EMPLOYEE_TYPES, true);

        return $code === false ? $label : $code;
    }

    public static function text(?string $text): string
    {
        return preg_replace_callback('/\b(?:[A-Z][A-Z_]+|Administrator|Doctor|Employee|Patient|Kiosk|kiosk|Check-in|check-in)\b/', fn (array $match): string => self::label($match[0]), $text ?? '') ?? '';
    }

    public static function badge(?string $code): string
    {
        return match ($code) {
            'ACTIVE', 'PAID', 'SUCCESS', 'COMPLETED', 'ALLOWED', 'YES', 'VERIFIED', 'LINKED', 'CREATE' => 'success',
            'INACTIVE', 'LOCKED', 'FAILED', 'CANCELLED', 'DELETE', 'CANCEL' => 'danger',
            'PROCESSING', 'IN_SERVICE', 'IN_EXAMINATION', 'RETURNING', 'CALLED', 'CHECKED_IN', 'UPDATE', 'STATUS_CHANGE' => 'info',
            'PENDING', 'WAITING', 'UNPAID', 'WAITING_FOR_SERVICE', 'BOOKED', 'SKIPPED', 'UNVERIFIED' => 'warning',
            default => 'neutral',
        };
    }

    public static function values(mixed $values): mixed
    {
        if (is_array($values)) {
            return array_map(self::values(...), $values);
        }

        return is_string($values) ? self::label($values) : $values;
    }
}
