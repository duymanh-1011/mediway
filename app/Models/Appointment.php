<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Validation\ValidationException;
use Carbon\CarbonImmutable;

class Appointment extends Model
{
    public const BOOKING_ADVANCE_DAYS = 3;
    public const RECEPTION_ADVANCE_MINUTES = 30;

    protected $fillable = [
        'appointment_code',
        'patient_id',
        'doctor_id',
        'specialty_id',
        'examination_room_id',
        'appointment_date',
        'appointment_time_slot_id',
        'appointment_status_id',
        'checkin_token',
        'reason_for_visit',
        'patient_notes',
        'booked_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'booked_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function isScheduledForToday(): bool
    {
        return $this->appointment_date?->isToday()
            && $this->appointmentTimeSlot?->doctorSchedule?->work_date?->isToday();
    }

    public function isScheduledInFuture(): bool
    {
        return $this->appointment_date?->isAfter(today())
            || $this->appointmentTimeSlot?->doctorSchedule?->work_date?->isAfter(today());
    }

    public function assertReceptionDate(): void
    {
        if (! $this->isScheduledForToday()) {
            throw ValidationException::withMessages(['appointment' => 'Chỉ được tiếp nhận vào đúng ngày hẹn khám. Lịch hẹn này có ngày khám là '.$this->appointment_date?->format('d/m/Y').'.']);
        }
    }

    /** @return array{start: CarbonImmutable, end: CarbonImmutable}|null */
    public function receptionWindow(): ?array
    {
        $slot = $this->appointmentTimeSlot;
        $schedule = $slot?->doctorSchedule;
        if (! $this->appointment_date || ! $slot?->start_time || ! $schedule?->work_date || ! $schedule->start_time || ! $schedule->end_time
            || ! $this->appointment_date->isSameDay($schedule->work_date)) {
            return null;
        }

        $date = $schedule->work_date->format('Y-m-d');
        $shiftStart = CarbonImmutable::parse($date.' '.$schedule->start_time, config('app.timezone'));
        $shiftEnd = CarbonImmutable::parse($date.' '.$schedule->end_time, config('app.timezone'));
        $earlyStart = CarbonImmutable::parse($date.' '.$slot->start_time, config('app.timezone'))->subMinutes(self::RECEPTION_ADVANCE_MINUTES);

        return ['start' => $shiftStart->greaterThan($earlyStart) ? $shiftStart : $earlyStart, 'end' => $shiftEnd];
    }

    public function receptionUnavailableReason(): ?string
    {
        if (! $this->isScheduledForToday()) {
            return 'Chỉ được tiếp nhận vào đúng ngày hẹn khám. Lịch hẹn này có ngày khám là '.$this->appointment_date?->format('d/m/Y').'.';
        }
        $window = $this->receptionWindow();
        if (! $window || $window['start']->greaterThanOrEqualTo($window['end'])) {
            return 'Không thể xác định thời gian tiếp nhận phù hợp với ca làm việc. Vui lòng kiểm tra lại lịch hẹn.';
        }
        if (now()->lessThan($window['start'])) {
            return 'Chỉ có thể tiếp nhận từ '.$window['start']->format('H:i').' ngày '.$window['start']->format('d/m/Y').' (tối đa 30 phút trước giờ hẹn và trong ca làm việc của bác sĩ).';
        }
        if (now()->greaterThanOrEqualTo($window['end'])) {
            return 'Ca làm việc của bác sĩ đã kết thúc lúc '.$window['end']->format('H:i').', không thể tiếp nhận lịch hẹn này.';
        }
        return null;
    }

    public function assertReceptionWindow(): void
    {
        if ($message = $this->receptionUnavailableReason()) {
            throw ValidationException::withMessages(['appointment' => $message]);
        }
    }

    public function patient()
    {
        return $this->belongsTo(
            Patient::class,
            'patient_id'
        );
    }

    public function doctor()
    {
        return $this->belongsTo(
            Doctor::class,
            'doctor_id'
        );
    }

    public function specialty()
    {
        return $this->belongsTo(
            Specialty::class,
            'specialty_id'
        );
    }

    public function examinationRoom()
    {
        return $this->belongsTo(
            ExaminationRoom::class,
            'examination_room_id'
        );
    }

    public function appointmentTimeSlot()
    {
        return $this->belongsTo(
            AppointmentTimeSlot::class,
            'appointment_time_slot_id'
        );
    }

    public function appointmentStatus()
    {
        return $this->belongsTo(
            AppointmentStatus::class,
            'appointment_status_id'
        );
    }

    public function medicalVisit(): HasOne
    {
        return $this->hasOne(
            MedicalVisit::class,
            'appointment_id'
        );
    }
}
