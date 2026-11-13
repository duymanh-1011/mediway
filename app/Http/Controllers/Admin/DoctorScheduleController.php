<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateDoctorScheduleRequest;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\AppointmentTimeSlot;
use App\Models\ExaminationRoom;
use App\Models\Specialty;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DoctorScheduleController extends Controller
{
    public function redirectToMonth(Request $request): RedirectResponse
    {
        $filters = $request->validate([
            'doctor_id' => 'nullable|integer|min:1',
            'month' => 'nullable|date_format:Y-m',
            'work_date' => 'nullable|date_format:Y-m-d',
            'keyword' => 'nullable|string|max:100',
        ]);

        $month = $filters['month'] ?? (isset($filters['work_date']) ? substr($filters['work_date'], 0, 7) : null);
        if (isset($filters['doctor_id']) && Doctor::whereKey($filters['doctor_id'])->exists()) {
            return redirect()->route('admin.doctor-schedules.month.show', [
                'doctor' => $filters['doctor_id'],
                'month' => $month,
            ]);
        }

        return redirect()->route('admin.doctor-schedules.month.index', [
            'keyword' => $filters['keyword'] ?? null,
            'month' => $month,
        ]);
    }

    public function redirectLegacyStore(): RedirectResponse
    {
        return redirect()->route('admin.doctor-schedules.month.index', [], 303)
            ->with('error', 'Vui lòng chọn bác sĩ và xếp ca trong lịch tháng.');
    }

    public function show(
        DoctorSchedule $doctorSchedule
    ) {
        $doctorSchedule->load([
            'doctor',
            'specialty',
            'examinationRoom',
        ]);

        $timeSlots = AppointmentTimeSlot::where('doctor_schedule_id', $doctorSchedule->id)
            ->withCount(['appointments' => fn ($appointments) => $appointments->whereHas(
                'appointmentStatus', fn ($status) => $status->where('name', '!=', 'CANCELLED'),
            )])->orderBy('start_time')->get();
        $timeSlots->each(fn (AppointmentTimeSlot $slot) => $slot->setRelation('doctorSchedule', $doctorSchedule));

        $totalSlots =
            $timeSlots->count();

        $totalAppointments =
            $timeSlots->sum(
                'appointments_count'
            );

        $totalCapacity =
            $timeSlots->sum(
                'max_patients'
            );

        return view(
            'admin.doctor-schedules.show',
            compact(
                'doctorSchedule',
                'timeSlots',
                'totalSlots',
                'totalAppointments',
                'totalCapacity'
            )
        );
    }

    public function edit(
        DoctorSchedule $doctorSchedule
    ) {
        $this->ensureLegacySchedule($doctorSchedule);

        $doctors =
            Doctor::query()
                ->with('specialty')
                ->orderBy('full_name')
                ->get();

        $specialties =
            Specialty::query()
                ->orderBy('name')
                ->get();

        $examinationRooms =
            ExaminationRoom::query()
                ->orderBy('name')
                ->get();

        return view(
            'admin.doctor-schedules.edit',
            compact(
                'doctorSchedule',
                'doctors',
                'specialties',
                'examinationRooms'
            )
        );
    }

    public function update(
        UpdateDoctorScheduleRequest $request,
        DoctorSchedule $doctorSchedule
    ): RedirectResponse {
        return DB::transaction(function () use ($request, $doctorSchedule): RedirectResponse {
            $data = $request->validated();

            $doctorSchedule = DoctorSchedule::whereKey($doctorSchedule->id)->lockForUpdate()->firstOrFail();
            $this->ensureLegacySchedule($doctorSchedule);
            $allowLegacyDeactivation = $data['status'] === 'INACTIVE'
                && (int) $doctorSchedule->doctor_id === (int) $data['doctor_id']
                && (int) $doctorSchedule->specialty_id === (int) $data['specialty_id']
                && (int) $doctorSchedule->examination_room_id === (int) $data['examination_room_id'];
            $this->lockActiveScheduleParents($data, $allowLegacyDeactivation);

            if ($data['status'] === 'ACTIVE') {

                $conflictMessage =
                    $this->getConflictMessage(
                        $data,
                        $doctorSchedule->id
                    );

                if ($conflictMessage !== null) {

                    return back()
                        ->withInput()
                        ->withErrors([
                            'schedule_conflict' => $conflictMessage,
                        ]);
                }
            }

            $hasAppointments =
                DB::table(
                    'appointment_time_slots as slots'
                )
                    ->join(
                        'appointments',
                        'appointments.appointment_time_slot_id',
                        '=',
                        'slots.id'
                    )
                    ->where(
                        'slots.doctor_schedule_id',
                        $doctorSchedule->id
                    )
                    ->exists();

            if ($hasAppointments) {

                $coreChanged =
                    (int) $doctorSchedule->doctor_id
                        !==
                    (int) $data['doctor_id']
                    ||
                    (int) $doctorSchedule->specialty_id
                        !==
                    (int) $data['specialty_id']
                    ||
                    (int) $doctorSchedule->examination_room_id
                        !==
                    (int) $data['examination_room_id']
                    ||
                    $doctorSchedule
                        ->work_date
                        ->format('Y-m-d')
                        !==
                    $data['work_date']
                    ||
                    substr(
                        $doctorSchedule->start_time,
                        0,
                        5
                    )
                        !==
                    $data['start_time']
                    ||
                    substr(
                        $doctorSchedule->end_time,
                        0,
                        5
                    )
                        !==
                    $data['end_time'];

                if ($coreChanged) {

                    return back()
                        ->withInput()
                        ->withErrors([
                            'schedule_conflict' => 'Lịch làm việc đã có lịch hẹn nên không thể thay đổi bác sĩ, chuyên khoa, phòng, ngày hoặc khung giờ. Bạn chỉ nên cập nhật trạng thái hoặc ghi chú.',
                        ]);
                }
            }

            $startTime = $data['start_time'].':00';
            $endTime = $data['end_time'].':00';
            if ($doctorSchedule->appointmentTimeSlots()->where(function ($query) use ($startTime, $endTime) {
                $query->where('start_time', '<', $startTime)->orWhere('end_time', '>', $endTime);
            })->exists()) {
                return back()->withInput()->withErrors([
                    'end_time' => 'Khung giờ mới phải bao phủ toàn bộ khung giờ hẹn đã tạo. Hãy điều chỉnh các khung giờ hẹn trước.',
                ]);
            }

            $doctorSchedule->update(
                $data
            );

            return redirect()
                ->route(
                    'admin.doctor-schedules.show',
                    $doctorSchedule
                )
                ->with(
                    'success',
                    'Cập nhật lịch làm việc thành công.'
                );
        }, 3);
    }

    public function destroy(
        DoctorSchedule $doctorSchedule
    ) {
        $this->ensureLegacySchedule($doctorSchedule);

        $hasAppointments =
            DB::table(
                'appointment_time_slots as slots'
            )
                ->join(
                    'appointments',
                    'appointments.appointment_time_slot_id',
                    '=',
                    'slots.id'
                )
                ->where(
                    'slots.doctor_schedule_id',
                    $doctorSchedule->id
                )
                ->exists();

        if ($hasAppointments) {

            return redirect()
                ->route(
                    'admin.doctor-schedules.show',
                    $doctorSchedule
                )
                ->with(
                    'error',
                    'Lịch làm việc đã có lịch hẹn nên không thể xóa.'
                );
        }

        $hasSlots =
            DB::table(
                'appointment_time_slots'
            )
                ->where(
                    'doctor_schedule_id',
                    $doctorSchedule->id
                )
                ->exists();

        if ($hasSlots) {

            return redirect()
                ->route(
                    'admin.doctor-schedules.show',
                    $doctorSchedule
                )
                ->with(
                    'error',
                    'Lịch làm việc đang có khung giờ. Hãy xóa các khung giờ chưa được đặt trước khi xóa lịch.'
                );
        }

        $doctorSchedule->delete();

        return redirect()
            ->route(
                'admin.doctor-schedules.month.show',
                [$doctorSchedule->doctor_id, 'month' => $doctorSchedule->work_date->format('Y-m')]
            )
            ->with(
                'success',
                'Xóa lịch làm việc thành công.'
            );
    }

    /** @param array<string, mixed> $data */
    private function lockActiveScheduleParents(array $data, bool $allowLegacyDeactivation = false): void
    {
        $doctor = null;
        $room = null;
        foreach (['doctor_id' => Doctor::class, 'specialty_id' => Specialty::class, 'examination_room_id' => ExaminationRoom::class] as $field => $model) {
            $parent = $model::whereKey($data[$field])->lockForUpdate()->firstOrFail();
            if ($field === 'doctor_id') {
                $doctor = $parent;
            }
            if ($field === 'examination_room_id') $room = $parent;
            if ($data['status'] === 'ACTIVE' && $parent->status !== 'ACTIVE') {
                throw ValidationException::withMessages([$field => 'Không thể tạo hoặc kích hoạt lịch với bác sĩ, chuyên khoa hoặc phòng đã ngừng hoạt động.']);
            }
        }
        if (! $allowLegacyDeactivation && (int) $room->specialty_id !== (int) $data['specialty_id']) {
            throw ValidationException::withMessages(['examination_room_id' => 'Phòng chưa được gán hoặc không thuộc chuyên khoa đã chọn. Vui lòng cập nhật chuyên khoa của phòng.']);
        }
        if (! $allowLegacyDeactivation && ! $doctor->canPracticeSpecialty((int) $data['specialty_id'])) {
            throw ValidationException::withMessages(['doctor_id' => 'Bác sĩ không thuộc chuyên khoa đã chọn. Vui lòng chọn lại bác sĩ hoặc chuyên khoa.']);
        }
    }

    private function ensureLegacySchedule(DoctorSchedule $schedule): void
    {
        abort_if($schedule->isStandardShift(), 409, 'Ca hành chính chuẩn được quản lý theo lịch tháng.');
    }

    private function getConflictMessage(array $data, ?int $ignoreScheduleId = null): ?string
    {
        $overlapping = DoctorSchedule::query()
            ->overlappingOn($data['work_date'], $data['start_time'].':00', $data['end_time'].':00')
            ->when($ignoreScheduleId, fn ($query) => $query->whereKeyNot($ignoreScheduleId));

        if ((clone $overlapping)->where('doctor_id', $data['doctor_id'])
            ->where(fn ($query) => $query->where('status', 'ACTIVE')->orWhereNotNull('shift_code'))->exists()) {
            return 'Bác sĩ đã có lịch làm việc hoặc ca nghỉ trùng thời gian trong ngày này.';
        }

        if ($overlapping->where('examination_room_id', $data['examination_room_id'])->where('status', 'ACTIVE')->exists()) {
            return 'Phòng khám đã được sử dụng bởi lịch làm việc khác trong khoảng thời gian này.';
        }

        return null;
    }
}
