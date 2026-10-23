<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

class AuditValuePresenter
{
    private const LABELS = [
        'id' => 'Số tham chiếu', 'items' => 'Danh sách dịch vụ', 'service_items' => 'Dịch vụ',
        'service_order_id' => 'Phiếu chỉ định', 'service_order_item_id' => 'Dịch vụ được chỉ định',
        'medical_visit_id' => 'Lượt khám', 'appointment_id' => 'Lịch hẹn',
        'doctor_schedule_id' => 'Lịch làm việc', 'appointment_status_id' => 'Trạng thái lịch hẹn',
        'payable_id' => 'Khoản phải thanh toán', 'collected_by_employee_id' => 'Nhân viên thu ngân',
        'performed_by_employee_id' => 'Người thực hiện', 'ordered_by_doctor_id' => 'Bác sĩ chỉ định',
        'service_order_code' => 'Mã phiếu chỉ định', 'payable_code' => 'Mã khoản thu',
        'transaction_code' => 'Mã giao dịch', 'visit_code' => 'Mã lượt khám',
        'appointment_code' => 'Mã lịch hẹn', 'patient_code' => 'Mã bệnh nhân',
        'doctor_code' => 'Mã bác sĩ', 'employee_code' => 'Mã nhân viên',
        'business_code' => 'Mã tài khoản', 'prescription_code' => 'Mã đơn thuốc',
        'medical_service_id' => 'Dịch vụ y tế', 'medicine_id' => 'Thuốc',
        'amount' => 'Số tiền', 'price_at_order' => 'Giá tại thời điểm chỉ định',
        'old_status' => 'Trạng thái trước', 'new_status' => 'Trạng thái sau',
        'service_status' => 'Trạng thái dịch vụ', 'payment_status' => 'Trạng thái thanh toán',
        'visit_status' => 'Trạng thái lượt khám', 'queue_status' => 'Trạng thái hàng đợi',
        'created_at' => 'Ngày tạo', 'updated_at' => 'Cập nhật gần nhất', 'paid_at' => 'Thời điểm thanh toán',
        'confirmed_at' => 'Thời điểm xác nhận', 'started_at' => 'Bắt đầu thực hiện',
        'completed_at' => 'Hoàn tất', 'ordered_at' => 'Thời điểm chỉ định',
        'generated_at' => 'Thời điểm tạo khoản thu', 'checked_in_at' => 'Thời điểm tiếp nhận',
        'queued_at' => 'Thời điểm vào hàng đợi', 'called_at' => 'Thời điểm gọi',
        'service_started_at' => 'Bắt đầu phục vụ', 'call_count' => 'Số lần gọi',
        'queue_number' => 'Số thứ tự hàng đợi', 'appointment_date' => 'Ngày hẹn',
        'examination_started_at' => 'Bắt đầu khám', 'examination_ended_at' => 'Kết thúc khám',
        'execution_notes' => 'Ghi chú thực hiện', 'quantity' => 'Số lượng',
        'dose_per_use' => 'Liều mỗi lần', 'times_per_day' => 'Số lần mỗi ngày',
        'duration_days' => 'Số ngày dùng', 'usage_unit' => 'Đơn vị sử dụng',
        'usage_method' => 'Cách dùng', 'usage_instructions' => 'Hướng dẫn sử dụng',
        'administration_time' => 'Thời điểm dùng', 'failure_reason' => 'Lý do thất bại',
        'result_pdf_path' => 'Tệp kết quả', 'result_image_path' => 'Ảnh kết quả',
        'bmi' => 'Chỉ số khối cơ thể', 'before' => 'Trước thay đổi', 'after' => 'Sau thay đổi',
    ];

    private const REFERENCES = [
        'user_id' => [User::class, 'business_code', null],
        'verified_by_user_id' => [User::class, 'business_code', null],
        'patient_id' => [Patient::class, 'patient_code', 'full_name'],
        'doctor_id' => [Doctor::class, 'doctor_code', 'full_name'],
        'ordered_by_doctor_id' => [Doctor::class, 'doctor_code', 'full_name'],
        'performed_by_employee_id' => [Employee::class, 'employee_code', 'full_name'],
        'collected_by_employee_id' => [Employee::class, 'employee_code', 'full_name'],
        'medical_visit_id' => [MedicalVisit::class, 'visit_code', null],
        'appointment_id' => [Appointment::class, 'appointment_code', null],
        'service_order_id' => [ServiceOrder::class, 'service_order_code', null],
        'payable_id' => [Payable::class, 'payable_code', null],
        'medical_service_id' => [MedicalService::class, 'service_code', 'name'],
        'medicine_id' => [Medicine::class, 'medicine_code', 'name'],
        'specialty_id' => [Specialty::class, 'specialty_code', 'name'],
        'examination_room_id' => [ExaminationRoom::class, 'room_code', 'name'],
        'disease_id' => [Disease::class, 'disease_code', 'name'],
        'disease_group_id' => [DiseaseGroup::class, 'disease_group_code', 'name'],
        'service_group_id' => [ServiceGroup::class, 'service_group_code', 'name'],
        'appointment_status_id' => [AppointmentStatus::class, 'name', null],
        'visit_status_id' => [VisitStatus::class, 'name', null],
        'service_status_id' => [ServiceStatus::class, 'name', null],
        'payment_status_id' => [PaymentStatus::class, 'name', null],
        'queue_status_id' => [QueueStatus::class, 'name', null],
    ];

    private array $references = [];

    /** @return list<array{label: string, value: string}> */
    public function rows(mixed $values, string $prefix = '', bool $clinicalParameters = false): array
    {
        if (is_string($values)) {
            $decoded = json_decode($values, true);
            $values = json_last_error() === JSON_ERROR_NONE ? $decoded : $values;
        }
        if ($values === null || $values === '' || $values === []) {
            return [];
        }
        if (! is_array($values)) {
            return [['label' => $prefix ?: 'Nội dung đã ghi nhận', 'value' => AdminDisplay::text((string) $values)]];
        }
        $rows = [];
        foreach ($values as $field => $value) {
            $field = (string) $field;
            $label = ctype_digit($field) ? 'Mục '.((int) $field + 1)
                : self::LABELS[$field] ?? config('admin.validation.attributes.'.$field)
                    ?? ($clinicalParameters ? Str::headline($field) : 'Thông tin bổ sung');
            $label = $prefix ? $prefix.' — '.$label : Str::ucfirst($label);
            if (preg_match('/password|token|secret|otp/i', $field)) {
                $rows[] = ['label' => 'Thông tin bảo mật', 'value' => 'Đã ẩn để bảo vệ tài khoản'];
            } elseif (is_array($value)) {
                $rows = array_merge($rows, $this->rows($value, $label, $clinicalParameters));
            } else {
                $rows[] = ['label' => $label, 'value' => $this->value($field, $value)];
            }
        }

        return $rows;
    }

    private function value(string $field, mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'Chưa có';
        }
        if ($field === 'is_active') {
            return AdminDisplay::label($value ? 'ACTIVE' : 'INACTIVE');
        }
        if (in_array($field, ['allows_transition', 'allows_service_execution'], true)) {
            return $value ? 'Cho phép' : 'Không cho phép';
        }
        if (is_bool($value) || $field === 'has_follow_up') {
            return $value ? 'Có' : 'Không';
        }
        if (str_ends_with($field, '_path')) {
            return 'Có tệp đính kèm';
        }
        if (isset(self::REFERENCES[$field]) && is_numeric($value)) {
            return $this->reference($field, (int) $value);
        }
        if ($field === 'service_order_item_id' && is_numeric($value)) {
            $item = ServiceOrderItem::with(['serviceOrder', 'medicalService'])->find($value);

            return $item ? $item->serviceOrder->service_order_code.' — '.$item->medicalService->service_code.' — '.$item->medicalService->name
                : 'Dịch vụ đã ghi nhận (số tham chiếu '.$value.')';
        }
        if (in_array($field, ['amount', 'price', 'price_at_order'], true) && is_numeric($value)) {
            return number_format((float) $value, fmod((float) $value, 1) === 0.0 ? 0 : 2, ',', '.').' ₫';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}(?:[T ].*)?$/', (string) $value)
            && preg_match('/(?:_at|_date)$/', $field)) {
            try {
                return CarbonImmutable::parse($value)->format(strlen((string) $value) > 10 ? 'd/m/Y H:i:s' : 'd/m/Y');
            } catch (\Throwable) {
                return 'Thời gian đã lưu chưa đúng định dạng';
            }
        }

        return AdminDisplay::text((string) $value);
    }

    private function reference(string $field, int $id): string
    {
        $key = $field.':'.$id;
        if (! array_key_exists($key, $this->references)) {
            [$model, $code, $name] = self::REFERENCES[$field];
            $record = $model::find($id);
            $context = str_starts_with($field, 'service_status') ? 'service' : (str_starts_with($field, 'payment_status') ? 'payment' : null);
            $this->references[$key] = $record ? AdminDisplay::label($record->getAttribute($code), $context).($name ? ' — '.$record->getAttribute($name) : '')
                : 'Dữ liệu đã ghi nhận (số tham chiếu '.$id.')';
        }

        return $this->references[$key];
    }
}
