<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\MedicalVisit;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReceptionSlipController extends Controller
{
    public function __invoke(Request $request, MedicalVisit $visit): Response
    {
        abort_unless($visit->checked_in_at && ($visit->appointment_id || $visit->reception_source === 'WALK_IN'), 404);
        $visit->load(['patient.user', 'patient.detail', 'appointment', 'examinationRoom', 'clinicalNote:id,medical_visit_id,reason_for_visit']);
        $fee = $visit->payables()->where('payment_type', 'EXAMINATION_FEE')->with('paymentStatus')->oldest('id')->first();
        $queueNumber = $visit->issuedQueueNumber();
        $receptionistName = $this->receptionistName($visit);
        $backUrl = $request->routeIs('admin.*') ? route('admin.visits.show', $visit)
            : ($visit->appointment_id ? route('employee.appointments.show', $visit->appointment_id) : route('employee.walk-in.show', $visit));

        return response()->view('print.reception-slip', compact('visit', 'fee', 'queueNumber', 'backUrl', 'receptionistName'))
            ->header('Cache-Control', 'private, no-store');
    }

    private function receptionistName(MedicalVisit $visit): ?string
    {
        $appointment = $visit->appointment;
        $checkIn = AuditLog::where('action', 'CHECK_IN')
            ->where('entity_type', $appointment ? 'Appointment' : 'MedicalVisit')
            ->where('entity_id', $appointment?->id ?? $visit->id)
            ->where('entity_business_code', $appointment?->appointment_code ?? $visit->visit_code)
            ->with(['user.employee', 'user.administrator'])
            ->oldest('id')->first();

        return match ($checkIn?->role_name_snapshot) {
            'Employee' => $checkIn->user?->employee?->full_name,
            'Administrator' => $checkIn->user?->administrator?->full_name,
            default => null,
        };
    }
}
