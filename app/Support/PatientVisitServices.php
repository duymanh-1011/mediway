<?php

namespace App\Support;

use App\Models\MedicalVisit;
use Illuminate\Support\Collection;

final class PatientVisitServices
{
    public function entries(MedicalVisit $visit): Collection
    {
        $entries = $visit->serviceOrders->flatMap(fn ($order) => $order->items->map(function ($item) use ($order) {
            $group = $item->medicalService?->serviceGroups->sortBy('id')->first();

            return [
                'key' => (string) $item->id,
                'group' => $group ? 'nhom-'.$group->id : 'khac',
                'groupName' => $group?->name ?? 'Dịch vụ khác',
                'name' => $item->medicalService?->name ?? 'Dịch vụ y tế',
                'status' => $item->serviceStatus?->name,
                'statusLabel' => $item->serviceStatus?->description,
                'ordered' => $order->ordered_at,
                'started' => $item->started_at,
                'completed' => $item->completed_at,
                'room' => null,
                'performer' => $item->performer?->full_name,
                'price' => $item->price_at_order,
                'unit' => $item->medicalService?->unit,
                'result' => $item->result?->hasContent() ? $item->result : null,
            ];
        }));

        $fee = $visit->payables->firstWhere('payment_type', 'EXAMINATION_FEE');
        $completed = $visit->status?->name === 'COMPLETED';
        if ($fee || $visit->examination_started_at || $completed) {
            $entries->prepend([
                'key' => 'kham-benh',
                'group' => 'kham-benh',
                'groupName' => 'Khám bệnh',
                'name' => $visit->specialty?->name ? 'Khám '.$visit->specialty->name : 'Khám bệnh',
                'status' => match (true) {
                    $completed => 'COMPLETED',
                    $visit->status?->name === 'CANCELLED' => 'CANCELLED',
                    $visit->examination_started_at !== null => 'PROCESSING',
                    default => 'PENDING',
                },
                'statusLabel' => null,
                'ordered' => $visit->checked_in_at,
                'started' => $visit->examination_started_at,
                'completed' => $visit->examination_ended_at,
                'room' => $visit->examinationRoom?->name,
                'performer' => $visit->doctor?->full_name,
                'price' => $fee?->amount,
                'unit' => 'lượt',
                'result' => null,
            ]);
        }

        return $entries->values();
    }
}
