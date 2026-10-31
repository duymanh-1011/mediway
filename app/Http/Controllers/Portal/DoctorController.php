<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\BrowseDoctorScheduleRequest;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Specialty;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DoctorController extends Controller
{
    public function index(BrowseDoctorScheduleRequest $request): View
    {
        $filters = $request->validated();

        $specialtyId = filled($filters['specialty_id'] ?? null)
            ? (int) $filters['specialty_id'] : null;

        $doctorId = filled($filters['doctor_id'] ?? null)
            ? (int) $filters['doctor_id'] : null;

        $date = $filters['date'] ?? null;

        $specialties = Specialty::query()
            ->where('status', 'ACTIVE')
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'description',
                'pre_visit_instructions',
            ]);

        // Luon tai danh sach bac si day du cho dropdown.
        // Khong loc dropdown theo specialtyId tai database.
        $doctors = Doctor::query()
            ->with('specialty:id,name,status,is_general')
            ->where('status', 'ACTIVE')
            ->whereHas(
                'specialty',
                fn(Builder $q) =>
                $q->where('status', 'ACTIVE')
            )
            ->orderBy('full_name')
            ->get([
                'id',
                'specialty_id',
                'full_name',
                'status',
            ]);

        /*
         * Trang tra cuu:
         * Giu lai cac slot da day hoac da qua gio
         * TRONG NGAY HOM NAY de hien thi trang thai.
         *
         * Ngay truoc hom nay duoc loai bo tai query.
         */
        $schedules = $this->scheduleQuery(
            $specialtyId,
            $doctorId,
            $date
        )
            ->get()
            ->filter(function (DoctorSchedule $schedule) {
                // Do not advertise malformed slots that booking will reject.
                $schedule->setRelation('appointmentTimeSlots', $schedule->appointmentTimeSlots
                    ->filter(fn ($slot) => $slot->start_time < $slot->end_time
                        && $slot->start_time >= $schedule->start_time
                        && $slot->end_time <= $schedule->end_time
                        && (int) $slot->max_patients > 0)
                    ->values());

                return $schedule->appointmentTimeSlots->isNotEmpty();
            })
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

    public function show(Doctor $doctor): View
    {
        $doctor = Doctor::query()
            ->select([
                'id',
                'specialty_id',
                'full_name',
                'status',
            ])
            ->with(
                'specialty:id,name,description,pre_visit_instructions,status,is_general'
            )
            ->whereKey($doctor->id)
            ->where('status', 'ACTIVE')
            ->whereHas(
                'specialty',
                fn(Builder $q) =>
                $q->where('status', 'ACTIVE')
            )
            ->firstOrFail();

        $schedules = $this->availableSchedules(
            null,
            (int) $doctor->id,
            null
        );

        return view(
            'portal.doctors.show',
            compact('doctor', 'schedules')
        );
    }

    public function specialty(Specialty $specialty): View
    {
        $specialty = Specialty::query()
            ->select([
                'id',
                'name',
                'description',
                'pre_visit_instructions',
                'status',
                'is_general',
            ])
            ->whereKey($specialty->id)
            ->where('status', 'ACTIVE')
            ->firstOrFail();

        $doctors = Doctor::query()
            ->select([
                'id',
                'specialty_id',
                'full_name',
                'status',
            ])
            ->with('specialty:id,name,status,is_general')
            ->where('status', 'ACTIVE')
            ->whereHas(
                'specialty',
                fn(Builder $q) =>
                $q->where('status', 'ACTIVE')
            )
            ->forSpecialty((int) $specialty->id)
            ->orderBy('full_name')
            ->get();

        $schedules = $this->availableSchedules(
            (int) $specialty->id,
            null,
            null
        );

        return view(
            'portal.specialties.show',
            compact('specialty', 'doctors', 'schedules')
        );
    }

    /*
     * Query lich lam viec.
     *
     * - Chi lay lich ACTIVE
     * - Bac si, chuyen khoa va phong ACTIVE
     * - Khong lay ngay truoc hom nay
     * - Gioi han so ngay duoc dat truoc
     * - Dem so lich chua bi huy
     * - Giu ACTIVE va FULL de hien thi trang thai
     */
    private function scheduleQuery(
        ?int $specialtyId,
        ?int $doctorId,
        ?string $date
    ): Builder {
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
                'doctor:id,specialty_id,full_name,status',
                'specialty:id,name,status',
                'examinationRoom:id,name,status',

                'appointmentTimeSlots' => fn($query) =>
                $query
                    ->select([
                        'id',
                        'doctor_schedule_id',
                        'start_time',
                        'end_time',
                        'max_patients',
                        'status',
                    ])
                    ->whereIn(
                        'status',
                        ['ACTIVE', 'FULL']
                    )
                    ->withCount([
                        'appointments as booked_count' =>
                        fn($bookings) =>
                        $bookings->whereHas(
                            'appointmentStatus',
                            fn($status) =>
                            $status->where(
                                'name',
                                '!=',
                                'CANCELLED'
                            )
                        ),
                    ])
                    ->orderBy('start_time'),
            ])
            ->where('status', 'ACTIVE')
            ->whereHas(
                'doctor',
                fn(Builder $q) =>
                $q->where('status', 'ACTIVE')
            )
            ->whereHas(
                'specialty',
                fn(Builder $q) =>
                $q->where('status', 'ACTIVE')
            )
            ->whereHas(
                'examinationRoom',
                fn(Builder $q) =>
                $q->where('status', 'ACTIVE')
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
                $specialtyId !== null,
                fn(Builder $q) =>
                $q->where('specialty_id', $specialtyId)
            )
            ->when(
                $doctorId !== null,
                fn(Builder $q) =>
                $q->where('doctor_id', $doctorId)
            )
            ->when(
                $date !== null,
                fn(Builder $q) =>
                $q->whereDate('work_date', $date)
            )
            ->orderBy('work_date')
            ->orderBy('start_time');
    }

    /*
     * Trang chi tiet bac si / chuyen khoa van
     * chi hien thi cac slot con dat duoc.
     */
    private function availableSchedules(
        ?int $specialtyId = null,
        ?int $doctorId = null,
        ?string $date = null
    ): Collection {
        return $this->scheduleQuery(
            $specialtyId,
            $doctorId,
            $date
        )
            ->get()
            ->filter(function (DoctorSchedule $schedule) {
                $available = $schedule
                    ->appointmentTimeSlots
                    ->filter(function ($slot) use ($schedule) {
                        $startsAt = Carbon::parse(
                            $schedule->work_date
                                ->format('Y-m-d')
                                . ' '
                                . substr(
                                    (string) $slot->start_time,
                                    0,
                                    8
                                )
                        );

                        return $slot->start_time < $slot->end_time
                            && $slot->start_time >= $schedule->start_time
                            && $slot->end_time <= $schedule->end_time
                            && $slot->status === 'ACTIVE'
                            && $startsAt->isFuture()
                            && (int) $slot->max_patients > 0
                            && (int) $slot->booked_count
                            < (int) $slot->max_patients;
                    })
                    ->values();

                $schedule->setRelation(
                    'appointmentTimeSlots',
                    $available
                );

                return $available->isNotEmpty();
            })
            ->values();
    }
}
