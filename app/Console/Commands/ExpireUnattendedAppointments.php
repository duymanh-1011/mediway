<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\AppointmentStatus;
use App\Models\AppointmentTimeSlot;
use App\Models\DoctorSchedule;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireUnattendedAppointments extends Command
{
    protected $signature = 'appointments:expire-unattended
        {--dry-run : Chỉ liệt kê số ứng viên, không ghi dữ liệu}
        {--limit=200 : Số lịch tối đa trong một lượt}';

    protected $description = 'Tự hủy lịch BOOKED quá giờ, chưa được tiếp nhận (có audit).';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $enabled = (bool) config(
            'appointments_auto_cancel.enabled',
            false
        );

        if (! $enabled && ! $dryRun) {
            $this->warn(
                'Đang tắt: APPOINTMENTS_AUTO_CANCEL_ENABLED=false'
            );

            return self::SUCCESS;
        }

        $booked = AppointmentStatus::query()
            ->where('name', 'BOOKED')
            ->where('is_active', true)
            ->where('allows_transition', true)
            ->first();

        $cancelled = AppointmentStatus::query()
            ->where('name', 'CANCELLED')
            ->where('is_active', true)
            ->first();

        if (! $booked || ! $cancelled) {
            $this->error(
                'Thiếu trạng thái BOOKED/CANCELLED hợp lệ. Không thay đổi dữ liệu.'
            );

            return self::FAILURE;
        }

        $rawLimit = filter_var(
            $this->option('limit'),
            FILTER_VALIDATE_INT
        );

        if (
            $rawLimit === false
            || $rawLimit < 1
            || $rawLimit > 1000
        ) {
            $this->error(
                '--limit phải nằm từ 1 đến 1000.'
            );

            return self::FAILURE;
        }

        $graceMinutes = max(
            0,
            min(
                1440,
                (int) config(
                    'appointments_auto_cancel.grace_minutes',
                    30
                )
            )
        );

        $cutoff = now()->subMinutes($graceMinutes);

        /*
         * Chỉ chọn ứng viên.
         * Mọi điều kiện được kiểm tra lại
         * trong transaction trước khi cập nhật.
         */
        $ids = DB::table('appointments as a')
            ->join(
                'appointment_time_slots as s',
                's.id',
                '=',
                'a.appointment_time_slot_id'
            )
            ->join(
                'doctor_schedules as ds',
                'ds.id',
                '=',
                's.doctor_schedule_id'
            )
            ->where(
                'a.appointment_status_id',
                $booked->id
            )
            ->whereColumn(
                'a.appointment_date',
                'ds.work_date'
            )
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('medical_visits as v')
                    ->whereColumn(
                        'v.appointment_id',
                        'a.id'
                    );
            })
            ->where(function ($query) use ($cutoff) {
                $query
                    ->whereDate(
                        'a.appointment_date',
                        '<',
                        $cutoff->toDateString()
                    )
                    ->orWhere(function ($day) use ($cutoff) {
                        $day
                            ->whereDate(
                                'a.appointment_date',
                                '=',
                                $cutoff->toDateString()
                            )
                            ->whereTime(
                                's.end_time',
                                '<=',
                                $cutoff->format('H:i:s')
                            );
                    });
            })
            ->orderBy('a.id')
            ->limit($rawLimit)
            ->pluck('a.id');

        if ($dryRun) {
            $this->info(
                'DRY-RUN: '
                    . $ids->count()
                    . ' ứng viên (tối đa '
                    . $rawLimit
                    . '). Không ghi dữ liệu.'
            );

            return self::SUCCESS;
        }

        $updatedCount = 0;

        foreach ($ids as $id) {
            $changed = DB::transaction(
                function () use (
                    $id,
                    $booked,
                    $cancelled,
                    $graceMinutes
                ) {
                    /*
                     * Khóa theo thứ tự:
                     * schedule -> slot -> appointment
                     */
                    $candidateSlotId = DB::table(
                        'appointments'
                    )
                        ->where('id', $id)
                        ->value('appointment_time_slot_id');

                    if (! $candidateSlotId) {
                        return false;
                    }

                    $candidateScheduleId = DB::table(
                        'appointment_time_slots'
                    )
                        ->where('id', $candidateSlotId)
                        ->value('doctor_schedule_id');

                    if (! $candidateScheduleId) {
                        return false;
                    }

                    $schedule = DoctorSchedule::query()
                        ->whereKey($candidateScheduleId)
                        ->lockForUpdate()
                        ->first();

                    if (! $schedule) {
                        return false;
                    }

                    $slot = AppointmentTimeSlot::query()
                        ->whereKey($candidateSlotId)
                        ->lockForUpdate()
                        ->first();

                    if (
                        ! $slot
                        || (int) $slot->doctor_schedule_id
                        !== (int) $schedule->id
                    ) {
                        return false;
                    }

                    $appointment = Appointment::query()
                        ->whereKey($id)
                        ->lockForUpdate()
                        ->first();

                    if (
                        ! $appointment
                        || (int) $appointment->appointment_status_id
                        !== (int) $booked->id
                        || (int) $appointment->appointment_time_slot_id
                        !== (int) $slot->id
                        || ! $appointment->appointment_date
                        || $appointment->appointment_date
                        ->format('Y-m-d')
                        !== $schedule->work_date
                        ->format('Y-m-d')
                        || $appointment->medicalVisit()
                        ->exists()
                    ) {
                        return false;
                    }

                    $now = now();

                    $endsAt = Carbon::parse(
                        $appointment->appointment_date
                            ->format('Y-m-d')
                            . ' '
                            . substr(
                                (string) $slot->end_time,
                                0,
                                8
                            ),
                        config(
                            'app.timezone',
                            'Asia/Ho_Chi_Minh'
                        )
                    );

                    /*
                     * Lịch vừa đổi giờ trong lúc chạy
                     * phải được kiểm tra lại.
                     */
                    if (
                        $endsAt
                        ->addMinutes($graceMinutes)
                        ->greaterThan($now)
                    ) {
                        return false;
                    }

                    $reason =
                        '[AUTO_NO_SHOW] Hệ thống tự động hủy vì lịch quá giờ và chưa ghi nhận tiếp nhận.';

                    $oldValues = [
                        'appointment_status_id' =>
                        $appointment->appointment_status_id,

                        'cancelled_at' =>
                        $appointment->cancelled_at
                            ?->toDateTimeString(),

                        'cancellation_reason' =>
                        $appointment->cancellation_reason,
                    ];

                    $appointment->update([
                        'appointment_status_id' =>
                        $cancelled->id,

                        'cancelled_at' =>
                        $now,

                        'cancellation_reason' =>
                        $reason,
                    ]);

                    /*
                     * Ghi audit cùng giao dịch.
                     * Nếu audit thất bại, toàn bộ
                     * thao tác sẽ rollback.
                     */
                    DB::table('audit_logs')->insert([
                        'user_id' => null,

                        'role_name_snapshot' =>
                        'SYSTEM',

                        'action' =>
                        'AUTO_CANCEL_NO_SHOW',

                        'entity_type' =>
                        'Appointment',

                        'entity_id' =>
                        $appointment->id,

                        'entity_business_code' =>
                        $appointment->appointment_code,

                        'old_values' => json_encode(
                            $oldValues,
                            JSON_UNESCAPED_UNICODE
                                | JSON_THROW_ON_ERROR
                        ),

                        'new_values' => json_encode(
                            [
                                'appointment_status_id' =>
                                $cancelled->id,

                                'cancelled_at' =>
                                $now->toDateTimeString(),

                                'cancellation_reason' =>
                                $reason,
                            ],
                            JSON_UNESCAPED_UNICODE
                                | JSON_THROW_ON_ERROR
                        ),

                        'description' =>
                        'Tự động hủy lịch vắng khám, không có lượt khám được ghi nhận.',

                        'ip_address' => null,

                        'user_agent' =>
                        'Laravel Scheduler',

                        'performed_at' => $now,

                        'created_at' => $now,
                    ]);

                    return true;
                },
                3
            );

            if ($changed) {
                $updatedCount++;
            }
        }

        $this->info(
            "Đã xử lý {$updatedCount} lịch; mỗi lượt tối đa {$rawLimit} ứng viên."
        );

        return self::SUCCESS;
    }
}
