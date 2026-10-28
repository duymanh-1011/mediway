<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\ExaminationRoomQueue;
use App\Models\Patient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class QueueController extends Controller
{
    public function index(): View
    {
        $patient =
            $this->currentPatient();

        /*
        |--------------------------------------------------------------------------
        | Lịch khám của chính bệnh nhân
        |--------------------------------------------------------------------------
        */

        $appointments =
            Appointment::query()
            ->where(
                'patient_id',
                $patient->id
            )
            ->with([
                'doctor',
                'specialty',
                'examinationRoom',
                'appointmentTimeSlot',
                'appointmentStatus',
                'medicalVisit',
            ])
            ->orderByDesc(
                'appointment_date'
            )
            ->orderByDesc(
                'id'
            )
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Hàng đợi của chính bệnh nhân
        |--------------------------------------------------------------------------
        |
        | Scope qua medical_visits.patient_id.
        |
        | Không phụ thuộc appointment vì medical_visit có thể được tạo
        | trực tiếp tại quầy mà không có appointment_id.
        |
        */

        $queues =
            ExaminationRoomQueue::query()
            ->whereHas(
                'medicalVisit',
                fn($query) =>
                $query->where(
                    'patient_id',
                    $patient->id
                )
            )
            ->with([
                'status',

                /*
                    |--------------------------------------------------------------------------
                    | Dữ liệu thực tế của lượt khám
                    |--------------------------------------------------------------------------
                    */

                'medicalVisit.doctor:id,full_name',

                'medicalVisit.specialty:id,name',

                'medicalVisit.examinationRoom:id,name',

                'medicalVisit.status:id,name,description',
            ])
            ->orderByDesc(
                'queued_at'
            )
            ->orderByDesc(
                'id'
            )
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Appointment map
        |--------------------------------------------------------------------------
        |
        | Chỉ dùng bổ sung context nếu lượt khám được tạo từ lịch hẹn.
        |
        */

        $appointmentsById =
            $appointments->keyBy(
                'id'
            );

        /*
        |--------------------------------------------------------------------------
        | M08.3 - Summary để handoff cho Dashboard
        |--------------------------------------------------------------------------
        */

        $summary = [
            'appointment_count' =>
            $appointments->count(),

            'today_count' =>
            $appointments
                ->filter(
                    fn(Appointment $appointment) =>
                    $appointment
                        ->appointment_date
                        ?->isToday()
                        &&
                        $appointment
                        ->appointmentStatus
                        ?->name
                        !==
                        'CANCELLED'
                )
                ->count(),

            'queue_count' =>
            $queues->count(),

            'waiting_queue_count' =>
            $queues
                ->filter(
                    fn(ExaminationRoomQueue $queue) =>
                    $queue->completed_at === null
                )
                ->count(),
        ];

        return view(
            'portal.queues.index',
            compact(
                'appointments',
                'appointmentsById',
                'queues',
                'summary'
            )
        );
    }

    private function currentPatient(): Patient
    {
        $user =
            Auth::user();

        $user?->loadMissing(
            'patient'
        );

        $patient =
            $user?->patient;

        if (
            ! $patient
            ||
            $patient->status
            !==
            'ACTIVE'
        ) {
            throw ValidationException::withMessages([
                'auth' =>
                'Hồ sơ bệnh nhân không còn đủ điều kiện sử dụng Patient Portal.',
            ]);
        }

        return $patient;
    }
}
