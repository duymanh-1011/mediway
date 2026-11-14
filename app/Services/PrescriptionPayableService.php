<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\MedicalVisit;
use App\Models\Payable;
use App\Models\PaymentStatus;
use App\Models\Prescription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class PrescriptionPayableService
{
    public function create(Prescription $prescription, Request $request, ?int $patientId = null): Payable
    {
        return DB::transaction(function () use ($prescription, $request, $patientId): Payable {
            // Khóa lượt rồi khóa đơn, dùng chung thứ tự cho Admin và bệnh nhân.
            $visit = MedicalVisit::with('status')->whereKey($prescription->medical_visit_id)->lockForUpdate()->firstOrFail();
            if ($patientId !== null) {
                abort_unless((int) $visit->patient_id === $patientId, 404);
                if ($visit->status?->name !== 'COMPLETED') {
                    throw ValidationException::withMessages(['confirm_purchase' => 'Chỉ có thể xác nhận mua thuốc sau khi hoàn tất lượt khám.']);
                }
            }
            $prescription = Prescription::with('items')->whereKey($prescription->id)->where('medical_visit_id', $visit->id)->lockForUpdate()->firstOrFail();
            if ($prescription->status !== 'ACTIVE') {
                throw ValidationException::withMessages(['confirm_purchase' => 'Chỉ có thể tạo khoản thu cho đơn thuốc đang hiệu lực.']);
            }
            $existing = Payable::where('prescription_id', $prescription->id)
                ->whereDoesntHave('paymentStatus', fn ($q) => $q->where('name', 'CANCELLED'))->first();
            if ($existing) {
                if ($patientId !== null) return $existing;
                throw ValidationException::withMessages(['confirm_purchase' => 'Đơn thuốc này đã có khoản thu. Vui lòng kiểm tra khoản thu hiện có.']);
            }
            $total = $prescription->pricedTotal();
            if ($patientId !== null && $prescription->items->contains(fn ($item) => $item->unit_price === null || $item->line_total === null
                || (float) $item->unit_price < 0 || (float) $item->line_total < 0 || (float) $item->quantity <= 0)) {
                throw ValidationException::withMessages(['confirm_purchase' => 'Đơn thuốc chưa có đủ giá hợp lệ; vui lòng liên hệ nhân viên.']);
            }
            if ($total === null || (float) $total <= 0) {
                throw ValidationException::withMessages(['confirm_purchase' => 'Đơn thuốc chưa có đủ giá hợp lệ; cần đối chiếu trước khi thu tiền.']);
            }
            $unpaid = PaymentStatus::where('name', 'UNPAID')->where('is_active', true)->first();
            if (! $unpaid) throw ValidationException::withMessages(['confirm_purchase' => 'Chưa cấu hình trạng thái chưa thanh toán.']);
            do { $code = 'PT'.strtoupper(Str::random(20)); } while (Payable::where('payable_code', $code)->exists());
            $payable = Payable::create([
                'payable_code' => $code, 'medical_visit_id' => $visit->id, 'prescription_id' => $prescription->id,
                'payment_type' => 'MEDICINE_FEE', 'description' => 'Tiền thuốc - '.$prescription->prescription_code,
                'amount' => $total, 'payment_status_id' => $unpaid->id, 'generated_at' => now(),
            ]);
            AuditLog::create([
                'user_id' => $request->user()->id, 'role_name_snapshot' => $request->user()->role?->name,
                'action' => 'CREATE', 'entity_type' => 'payables', 'entity_id' => $payable->id,
                'entity_business_code' => $code, 'old_values' => [], 'new_values' => ['prescription_id' => $prescription->id, 'amount' => $total],
                'description' => 'Tạo khoản thu tiền thuốc cho đơn '.$prescription->prescription_code.'.',
                'ip_address' => $request->ip(), 'user_agent' => mb_substr((string) $request->userAgent(), 0, 500), 'performed_at' => now(),
            ]);
            return $payable;
        }, 3);
    }
}
