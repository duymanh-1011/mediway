<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

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

        return view('portal.notifications', compact('categories', 'category'));
    }
}
