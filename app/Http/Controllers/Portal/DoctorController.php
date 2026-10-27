<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\BrowseDoctorScheduleRequest;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Specialty;
use Carbon\Carbon;

class DoctorController extends Controller
{
    public function index(BrowseDoctorScheduleRequest $request)
    {
        $data = $request->validated();

        $specialtyId =
            isset($data['specialty_id'])
            ? (int) $data['specialty_id']
            : null;

        $doctorId =
            isset($data['doctor_id'])
            ? (int) $data['doctor_id']
            : null;

        $date =
            $data['date']
            ?? null;

        /*
        |--------------------------------------------------------------------------
        | Chuyên khoa đang hoạt động
        |--------------------------------------------------------------------------
        */

        $specialties =
            Specialty::query()
            ->where(
                'status',
                'ACTIVE'
            )
            ->orderBy(
                'name'
            )
            ->get([
                'id',
                'name',
                'description',
                'pre_visit_instructions',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Bác sĩ đang hoạt động
        |--------------------------------------------------------------------------
        */

        $doctors =
            Doctor::query()
            ->with([
                'specialty:id,name,status,is_general',
            ])
            ->where(
                'status',
                'ACTIVE'
            )
            ->whereHas(
                'specialty',
                fn($query) =>
                $query->where(
                    'status',
                    'ACTIVE'
                )
            )
            ->when(
                $specialtyId,
                fn($query) =>
                $query->forSpecialty(
                    $specialtyId
                )
            )
            ->orderBy(
                'full_name'
            )
            ->get([
                'id',
                'specialty_id',
                'full_name',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Lịch và slot thực tế còn nhận đặt
        |--------------------------------------------------------------------------
        */

        $schedules =
            DoctorSchedule::query()
            ->select([
                'id',
                'doctor_id',
                'specialty_id',
                'examination_room_id',
                'work_date',
                'start_time',
                'end_time',
                'status',
            ])
            ->withQualifiedDoctor()
            ->with([
                'doctor:id,specialty_id,full_name,status',

                'specialty:id,name,status',

                'examinationRoom:id,name,status',

                'appointmentTimeSlots' =>
                fn($query) =>
                $query
                    ->select([
                        'id',
                        'doctor_schedule_id',
                        'start_time',
                        'end_time',
                        'max_patients',
                        'status',
                    ])
                    ->where(
                        'status',
                        'ACTIVE'
                    )
                    ->withCount([
                        'appointments as booked_count' =>
                        fn($bookings) =>
                        $bookings
                            ->whereHas(
                                'appointmentStatus',
                                fn($status) =>
                                $status->where(
                                    'name',
                                    '!=',
                                    'CANCELLED'
                                )
                            ),
                    ])
                    ->orderBy(
                        'start_time'
                    ),
            ])
            ->where(
                'status',
                'ACTIVE'
            )
            ->whereHas(
                'doctor',
                fn($query) =>
                $query->where(
                    'status',
                    'ACTIVE'
                )
            )
            ->whereHas(
                'specialty',
                fn($query) =>
                $query->where(
                    'status',
                    'ACTIVE'
                )
            )
            ->whereHas(
                'examinationRoom',
                fn($query) =>
                $query->where(
                    'status',
                    'ACTIVE'
                )
            )
            ->whereBetween(
                'work_date',
                [
                    today()->toDateString(),

                    today()
                        ->addDays(
                            Appointment::BOOKING_ADVANCE_DAYS
                        )
                        ->toDateString(),
                ]
            )
            ->when(
                $specialtyId,
                fn($query) =>
                $query->where(
                    'specialty_id',
                    $specialtyId
                )
            )
            ->when(
                $doctorId,
                fn($query) =>
                $query->where(
                    'doctor_id',
                    $doctorId
                )
            )
            ->when(
                $date,
                fn($query) =>
                $query->whereDate(
                    'work_date',
                    $date
                )
            )
            ->orderBy(
                'work_date'
            )
            ->orderBy(
                'start_time'
            )
            ->get()
            ->filter(
                function (
                    DoctorSchedule $schedule
                ) {
                    $availableSlots =
                        $schedule
                        ->appointmentTimeSlots
                        ->filter(
                            function (
                                $slot
                            ) use (
                                $schedule
                            ) {
                                $slotStart =
                                    Carbon::parse(
                                        $schedule
                                            ->work_date
                                            ->format(
                                                'Y-m-d'
                                            )
                                            .
                                            ' '
                                            .
                                            substr(
                                                $slot->start_time,
                                                0,
                                                8
                                            )
                                    );

                                return
                                    $slotStart
                                    ->isFuture()
                                    &&
                                    (int) $slot
                                        ->booked_count
                                    <
                                    (int) $slot
                                        ->max_patients;
                            }
                        )
                        ->values();

                    $schedule
                        ->setRelation(
                            'appointmentTimeSlots',
                            $availableSlots
                        );

                    return
                        $availableSlots
                        ->isNotEmpty();
                }
            )
            ->values();

        return view(
            'portal.doctors.index',
            compact(
                'specialties',
                'doctors',
                'schedules',
                'specialtyId',
                'doctorId',
                'date'
            )
        );
    }
}
