<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\BrowseDoctorScheduleRequest;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Specialty;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DoctorController extends Controller
{
    public function index(
        BrowseDoctorScheduleRequest $request
    ): View {
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
        | Chuyên khoa ACTIVE
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
        | Bác sĩ ACTIVE
        |--------------------------------------------------------------------------
        |
        | Chỉ lấy các field cần cho Patient Portal.
        | Không expose doctor_code, user_id, date_of_birth, gender...
        |
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
                'status',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Lịch thực tế còn nhận đặt
        |--------------------------------------------------------------------------
        */

        $schedules =
            $this->availableSchedules(
                $specialtyId,
                $doctorId,
                $date
            );

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

    /*
    |--------------------------------------------------------------------------
    | M05.3 - Trang bác sĩ
    |--------------------------------------------------------------------------
    */

    public function show(
        Doctor $doctor
    ): View {
        $doctor =
            Doctor::query()
            ->select([
                'id',
                'specialty_id',
                'full_name',
                'status',
            ])
            ->with([
                /*
                |--------------------------------------------------------------------------
                | Chỉ lấy dữ liệu chuyên khoa được phép hiển thị cho Patient
                |--------------------------------------------------------------------------
                |
                | Không lấy specialty_code, notes, dữ liệu nội bộ.
                |
                | description + pre_visit_instructions cần có vì Blade trang
                | bác sĩ đang hiển thị hai trường này.
                |
                */
                'specialty:id,name,description,pre_visit_instructions,status,is_general',
            ])
            ->whereKey(
                $doctor->id
            )
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
            ->firstOrFail();

        $schedules =
            $this->availableSchedules(
                null,
                $doctor->id,
                null
            );

        return view(
            'portal.doctors.show',
            compact(
                'doctor',
                'schedules'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | M05.3 - Trang chuyên khoa
    |--------------------------------------------------------------------------
    */

    public function specialty(
        Specialty $specialty
    ): View {
        $specialty =
            Specialty::query()
            ->select([
                'id',
                'name',
                'description',
                'pre_visit_instructions',
                'status',
                'is_general',
            ])
            ->whereKey(
                $specialty->id
            )
            ->where(
                'status',
                'ACTIVE'
            )
            ->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | Bác sĩ có thể khám chuyên khoa này
        |--------------------------------------------------------------------------
        */

        $doctors =
            Doctor::query()
            ->select([
                'id',
                'specialty_id',
                'full_name',
                'status',
            ])
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
            ->forSpecialty(
                $specialty->id
            )
            ->orderBy(
                'full_name'
            )
            ->get();

        $schedules =
            $this->availableSchedules(
                $specialty->id,
                null,
                null
            );

        return view(
            'portal.specialties.show',
            compact(
                'specialty',
                'doctors',
                'schedules'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Lịch ACTIVE + slot ACTIVE + capacity + tương lai
    |--------------------------------------------------------------------------
    */

    private function availableSchedules(
        ?int $specialtyId = null,
        ?int $doctorId = null,
        ?string $date = null
    ): Collection {
        return DoctorSchedule::query()
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
                /*
                |--------------------------------------------------------------------------
                | Chỉ lấy dữ liệu cần thiết cho Portal
                |--------------------------------------------------------------------------
                */

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
                    today()
                        ->toDateString(),

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
                    /*
                    |--------------------------------------------------------------------------
                    | Loại slot đã qua hoặc đã đầy
                    |--------------------------------------------------------------------------
                    */

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
                                                (string) $slot->start_time,
                                                0,
                                                8
                                            )
                                    );

                                return
                                    $slotStart->isFuture()
                                    &&
                                    (int) $slot->booked_count
                                    <
                                    (int) $slot->max_patients;
                            }
                        )
                        ->values();

                    $schedule->setRelation(
                        'appointmentTimeSlots',
                        $availableSlots
                    );

                    return
                        $availableSlots
                        ->isNotEmpty();
                }
            )
            ->values();
    }
}
