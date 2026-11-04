<?php

namespace App\Services;

use App\Models\MedicalVisit;
use App\Models\Payable;
use App\Models\PaymentStatus;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ExaminationFeeService
{
    public function createForVisit(MedicalVisit $visit): Payable
    {
        $unpaid = PaymentStatus::where('name', 'UNPAID')->where('is_active', true)->first();
        if (! $unpaid) throw ValidationException::withMessages(['appointment' => 'Chưa cấu hình trạng thái chưa thanh toán.']);
        return Payable::create(['payable_code' => 'PT'.strtoupper(Str::random(20)), 'medical_visit_id' => $visit->id,
            'service_order_item_id' => null, 'payment_type' => 'EXAMINATION_FEE', 'description' => 'Phí khám bệnh',
            'amount' => config('patient_appointments.examination_fee'), 'payment_status_id' => $unpaid->id, 'generated_at' => now()]);
    }
}
