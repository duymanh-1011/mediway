<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSpecialtyRequest;
use App\Http\Requests\Admin\UpdateSpecialtyRequest;
use App\Models\Specialty;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SpecialtyController extends Controller
{
    public function index(Request $request)
    {
        $keyword = trim(
            (string) $request->get('keyword')
        );

        $status = $request->get('status');

        $query = Specialty::query();

        /*
        |--------------------------------------------------------------------------
        | Tìm kiếm
        |--------------------------------------------------------------------------
        */

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where(
                    'specialty_code',
                    'like',
                    "%{$keyword}%"
                )
                ->orWhere(
                    'name',
                    'like',
                    "%{$keyword}%"
                );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Lọc trạng thái
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
        | Phân trang
        |--------------------------------------------------------------------------
        */

        $specialties = $query
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Thống kê nhanh
        |--------------------------------------------------------------------------
        */

        $totalSpecialties =
            Specialty::count();

        $activeSpecialties =
            Specialty::where(
                'status',
                'ACTIVE'
            )->count();

        $inactiveSpecialties =
            Specialty::where(
                'status',
                'INACTIVE'
            )->count();

        return view(
            'admin.specialties.index',
            compact(
                'specialties',
                'keyword',
                'status',
                'totalSpecialties',
                'activeSpecialties',
                'inactiveSpecialties'
            )
        );
    }

    public function create()
    {
        return view(
            'admin.specialties.create'
        );
    }

    public function store(
        StoreSpecialtyRequest $request
    ) {
        DB::transaction(
            function () use ($request) {

                $data = $request->validated();

                unset($data['image']);

                /*
                |--------------------------------------------------------------------------
                | Upload ảnh
                |--------------------------------------------------------------------------
                */

                if ($request->hasFile('image')) {
                    $data['image_path'] =
                        $request
                            ->file('image')
                            ->store(
                                'specialties',
                                'public'
                            );
                }

                Specialty::create($data);
            }
        );

        return redirect()
            ->route(
                'admin.specialties.index'
            )
            ->with(
                'success',
                'Thêm chuyên khoa thành công.'
            );
    }

    public function show(
        Specialty $specialty
    ) {
        /*
        |--------------------------------------------------------------------------
        | Thống kê dữ liệu liên quan
        |--------------------------------------------------------------------------
        */

        $scheduleCount =
            DB::table('doctor_schedules')
                ->where(
                    'specialty_id',
                    $specialty->id
                )
                ->count();

        $appointmentCount =
            DB::table('appointments')
                ->where(
                    'specialty_id',
                    $specialty->id
                )
                ->count();

        $visitCount =
            DB::table('medical_visits')
                ->where(
                    'specialty_id',
                    $specialty->id
                )
                ->count();

        /*
        |--------------------------------------------------------------------------
        | Lịch bác sĩ gần đây
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
                    'examination_rooms',
                    'doctor_schedules.examination_room_id',
                    '=',
                    'examination_rooms.id'
                )
                ->where(
                    'doctor_schedules.specialty_id',
                    $specialty->id
                )
                ->select([
                    'doctor_schedules.id',
                    'doctor_schedules.work_date',
                    'doctor_schedules.start_time',
                    'doctor_schedules.end_time',
                    'doctor_schedules.status',

                    'doctors.doctor_code',
                    'doctors.full_name',

                    'examination_rooms.room_code',
                    'examination_rooms.name as room_name',
                ])
                ->orderByDesc(
                    'doctor_schedules.work_date'
                )
                ->limit(5)
                ->get();

        /*
        |--------------------------------------------------------------------------
        | Lịch hẹn gần đây
        |--------------------------------------------------------------------------
        */

        $recentAppointments =
            DB::table('appointments')
                ->join(
                    'patients',
                    'appointments.patient_id',
                    '=',
                    'patients.id'
                )
                ->join(
                    'doctors',
                    'appointments.doctor_id',
                    '=',
                    'doctors.id'
                )
                ->join(
                    'appointment_statuses',
                    'appointments.appointment_status_id',
                    '=',
                    'appointment_statuses.id'
                )
                ->where(
                    'appointments.specialty_id',
                    $specialty->id
                )
                ->select([
                    'appointments.id',
                    'appointments.appointment_code',
                    'appointments.appointment_date',

                    'patients.patient_code',
                    'patients.full_name as patient_name',

                    'doctors.doctor_code',
                    'doctors.full_name as doctor_name',

                    'appointment_statuses.name as appointment_status',
                ])
                ->orderByDesc(
                    'appointments.appointment_date'
                )
                ->limit(5)
                ->get();

        return view(
            'admin.specialties.show',
            compact(
                'specialty',
                'scheduleCount',
                'appointmentCount',
                'visitCount',
                'recentSchedules',
                'recentAppointments'
            )
        );
    }

    public function edit(
        Specialty $specialty
    ) {
        return view(
            'admin.specialties.edit',
            compact('specialty')
        );
    }

    public function update(
        UpdateSpecialtyRequest $request,
        Specialty $specialty
    ) {
        DB::transaction(
            function () use (
                $request,
                $specialty
            ) {
                $data =
                    $request->validated();

                unset(
                    $data['image'],
                    $data['remove_image']
                );

                /*
                |--------------------------------------------------------------------------
                | Xóa ảnh cũ nếu người dùng yêu cầu
                |--------------------------------------------------------------------------
                */

                if (
                    $request->boolean(
                        'remove_image'
                    )
                    && $specialty->image_path
                ) {
                    Storage::disk('public')
                        ->delete(
                            $specialty->image_path
                        );

                    $data['image_path'] = null;
                }

                /*
                |--------------------------------------------------------------------------
                | Thay ảnh mới
                |--------------------------------------------------------------------------
                */

                if ($request->hasFile('image')) {

                    if (
                        $specialty->image_path
                    ) {
                        Storage::disk('public')
                            ->delete(
                                $specialty->image_path
                            );
                    }

                    $data['image_path'] =
                        $request
                            ->file('image')
                            ->store(
                                'specialties',
                                'public'
                            );
                }

                $specialty->update($data);
            }
        );

        return redirect()
            ->route(
                'admin.specialties.show',
                $specialty
            )
            ->with(
                'success',
                'Cập nhật chuyên khoa thành công.'
            );
    }

    public function destroy(
        Specialty $specialty
    ) {
        /*
        |--------------------------------------------------------------------------
        | Kiểm tra chuyên khoa đã được sử dụng chưa
        |--------------------------------------------------------------------------
        */

        $hasSchedules =
            DB::table('doctor_schedules')
                ->where(
                    'specialty_id',
                    $specialty->id
                )
                ->exists();

        $hasAppointments =
            DB::table('appointments')
                ->where(
                    'specialty_id',
                    $specialty->id
                )
                ->exists();

        $hasVisits =
            DB::table('medical_visits')
                ->where(
                    'specialty_id',
                    $specialty->id
                )
                ->exists();

        /*
        |--------------------------------------------------------------------------
        | Nếu đã được tham chiếu -> INACTIVE
        |--------------------------------------------------------------------------
        */

        if (
            $hasSchedules
            || $hasAppointments
            || $hasVisits
        ) {
            $specialty->update([
                'status' => 'INACTIVE',
            ]);

            return redirect()
                ->route(
                    'admin.specialties.index'
                )
                ->with(
                    'success',
                    'Chuyên khoa đã được sử dụng trong hệ thống nên không thể xóa. Hệ thống đã chuyển chuyên khoa sang trạng thái INACTIVE.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Chưa được sử dụng -> cho phép xóa
        |--------------------------------------------------------------------------
        */

        DB::transaction(
            function () use ($specialty) {

                if ($specialty->image_path) {
                    Storage::disk('public')
                        ->delete(
                            $specialty->image_path
                        );
                }

                $specialty->delete();
            }
        );

        return redirect()
            ->route(
                'admin.specialties.index'
            )
            ->with(
                'success',
                'Xóa chuyên khoa thành công.'
            );
    }
}