<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreExaminationRoomRequest;
use App\Http\Requests\Admin\UpdateExaminationRoomRequest;
use App\Models\ExaminationRoom;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExaminationRoomController extends Controller
{
    public function index(Request $request)
    {
        $keyword = trim(
            (string) $request->get('keyword')
        );

        $status =
            $request->get('status');

        $building = trim(
            (string) $request->get('building')
        );

        $floor = trim(
            (string) $request->get('floor')
        );

        $query =
            ExaminationRoom::query();

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($keyword !== '') {

            $query->where(
                function ($q) use ($keyword) {

                    $q->where(
                        'room_code',
                        'like',
                        "%{$keyword}%"
                    )
                    ->orWhere(
                        'name',
                        'like',
                        "%{$keyword}%"
                    )
                    ->orWhere(
                        'room_number',
                        'like',
                        "%{$keyword}%"
                    );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $status,
                ['ACTIVE', 'INACTIVE'],
                true
            )
        ) {
            $query->where(
                'status',
                $status
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Building
        |--------------------------------------------------------------------------
        */

        if ($building !== '') {
            $query->where(
                'building',
                $building
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Floor
        |--------------------------------------------------------------------------
        */

        if ($floor !== '') {
            $query->where(
                'floor',
                $floor
            );
        }

        $rooms =
            $query
                ->orderBy('building')
                ->orderBy('floor')
                ->orderBy('room_number')
                ->paginate(10)
                ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Filter options
        |--------------------------------------------------------------------------
        */

        $buildings =
            ExaminationRoom::query()
                ->whereNotNull('building')
                ->where('building', '!=', '')
                ->distinct()
                ->orderBy('building')
                ->pluck('building');

        $floors =
            ExaminationRoom::query()
                ->whereNotNull('floor')
                ->where('floor', '!=', '')
                ->distinct()
                ->orderBy('floor')
                ->pluck('floor');

        /*
        |--------------------------------------------------------------------------
        | Statistics
        |--------------------------------------------------------------------------
        */

        $totalRooms =
            ExaminationRoom::count();

        $activeRooms =
            ExaminationRoom::where(
                'status',
                'ACTIVE'
            )->count();

        $inactiveRooms =
            ExaminationRoom::where(
                'status',
                'INACTIVE'
            )->count();

        return view(
            'admin.examination-rooms.index',
            compact(
                'rooms',
                'keyword',
                'status',
                'building',
                'floor',
                'buildings',
                'floors',
                'totalRooms',
                'activeRooms',
                'inactiveRooms'
            )
        );
    }

    public function create()
    {
        return view(
            'admin.examination-rooms.create'
        );
    }

    public function store(
        StoreExaminationRoomRequest $request
    ) {
        $room =
            ExaminationRoom::create(
                $request->validated()
            );

        return redirect()
            ->route(
                'admin.examination-rooms.show',
                $room
            )
            ->with(
                'success',
                'Thêm phòng khám thành công.'
            );
    }

    public function show(
        ExaminationRoom $examinationRoom
    ) {
        /*
        |--------------------------------------------------------------------------
        | Schedule count
        |--------------------------------------------------------------------------
        */

        $scheduleCount =
            DB::table('doctor_schedules')
                ->where(
                    'examination_room_id',
                    $examinationRoom->id
                )
                ->count();

        /*
        |--------------------------------------------------------------------------
        | Appointment count
        |--------------------------------------------------------------------------
        */

        $appointmentCount =
            DB::table('appointments')
                ->where(
                    'examination_room_id',
                    $examinationRoom->id
                )
                ->count();

        /*
        |--------------------------------------------------------------------------
        | Visit count
        |--------------------------------------------------------------------------
        */

        $visitCount =
            DB::table('medical_visits')
                ->where(
                    'examination_room_id',
                    $examinationRoom->id
                )
                ->count();

        /*
        |--------------------------------------------------------------------------
        | Queue count
        |
        | examination_room_queues không chứa room_id.
        | Phòng được suy ra thông qua medical_visit.
        |--------------------------------------------------------------------------
        */

        $queueCount =
            DB::table('examination_room_queues')
                ->join(
                    'medical_visits',
                    'examination_room_queues.medical_visit_id',
                    '=',
                    'medical_visits.id'
                )
                ->where(
                    'medical_visits.examination_room_id',
                    $examinationRoom->id
                )
                ->count();

        /*
        |--------------------------------------------------------------------------
        | Recent schedules
        |--------------------------------------------------------------------------
        */

        $recentSchedules =
            DB::table('doctor_schedules')
                ->join(
                    'doctors',
                    'doctor_schedules.doctor_id',
                    '=',
                    'doctors.id'
                )
                ->join(
                    'specialties',
                    'doctor_schedules.specialty_id',
                    '=',
                    'specialties.id'
                )
                ->where(
                    'doctor_schedules.examination_room_id',
                    $examinationRoom->id
                )
                ->select([
                    'doctor_schedules.id',
                    'doctor_schedules.work_date',
                    'doctor_schedules.start_time',
                    'doctor_schedules.end_time',
                    'doctor_schedules.status',

                    'doctors.doctor_code',
                    'doctors.full_name as doctor_name',

                    'specialties.specialty_code',
                    'specialties.name as specialty_name',
                ])
                ->orderByDesc(
                    'doctor_schedules.work_date'
                )
                ->limit(5)
                ->get();

        /*
        |--------------------------------------------------------------------------
        | Recent visits
        |--------------------------------------------------------------------------
        */

        $recentVisits =
            DB::table('medical_visits')
                ->join(
                    'patients',
                    'medical_visits.patient_id',
                    '=',
                    'patients.id'
                )
                ->join(
                    'doctors',
                    'medical_visits.doctor_id',
                    '=',
                    'doctors.id'
                )
                ->join(
                    'visit_statuses',
                    'medical_visits.visit_status_id',
                    '=',
                    'visit_statuses.id'
                )
                ->where(
                    'medical_visits.examination_room_id',
                    $examinationRoom->id
                )
                ->select([
                    'medical_visits.id',
                    'medical_visits.visit_code',
                    'medical_visits.checked_in_at',

                    'patients.patient_code',
                    'patients.full_name as patient_name',

                    'doctors.doctor_code',
                    'doctors.full_name as doctor_name',

                    'visit_statuses.name as visit_status',
                ])
                ->orderByDesc(
                    'medical_visits.checked_in_at'
                )
                ->limit(5)
                ->get();

        return view(
            'admin.examination-rooms.show',
            compact(
                'examinationRoom',
                'scheduleCount',
                'appointmentCount',
                'visitCount',
                'queueCount',
                'recentSchedules',
                'recentVisits'
            )
        );
    }

    public function edit(
        ExaminationRoom $examinationRoom
    ) {
        return view(
            'admin.examination-rooms.edit',
            compact('examinationRoom')
        );
    }

    public function update(
        UpdateExaminationRoomRequest $request,
        ExaminationRoom $examinationRoom
    ) {
        $examinationRoom->update(
            $request->validated()
        );

        return redirect()
            ->route(
                'admin.examination-rooms.show',
                $examinationRoom
            )
            ->with(
                'success',
                'Cập nhật phòng khám thành công.'
            );
    }

    public function destroy(
        ExaminationRoom $examinationRoom
    ) {
        /*
        |--------------------------------------------------------------------------
        | Phòng đã có lịch bác sĩ?
        |--------------------------------------------------------------------------
        */

        $hasSchedules =
            DB::table('doctor_schedules')
                ->where(
                    'examination_room_id',
                    $examinationRoom->id
                )
                ->exists();

        /*
        |--------------------------------------------------------------------------
        | Phòng đã có appointment?
        |--------------------------------------------------------------------------
        */

        $hasAppointments =
            DB::table('appointments')
                ->where(
                    'examination_room_id',
                    $examinationRoom->id
                )
                ->exists();

        /*
        |--------------------------------------------------------------------------
        | Phòng đã có visit?
        |--------------------------------------------------------------------------
        */

        $hasVisits =
            DB::table('medical_visits')
                ->where(
                    'examination_room_id',
                    $examinationRoom->id
                )
                ->exists();

        /*
        |--------------------------------------------------------------------------
        | Đã sử dụng -> INACTIVE
        |--------------------------------------------------------------------------
        */

        if (
            $hasSchedules
            || $hasAppointments
            || $hasVisits
        ) {
            $examinationRoom->update([
                'status' => 'INACTIVE',
            ]);

            return redirect()
                ->route(
                    'admin.examination-rooms.index'
                )
                ->with(
                    'success',
                    'Phòng khám đã được sử dụng trong hệ thống nên không thể xóa. Hệ thống đã chuyển phòng sang trạng thái INACTIVE.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Chưa sử dụng -> hard delete
        |--------------------------------------------------------------------------
        */

        $examinationRoom->delete();

        return redirect()
            ->route(
                'admin.examination-rooms.index'
            )
            ->with(
                'success',
                'Xóa phòng khám thành công.'
            );
    }
}