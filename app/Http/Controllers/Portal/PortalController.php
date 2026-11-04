<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use App\Models\Appointment;
use App\Models\ExaminationRoomQueue;
use App\Models\Payable;
use App\Models\ServiceResult;
use App\Models\VisitConclusion;
use App\Models\Prescription;
use App\Models\AuditLog;
use App\Models\Patient;
use Illuminate\Support\Collection;

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
                ->join('doctor_schedules', 'doctor_schedules.id', '=', 'appointment_time_slots.doctor_schedule_id')
                ->whereHas('appointmentStatus', fn ($q) => $q->whereIn('name', ['BOOKED', 'CHECKED_IN', 'IN_EXAMINATION']))
                ->where(fn ($q) => $q->whereDate('appointment_date', '>', today())
                    ->orWhere(fn ($q) => $q->whereDate('appointment_date', today())->where(fn ($q) => $q
                        ->whereHas('appointmentStatus', fn ($status) => $status->whereIn('name', ['CHECKED_IN', 'IN_EXAMINATION']))
                        ->orWhere('doctor_schedules.end_time', '>', now()->format('H:i:s')))))
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
        if ($request->expectsJson()) $category = 'tat-ca';
        $patient = $request->user()->patient;
        $items = $this->notificationItems($patient, $category);
        $readKeys = AuditLog::where('user_id', $request->user()->id)->where('action', 'PATIENT_NOTIFICATION_READ')
            ->where('entity_type', 'patient_notification')->where('entity_id', $patient->id)
            ->whereIn('entity_business_code', $items->pluck('key'))->pluck('entity_business_code')->all();
        $items = $items->reject(fn ($item) => in_array($item['key'], $readKeys, true))->take(50)->values();
        $callItem = $items->first(fn ($item) => isset($item['call']));
        $activeCall = $callItem ? [...$callItem['call'], 'key' => $callItem['key']] : null;
        if ($request->expectsJson()) {
            return response()->json(['count' => $items->count(), 'active_call' => $activeCall]);
        }
        return view('portal.notifications', compact('categories', 'category', 'items'));
    }

    public function openNotification(Request $request)
    {
        $data = $request->validate(['notification_key' => ['required', 'string', 'regex:/^[a-f0-9]{60}$/']]);
        $patient = $request->user()->patient;
        $item = $this->notificationItems($patient, 'tat-ca')->firstWhere('key', $data['notification_key']);
        abort_unless($item, 404);
        AuditLog::firstOrCreate([
            'user_id' => $request->user()->id, 'action' => 'PATIENT_NOTIFICATION_READ',
            'entity_type' => 'patient_notification', 'entity_id' => $patient->id,
            'entity_business_code' => $item['key'],
        ], [
            'role_name_snapshot' => 'Patient', 'description' => 'Bệnh nhân đã đọc thông báo.',
            'performed_at' => now(), 'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
        ]);
        return redirect()->to($item['url']);
    }

    private function notificationItems(Patient $patient, string $category): Collection
    {
        $items = collect();

        if (in_array($category, ['tat-ca', 'lich-hen'], true)) {
            $appointments = Appointment::where('patient_id', $patient->id)->with(['appointmentStatus', 'appointmentTimeSlot'])
                ->orderByDesc('updated_at')->limit(20)->get();
            foreach ($appointments as $appointment) {
                $items->push(['key' => $this->notificationKey('appointment', $appointment->id, $appointment->updated_at.'|'.$appointment->displayStatus()), 'category' => 'Lịch hẹn', 'title' => 'Lịch hẹn '.$appointment->appointment_code,
                    'description' => \App\Models\AdminDisplay::label($appointment->displayStatus()),
                    'time' => $appointment->updated_at, 'url' => route('patient.appointments.show', $appointment)]);
            }
        }
        if (in_array($category, ['tat-ca', 'kham-benh'], true)) {
            $queues = ExaminationRoomQueue::whereHas('medicalVisit', fn ($q) => $q->where('patient_id', $patient->id))
                ->whereHas('status', fn ($q) => $q->where('name', 'CALLED'))
                ->whereNotNull('called_at')->with('medicalVisit.examinationRoom')->orderByDesc('called_at')->limit(20)->get();
            foreach ($queues as $queue) {
                $room = $queue->medicalVisit?->examinationRoom?->name;
                $message = $room ? 'Vui lòng đến '.$room.' theo hướng dẫn.' : 'Vui lòng kiểm tra hàng đợi và đến phòng khám theo hướng dẫn.';
                $items->push(['key' => $this->notificationKey('queue', $queue->id, $queue->called_at.'|'.$queue->call_count), 'call' => ['message' => $message, 'url' => route('patient.queues.index')], 'category' => 'Khám bệnh', 'title' => 'Đã đến lượt khám',
                    'description' => $message,
                    'time' => $queue->called_at, 'url' => route('patient.queues.index')]);
            }
        }
        if (in_array($category, ['tat-ca', 'ket-qua'], true)) {
            $results = ServiceResult::whereHas('serviceOrderItem.serviceOrder.medicalVisit', fn ($q) => $q->where('patient_id', $patient->id))
                ->where(fn ($q) => $q->whereNotNull('result_summary')->orWhereNotNull('result_conclusion')
                    ->orWhereNotNull('result_parameters')->orWhereNotNull('result_pdf_path')->orWhereNotNull('result_image_path'))
                ->with('serviceOrderItem.medicalService')->orderByDesc('updated_at')->limit(20)->get();
            foreach ($results as $result) {
                $items->push(['key' => $this->notificationKey('result', $result->id, $result->updated_at), 'category' => 'Kết quả', 'title' => 'Có kết quả dịch vụ',
                    'description' => $result->serviceOrderItem?->medicalService?->name ?? 'Kết quả khám bệnh',
                    'time' => $result->resulted_at ?? $result->updated_at, 'url' => route('patient.results')]);
            }
            $conclusions = VisitConclusion::whereHas('medicalVisit', fn ($q) => $q->where('patient_id', $patient->id))
                ->orderByDesc('concluded_at')->limit(20)->get();
            foreach ($conclusions as $conclusion) {
                $items->push(['key' => $this->notificationKey('conclusion', $conclusion->id, $conclusion->updated_at), 'category' => 'Kết quả', 'title' => 'Có kết luận khám bệnh',
                    'description' => 'Bạn có thể xem kết luận trong kết quả khám bệnh.',
                    'time' => $conclusion->concluded_at ?? $conclusion->created_at, 'url' => route('patient.results')]);
            }
            $prescriptions = Prescription::whereHas('medicalVisit', fn ($q) => $q->where('patient_id', $patient->id))
                ->where('status', 'ACTIVE')->orderByDesc('prescribed_at')->limit(20)->get();
            foreach ($prescriptions as $prescription) {
                $items->push(['key' => $this->notificationKey('prescription', $prescription->id, $prescription->updated_at), 'category' => 'Kết quả', 'title' => 'Có đơn thuốc',
                    'description' => 'Đơn thuốc '.$prescription->prescription_code,
                    'time' => $prescription->prescribed_at ?? $prescription->created_at, 'url' => route('patient.prescriptions')]);
            }
        }
        if (in_array($category, ['tat-ca', 'thanh-toan'], true)) {
            $payables = Payable::whereHas('medicalVisit', fn ($q) => $q->where('patient_id', $patient->id))
                ->whereHas('paymentStatus', fn ($q) => $q->whereIn('name', ['UNPAID', 'PENDING']))
                ->orderByDesc('generated_at')->limit(20)->get();
            foreach ($payables as $payable) {
                $items->push(['key' => $this->notificationKey('payable', $payable->id, $payable->updated_at), 'category' => 'Thanh toán', 'title' => 'Khoản cần thanh toán',
                    'description' => $payable->payable_code.' · '.number_format((float) $payable->amount, 0, ',', '.').' đ',
                    'time' => $payable->generated_at ?? $payable->created_at, 'url' => route('patient.payments.index')]);
            }
        }

        return $items->sortByDesc(fn ($item) => $item['time']?->timestamp ?? 0)->values();
    }

    private function notificationKey(string $type, int $id, mixed $version): string
    {
        return substr(hash('sha256', $type.'|'.$id.'|'.(string) $version), 0, 60);
    }
}
