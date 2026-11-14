<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Payable;
use App\Models\Prescription;
use App\Services\PrescriptionPayableService;
use Illuminate\Http\Request;

class PrescriptionPurchaseController extends Controller
{
    private function owned(Request $request, int $id): Prescription
    {
        $patient = $request->user()->patient;
        abort_unless($patient && $patient->status === 'ACTIVE', 404);
        return Prescription::whereHas('medicalVisit', fn ($q) => $q->where('patient_id', $patient->id))
            ->with(['medicalVisit.status', 'items.medicine'])->findOrFail($id);
    }

    public function show(Request $request, int $prescription)
    {
        $record = $this->owned($request, $prescription);
        $payable = Payable::with('paymentStatus')->where('prescription_id', $record->id)
            ->whereDoesntHave('paymentStatus', fn ($q) => $q->where('name', 'CANCELLED'))->latest('id')->first();
        $priceValid = $record->pricedTotal() !== null && (float) $record->pricedTotal() > 0
            && ! $record->items->contains(fn ($item) => $item->unit_price === null || $item->line_total === null
                || (float) $item->unit_price < 0 || (float) $item->line_total < 0 || (float) $item->quantity <= 0);
        return response()->view('portal.clinical.prescription-purchase', compact('record', 'payable', 'priceValid'))
            ->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request, int $prescription, PrescriptionPayableService $service)
    {
        $record = $this->owned($request, $prescription);
        $request->validate(['confirm_purchase' => ['accepted']]);
        $service->create($record, $request, (int) $request->user()->patient->id);
        return redirect()->route('patient.prescriptions.purchase.show', $record)->with('success', 'Đã ghi nhận yêu cầu mua thuốc. Vui lòng kiểm tra trạng thái khoản thu.');
    }
}
