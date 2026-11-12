<?php

namespace App\Support\Payments;

use App\Models\Payable;

class ReceiptDataBuilder
{
    public function build(Payable $payable, string $type): array
    {
        $payable->loadMissing([
            'medicalVisit.patient.detail', 'medicalVisit.patient.user',
            'medicalVisit.examinationRoom',
        ]);
        $data = ['serviceItem' => null, 'prescription' => null];
        abort_unless($payable->medicalVisit?->patient, 422, 'Không đủ thông tin bệnh nhân hoặc lượt khám để lập phiếu thu.');
        if ($type === 'service') {
            $payable->loadMissing(['serviceOrderItem.medicalService', 'serviceOrderItem.serviceOrder']);
            $item = $payable->serviceOrderItem;
            if ($item && (int) $item->serviceOrder?->medical_visit_id === (int) $payable->medical_visit_id) {
                $data['serviceItem'] = $item;
            }
            abort_unless($data['serviceItem']?->medicalService && $data['serviceItem']->price_at_order !== null && (float) $data['serviceItem']->price_at_order >= 0, 422, 'Thiếu thông tin dịch vụ hoặc giá chỉ định để lập phiếu thu.');
        }
        if ($type === 'prescription') {
            $payable->loadMissing('prescription.items.medicine');
            $prescription = $payable->prescription;
            if ($prescription && (int) $prescription->medical_visit_id === (int) $payable->medical_visit_id) {
                $data['prescription'] = $prescription;
            }
            abort_unless($data['prescription'] && $data['prescription']->items->isNotEmpty(), 422, 'Thiếu đơn thuốc hoặc chi tiết thuốc để lập phiếu thu.');
            foreach ($data['prescription']->items as $item) {
                abort_unless($item->medicine && $item->quantity !== null && (float) $item->quantity > 0 && $item->unit_price !== null && (float) $item->unit_price >= 0 && $item->line_total !== null && (float) $item->line_total >= 0, 422, 'Thiếu tên thuốc, số lượng hoặc giá thuốc để lập phiếu thu.');
            }
        }
        return $data;
    }
}
