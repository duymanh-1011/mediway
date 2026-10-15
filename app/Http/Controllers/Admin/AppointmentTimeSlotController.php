<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAppointmentTimeSlotRequest;
use App\Http\Requests\Admin\UpdateAppointmentTimeSlotRequest;
use App\Models\AppointmentTimeSlot;
use App\Models\DoctorSchedule;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AppointmentTimeSlotController extends Controller
{
    public function create(
        DoctorSchedule $doctorSchedule
    ) {
        $doctorSchedule->load([
            'doctor',
            'specialty',
            'examinationRoom',
        ]);

        return view(
            'admin.appointment-time-slots.create',
            compact('doctorSchedule')
        );
    }

    public function store(
        StoreAppointmentTimeSlotRequest $request,
        DoctorSchedule $doctorSchedule
    ) {
        $data =
            $request->validated();

        $startTime =
            $this->normalizeTime(
                $data['start_time']
            );

        $endTime =
            $this->normalizeTime(
                $data['end_time']
            );

        /*
        |--------------------------------------------------------------------------
        | Slot phải nằm trong giờ làm việc
        |--------------------------------------------------------------------------
        */

        if (
            !$this->isInsideSchedule(
                $doctorSchedule,
                $startTime,
                $endTime
            )
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'slot_conflict' =>
                        'Khung giờ phải nằm hoàn toàn trong thời gian làm việc của bác sĩ.',
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | ACTIVE slot không được thuộc lịch INACTIVE
        |--------------------------------------------------------------------------
        */

        if (
            $data['status'] === 'ACTIVE'
            &&
            $doctorSchedule->status !== 'ACTIVE'
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'slot_conflict' =>
                        'Không thể tạo khung giờ ACTIVE cho lịch làm việc đang INACTIVE.',
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Kiểm tra slot chồng giờ
        |--------------------------------------------------------------------------
        */

        if (
            $this->hasOverlap(
                $doctorSchedule->id,
                $startTime,
                $endTime
            )
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'slot_conflict' =>
                        'Khung giờ bị trùng với một khung giờ khác trong lịch làm việc này.',
                ]);
        }

        AppointmentTimeSlot::create([
            'doctor_schedule_id' =>
                $doctorSchedule->id,

            'start_time' =>
                $startTime,

            'end_time' =>
                $endTime,

            'max_patients' =>
                $data['max_patients'],

            'status' =>
                $data['status'],

            'notes' =>
                $data['notes'] ?? null,
        ]);

        return redirect()
            ->route(
                'admin.doctor-schedules.show',
                $doctorSchedule
            )
            ->with(
                'success',
                'Thêm khung giờ khám thành công.'
            );
    }

    public function edit(
        AppointmentTimeSlot $appointmentTimeSlot
    ) {
        $appointmentTimeSlot->load(
            'doctorSchedule.doctor',
            'doctorSchedule.specialty',
            'doctorSchedule.examinationRoom'
        );

        $appointmentsCount =
            DB::table('appointments')
                ->where(
                    'appointment_time_slot_id',
                    $appointmentTimeSlot->id
                )
                ->count();

        return view(
            'admin.appointment-time-slots.edit',
            compact(
                'appointmentTimeSlot',
                'appointmentsCount'
            )
        );
    }

    public function update(
        UpdateAppointmentTimeSlotRequest $request,
        AppointmentTimeSlot $appointmentTimeSlot
    ) {
        $data =
            $request->validated();

        $doctorSchedule =
            $appointmentTimeSlot
                ->doctorSchedule;

        $startTime =
            $this->normalizeTime(
                $data['start_time']
            );

        $endTime =
            $this->normalizeTime(
                $data['end_time']
            );

        $appointmentsCount =
            DB::table('appointments')
                ->where(
                    'appointment_time_slot_id',
                    $appointmentTimeSlot->id
                )
                ->count();

        /*
        |--------------------------------------------------------------------------
        | Slot đã có appointment không được đổi giờ
        |--------------------------------------------------------------------------
        */

        if ($appointmentsCount > 0) {

            $timeChanged =
                substr(
                    $appointmentTimeSlot->start_time,
                    0,
                    5
                )
                    !==
                substr(
                    $startTime,
                    0,
                    5
                )
                ||
                substr(
                    $appointmentTimeSlot->end_time,
                    0,
                    5
                )
                    !==
                substr(
                    $endTime,
                    0,
                    5
                );

            if ($timeChanged) {

                return back()
                    ->withInput()
                    ->withErrors([
                        'slot_conflict' =>
                            'Khung giờ đã có lịch hẹn nên không thể thay đổi thời gian bắt đầu hoặc kết thúc.',
                    ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Không giảm sức chứa thấp hơn số đã đặt
        |--------------------------------------------------------------------------
        */

        if (
            (int) $data['max_patients']
            <
            $appointmentsCount
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'max_patients' =>
                        'Sức chứa không được nhỏ hơn số lịch hẹn đã đặt (' .
                        $appointmentsCount .
                        ').',
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Phải nằm trong schedule
        |--------------------------------------------------------------------------
        */

        if (
            !$this->isInsideSchedule(
                $doctorSchedule,
                $startTime,
                $endTime
            )
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'slot_conflict' =>
                        'Khung giờ phải nằm hoàn toàn trong thời gian làm việc của bác sĩ.',
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | ACTIVE slot không được thuộc lịch INACTIVE
        |--------------------------------------------------------------------------
        */

        if (
            $data['status'] === 'ACTIVE'
            &&
            $doctorSchedule->status !== 'ACTIVE'
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'slot_conflict' =>
                        'Không thể chuyển khung giờ sang ACTIVE khi lịch làm việc đang INACTIVE.',
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Kiểm tra overlap, bỏ qua chính slot đang sửa
        |--------------------------------------------------------------------------
        */

        if (
            $this->hasOverlap(
                $doctorSchedule->id,
                $startTime,
                $endTime,
                $appointmentTimeSlot->id
            )
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'slot_conflict' =>
                        'Khung giờ bị trùng với một khung giờ khác trong lịch làm việc này.',
                ]);
        }

        $appointmentTimeSlot->update([
            'start_time' =>
                $startTime,

            'end_time' =>
                $endTime,

            'max_patients' =>
                $data['max_patients'],

            'status' =>
                $data['status'],

            'notes' =>
                $data['notes'] ?? null,
        ]);

        return redirect()
            ->route(
                'admin.doctor-schedules.show',
                $doctorSchedule
            )
            ->with(
                'success',
                'Cập nhật khung giờ khám thành công.'
            );
    }

    public function destroy(
        AppointmentTimeSlot $appointmentTimeSlot
    ) {
        $doctorScheduleId =
            $appointmentTimeSlot
                ->doctor_schedule_id;

        /*
        |--------------------------------------------------------------------------
        | Đã có appointment -> không xóa
        |--------------------------------------------------------------------------
        */

        $hasAppointments =
            DB::table('appointments')
                ->where(
                    'appointment_time_slot_id',
                    $appointmentTimeSlot->id
                )
                ->exists();

        if ($hasAppointments) {

            return redirect()
                ->route(
                    'admin.doctor-schedules.show',
                    $doctorScheduleId
                )
                ->with(
                    'error',
                    'Khung giờ đã có lịch hẹn nên không thể xóa.'
                );
        }

        $appointmentTimeSlot->delete();

        return redirect()
            ->route(
                'admin.doctor-schedules.show',
                $doctorScheduleId
            )
            ->with(
                'success',
                'Xóa khung giờ khám thành công.'
            );
    }

    private function normalizeTime(
        string $time
    ): string {
        return Carbon::createFromFormat(
            'H:i',
            $time
        )->format('H:i:s');
    }

    private function isInsideSchedule(
        DoctorSchedule $doctorSchedule,
        string $startTime,
        string $endTime
    ): bool {
        $scheduleStart =
            substr(
                $doctorSchedule->start_time,
                0,
                8
            );

        $scheduleEnd =
            substr(
                $doctorSchedule->end_time,
                0,
                8
            );

        return
            $startTime >= $scheduleStart
            &&
            $endTime <= $scheduleEnd;
    }

    private function hasOverlap(
        int $doctorScheduleId,
        string $startTime,
        string $endTime,
        ?int $ignoreSlotId = null
    ): bool {
        return AppointmentTimeSlot::query()
            ->where(
                'doctor_schedule_id',
                $doctorScheduleId
            )
            ->when(
                $ignoreSlotId,
                function ($query) use (
                    $ignoreSlotId
                ) {
                    $query->where(
                        'id',
                        '!=',
                        $ignoreSlotId
                    );
                }
            )
            ->where(
                'start_time',
                '<',
                $endTime
            )
            ->where(
                'end_time',
                '>',
                $startTime
            )
            ->exists();
    }
}