<?php

namespace App\Support\Payments;

use App\Models\Payable;
use App\Models\AdminDisplay;

class ReceiptDataBuilder
{
    public function build(Payable $payable, string $type): array
    {
        $payable->loadMissing([
            'medicalVisit.patient.detail', 'medicalVisit.patient.user',
            'medicalVisit.doctor', 'medicalVisit.specialty', 'medicalVisit.examinationRoom', 'medicalVisit.diagnoses.disease',
        ]);
        $data = ['serviceItem' => null, 'prescription' => null];
        abort_unless($payable->medicalVisit?->patient, 422, 'Không đủ thông tin bệnh nhân hoặc lượt khám để lập phiếu thu.');
        if ($type === 'exam') {
            $visit = $payable->medicalVisit;
            $patient = $visit->patient;
            $examDate = $visit->examination_started_at ?? $visit->checked_in_at;
            $data['examPatient'] = [
                'name' => $patient->full_name,
                'address' => $patient->detail?->current_address,
                'age' => $patient->date_of_birth ? (int) $patient->date_of_birth->diffInYears($examDate ?? now()) : null,
                'gender' => $patient->gender ? AdminDisplay::label($patient->gender) : null,
                'phone' => $patient->user?->phone,
                'exam_date' => $examDate,
                'diagnosis' => $visit->diagnoses->map(fn ($diagnosis) => $diagnosis->diagnosis_description ?: $diagnosis->disease?->name)->filter()->join('; ') ?: null,
            ];
        }
        if ($type === 'service') {
            $payable->loadMissing(['serviceOrderItem.medicalService.serviceGroups', 'serviceOrderItem.serviceOrder.orderedByDoctor']);
            $item = $payable->serviceOrderItem;
            if ($item && (int) $item->serviceOrder?->medical_visit_id === (int) $payable->medical_visit_id) {
                $data['serviceItem'] = $item;
            }
            abort_unless($data['serviceItem']?->medicalService && $data['serviceItem']->price_at_order !== null && (float) $data['serviceItem']->price_at_order >= 0, 422, 'Thiếu thông tin dịch vụ hoặc giá chỉ định để lập phiếu thu.');
            $visit = $payable->medicalVisit;
            $patient = $visit->patient;
            $referenceDate = $visit->examination_started_at ?? $visit->checked_in_at ?? now();
            $service = $item->medicalService;
            $data['serviceReceipt'] = [
                'patient_name' => $patient->full_name,
                'patient_code' => $patient->patient_code,
                'patient_age' => $patient->date_of_birth ? (int) $patient->date_of_birth->diffInYears($referenceDate) : null,
                'patient_gender' => $patient->gender ? AdminDisplay::label($patient->gender) : null,
                'patient_address' => $patient->detail?->current_address,
                'patient_phone' => $patient->user?->phone,
                'doctor_name' => $item->serviceOrder?->orderedByDoctor?->full_name,
                'department_name' => $visit->specialty?->name,
                'room_name' => $visit->examinationRoom?->name,
                'service_name' => $service->name,
                'service_group' => $service->serviceGroups->pluck('name')->filter()->join(', ') ?: null,
                'service_unit' => $service->unit,
                // The current schema records one item and its price, but no quantity.
                'quantity' => null,
                'unit_price' => $item->price_at_order,
                'line_total' => $item->price_at_order,
            ];
        }
        if ($type === 'prescription') {
            $payable->loadMissing(['prescription.items.medicine', 'prescription.doctor']);
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
