<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAppointmentStatusRequest;
use App\Models\AppointmentStatus;

use Illuminate\Http\Request;

class AppointmentStatusController extends Controller
{
    public function index(Request $request)
    {
        $keyword = trim(
            (string) $request->get('keyword')
        );

        $isActive =
            $request->get('is_active');

        $allowsTransition =
            $request->get('allows_transition');

        $query =
            AppointmentStatus::query();

        /*
        |--------------------------------------------------------------------------
        | Tìm kiếm
        |--------------------------------------------------------------------------
        */

        if ($keyword !== '') {

            $query->where(
                function ($q) use ($keyword) {

                    $q->where(
                        'name',
                        'like',
                        "%{$keyword}%"
                    )
                    ->orWhere(
                        'description',
                        'like',
                        "%{$keyword}%"
                    );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Lọc hoạt động
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $isActive,
                ['0', '1'],
                true
            )
        ) {
            $query->where(
                'is_active',
                (int) $isActive
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Lọc khả năng chuyển trạng thái
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $allowsTransition,
                ['0', '1'],
                true
            )
        ) {
            $query->where(
                'allows_transition',
                (int) $allowsTransition
            );
        }

        $appointmentStatuses =
            $query
                ->orderBy('process_order')
                ->paginate(10)
                ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Thống kê
        |--------------------------------------------------------------------------
        */

        $totalStatuses =
            AppointmentStatus::count();

        $activeStatuses =
            AppointmentStatus::where(
                'is_active',
                true
            )->count();

        $terminalStatuses =
            AppointmentStatus::where(
                'allows_transition',
                false
            )->count();

        return view(
            'admin.appointment-statuses.index',
            compact(
                'appointmentStatuses',
                'keyword',
                'isActive',
                'allowsTransition',
                'totalStatuses',
                'activeStatuses',
                'terminalStatuses'
            )
        );
    }

    public function show(
        AppointmentStatus $appointmentStatus
    ) {
        return view(
            'admin.appointment-statuses.show',
            compact('appointmentStatus')
        );
    }

    public function edit(
        AppointmentStatus $appointmentStatus
    ) {
        return view(
            'admin.appointment-statuses.edit',
            compact('appointmentStatus')
        );
    }

    public function update(
        UpdateAppointmentStatusRequest $request,
        AppointmentStatus $appointmentStatus
    ) {
        $data =
            $request->validated();

        $data['allows_transition'] =
            $request->boolean(
                'allows_transition'
            );

        $data['is_active'] =
            $request->boolean(
                'is_active'
            );

        $appointmentStatus->update(
            $data
        );

        return redirect()
            ->route(
                'admin.appointment-statuses.show',
                $appointmentStatus
            )
            ->with(
                'success',
                'Cập nhật trạng thái lịch hẹn thành công.'
            );
    }
}