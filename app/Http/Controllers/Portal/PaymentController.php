<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Payable;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $patient = $request->user()->patient;
        abort_unless($patient && $patient->status === 'ACTIVE', 404);

        $outstanding = Payable::query()->whereHas('medicalVisit', fn ($visit) => $visit->where('patient_id', $patient->id))
            ->outstandingForPatient();

        $payables = $outstanding->with(['medicalVisit:id,visit_code,appointment_id', 'medicalVisit.appointment', 'paymentStatus', 'serviceOrderItem.medicalService'])
            ->orderByDesc('generated_at')->orderByDesc('id')->get();
        $amount = $payables->sum('amount');
        $snapshot = hash('sha256', $payables->map(fn ($payable) => [
            $payable->id, $payable->amount, $payable->payment_status_id,
        ])->toJson());

        if ($request->expectsJson()) {
            return response()->json(['snapshot' => $snapshot])->header('Cache-Control', 'private, no-store');
        }

        return view('portal.payments.index', compact('payables', 'amount', 'snapshot'));
    }
}
