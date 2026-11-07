<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Payable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $patient = $request->user()->patient;
        abort_unless($patient && $patient->status === 'ACTIVE', 404);

        $outstanding = Payable::query()->whereHas('medicalVisit', fn ($visit) => $visit->where('patient_id', $patient->id))
            ->outstandingForPatient();

        $amount = (clone $outstanding)->sum('amount');
        $payables = $outstanding->with(['medicalVisit:id,visit_code,appointment_id', 'medicalVisit.appointment', 'paymentStatus', 'serviceOrderItem.medicalService'])
            ->orderByDesc('generated_at')->orderByDesc('id')->get();

        return view('portal.payments.index', compact('payables', 'amount'));
    }
}
