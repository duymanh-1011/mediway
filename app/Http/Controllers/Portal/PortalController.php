<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use App\Models\Appointment;
use App\Models\ExaminationRoomQueue;
use App\Models\Payable;
use App\Models\ServiceResult;

class PortalController extends Controller
{
    public function placeholder(Request $request)
    {
        $patient = $request->user()?->patient;
        abort_unless($patient && $request->user()->canAccessPortal(), 403);

        return response()->view('portal.home', compact('patient'))->header('Cache-Control', 'private, no-store');
    }

    public function appointments(Request $request)
    {
        $patient = $request->user()?->patient;
        abort_unless($patient && $request->user()->canAccessPortal(), 403);
        $appointment = null;
        $unavailable = false;

        try {
            $appointment = $patient->appointments()
                ->select('appointments.*')
                ->join('appointment_time_slots', 'appointment_time_slots.id', '=', 'appointments.appointment_time_slot_id')
                ->whereHas('appointmentStatus', fn ($q) => $q->whereIn('name', ['BOOKED', 'CHECKED_IN', 'IN_EXAMINATION']))
                ->where(fn ($q) => $q->whereDate('appointment_date', '>', today())
                    ->orWhere(fn ($q) => $q->whereDate('appointment_date', today())->where('appointment_time_slots.end_time', '>=', now()->format('H:i:s'))))
                ->orderBy('appointment_date')->orderBy('appointment_time_slots.start_time')->orderBy('appointments.id')
                ->with(['appointmentStatus', 'appointmentTimeSlot', 'doctor', 'specialty', 'examinationRoom'])->first();
        } catch (QueryException $e) {
            report($e);
            $appointment = null;
            $unavailable = true;
        }

        return response()->view('portal.appointments', compact('appointment', 'unavailable', 'patient'))
            ->header('Cache-Control', 'private, no-store');
    }

    public function notifications(Request $request)
    {
        $categories = ['tat-ca' => 'Tất cả', 'he-thong' => 'Hệ thống', 'lich-hen' => 'Lịch hẹn', 'kham-benh' => 'Khám bệnh', 'ket-qua' => 'Kết quả', 'thanh-toan' => 'Thanh toán'];
        $category = $request->query('danh-muc', 'tat-ca');
        $category = is_string($category) && array_key_exists($category, $categories) ? $category : 'tat-ca';
        $patient = $request->user()->patient;
        $items = collect();

        if (in_array($category, ['tat-ca', 'lich-hen'], true)) {
            $appointments = Appointment::where('patient_id', $patient->id)->with(['appointmentStatus', 'appointmentTimeSlot'])
                ->orderByDesc('updated_at')->limit(20)->get();
            foreach ($appointments as $appointment) {
                $items->push(['category' => 'Lịch hẹn', 'title' => 'Lịch hẹn '.$appointment->appointment_code,
                    'description' => \App\Models\AdminDisplay::label($appointment->displayStatus()),
                    'time' => $appointment->updated_at, 'url' => route('patient.appointments.show', $appointment)]);
            }
        }
        if (in_array($category, ['tat-ca', 'kham-benh'], true)) {
            $queues = ExaminationRoomQueue::whereHas('medicalVisit', fn ($q) => $q->where('patient_id', $patient->id))
                ->whereHas('status', fn ($q) => $q->where('name', 'CALLED'))
                ->whereNotNull('called_at')->orderByDesc('called_at')->limit(20)->get();
            foreach ($queues as $queue) {
                $items->push(['category' => 'Khám bệnh', 'title' => 'Đã đến lượt khám',
                    'description' => 'Vui lòng kiểm tra hàng đợi và đến phòng khám theo hướng dẫn.',
                    'time' => $queue->called_at, 'url' => route('patient.queues.index')]);
            }
        }
        if (in_array($category, ['tat-ca', 'ket-qua'], true)) {
            $results = ServiceResult::whereHas('serviceOrderItem.serviceOrder.medicalVisit', fn ($q) => $q->where('patient_id', $patient->id))
                ->where(fn ($q) => $q->whereNotNull('result_summary')->orWhereNotNull('result_conclusion')
                    ->orWhereNotNull('result_parameters')->orWhereNotNull('result_pdf_path')->orWhereNotNull('result_image_path'))
                ->with('serviceOrderItem.medicalService')->orderByDesc('updated_at')->limit(20)->get();
            foreach ($results as $result) {
                $items->push(['category' => 'Kết quả', 'title' => 'Có kết quả dịch vụ',
                    'description' => $result->serviceOrderItem?->medicalService?->name ?? 'Kết quả khám bệnh',
                    'time' => $result->resulted_at ?? $result->updated_at, 'url' => route('patient.results')]);
            }
        }
        if (in_array($category, ['tat-ca', 'thanh-toan'], true)) {
            $payables = Payable::whereHas('medicalVisit', fn ($q) => $q->where('patient_id', $patient->id))
                ->whereHas('paymentStatus', fn ($q) => $q->whereIn('name', ['UNPAID', 'PENDING']))
                ->orderByDesc('generated_at')->limit(20)->get();
            foreach ($payables as $payable) {
                $items->push(['category' => 'Thanh toán', 'title' => 'Khoản cần thanh toán',
                    'description' => $payable->payable_code.' · '.number_format((float) $payable->amount, 0, ',', '.').' đ',
                    'time' => $payable->generated_at ?? $payable->created_at, 'url' => route('patient.payments.index')]);
            }
        }

        $items = $items->sortByDesc(fn ($item) => $item['time']?->timestamp ?? 0)->take(50)->values();
        return view('portal.notifications', compact('categories', 'category', 'items'));
    }
}
