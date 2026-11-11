<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAppointmentTimeSlotRequest;
use App\Http\Requests\Admin\UpdateAppointmentTimeSlotRequest;
use App\Models\AppointmentTimeSlot;
use App\Models\DoctorSchedule;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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

    public function store(StoreAppointmentTimeSlotRequest $request, DoctorSchedule $doctorSchedule): RedirectResponse
    {
        $data = $request->validated();

        return DB::transaction(function () use ($doctorSchedule, $data): RedirectResponse {
            $doctorSchedule = DoctorSchedule::whereKey($doctorSchedule->id)->lockForUpdate()->firstOrFail();
            $startTime = $this->normalizeTime($data['start_time']);
            $endTime = $this->normalizeTime($data['end_time']);
            $this->validateSlot($doctorSchedule, $data, $startTime, $endTime);
            $doctorSchedule->appointmentTimeSlots()->create([
                'start_time' => $startTime, 'end_time' => $endTime,
                'max_patients' => $data['max_patients'], 'status' => $data['status'], 'notes' => $data['notes'] ?? null,
            ]);

            return redirect()->route('admin.doctor-schedules.show', $doctorSchedule)->with('success', 'Thêm khung giờ khám thành công.');
        }, 3);
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

    public function update(UpdateAppointmentTimeSlotRequest $request, AppointmentTimeSlot $appointmentTimeSlot): RedirectResponse
    {
        $data = $request->validated();

        return DB::transaction(function () use ($appointmentTimeSlot, $data): RedirectResponse {
            $doctorSchedule = DoctorSchedule::whereKey($appointmentTimeSlot->doctor_schedule_id)->lockForUpdate()->firstOrFail();
            $slot = AppointmentTimeSlot::whereKey($appointmentTimeSlot->id)->lockForUpdate()->firstOrFail();
            $startTime = $this->normalizeTime($data['start_time']);
            $endTime = $this->normalizeTime($data['end_time']);
            $appointmentsCount = $slot->appointments()->count();
            if ($appointmentsCount > 0 && ($slot->start_time !== $startTime || $slot->end_time !== $endTime)) {
                throw ValidationException::withMessages(['slot_conflict' => 'Khung giờ đã có lịch hẹn nên không thể thay đổi thời gian bắt đầu hoặc kết thúc.']);
            }
            if ((int) $data['max_patients'] < $appointmentsCount) {
                throw ValidationException::withMessages(['max_patients' => 'Sức chứa không được nhỏ hơn số lịch hẹn đã đặt ('.$appointmentsCount.').']);
            }
            $this->validateSlot($doctorSchedule, $data, $startTime, $endTime, $slot->id);
            $slot->update([
                'start_time' => $startTime, 'end_time' => $endTime,
                'max_patients' => $data['max_patients'], 'status' => $data['status'], 'notes' => $data['notes'] ?? null,
            ]);

            return redirect()->route('admin.doctor-schedules.show', $doctorSchedule)->with('success', 'Cập nhật khung giờ khám thành công.');
        }, 3);
    }

    public function destroy(AppointmentTimeSlot $appointmentTimeSlot): RedirectResponse
    {
        return DB::transaction(function () use ($appointmentTimeSlot): RedirectResponse {
            $doctorSchedule = DoctorSchedule::whereKey($appointmentTimeSlot->doctor_schedule_id)->lockForUpdate()->firstOrFail();
            $slot = AppointmentTimeSlot::whereKey($appointmentTimeSlot->id)->lockForUpdate()->firstOrFail();
            if ($slot->appointments()->exists()) {
                return redirect()->route('admin.doctor-schedules.show', $doctorSchedule)->with('error', 'Khung giờ đã có lịch hẹn nên không thể xóa.');
            }
            $slot->delete();

            return redirect()->route('admin.doctor-schedules.show', $doctorSchedule)->with('success', 'Xóa khung giờ khám thành công.');
        }, 3);
    }

    /** @param array<string, mixed> $data */
    private function validateSlot(DoctorSchedule $doctorSchedule, array $data, string $startTime, string $endTime, ?int $ignoreSlotId = null): void
    {
        if (! $this->isInsideSchedule($doctorSchedule, $startTime, $endTime)) {
            throw ValidationException::withMessages(['slot_conflict' => 'Khung giờ phải nằm hoàn toàn trong thời gian làm việc của bác sĩ.']);
        }
        if ($data['status'] === 'ACTIVE' && $doctorSchedule->status !== 'ACTIVE') {
            throw ValidationException::withMessages(['slot_conflict' => 'Không thể kích hoạt khung giờ khi lịch làm việc đã ngừng hoạt động.']);
        }
        if ($this->hasOverlap($doctorSchedule->id, $startTime, $endTime, $ignoreSlotId)) {
            throw ValidationException::withMessages(['slot_conflict' => 'Khung giờ bị trùng với một khung giờ khác trong lịch làm việc này.']);
        }
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
