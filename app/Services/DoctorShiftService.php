<?php

namespace App\Services;

use App\Models\DoctorSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

final class DoctorShiftService
{
    public const SHIFTS = [
        'MORNING' => ['07:00', '11:30'],
        'AFTERNOON' => ['13:30', '16:30'],
    ];

    // Chỉ dùng luồng mới sau khi cấu trúc không giới hạn đã được áp dụng.
    public function requireUnlimitedSchema(): void
    {
        $columns = \Illuminate\Support\Facades\Schema::getColumns('appointment_time_slots');
        $column = collect($columns)->firstWhere('name', 'max_patients');
        if (! $column || ! ($column['nullable'] ?? false)) {
            throw ValidationException::withMessages(['shift' => 'Chưa cập nhật cấu trúc khung giờ không giới hạn. Vui lòng liên hệ quản trị hệ thống.']);
        }
    }

    /** Giờ của ca được xác định ở máy chủ, không tin giờ từ biểu mẫu. */
    public function times(string $shift): array
    {
        return self::SHIFTS[$shift] ?? throw ValidationException::withMessages(['shift' => 'Vui lòng chọn ca sáng hoặc ca chiều.']);
    }

    /** Tạo các khung liền nhau trong giao dịch của thao tác gọi. */
    public function createSlots(DoctorSchedule $schedule): int
    {
        $this->requireUnlimitedSchema();
        $start = CarbonImmutable::createFromFormat('!H:i', substr($schedule->start_time, 0, 5));
        $end = CarbonImmutable::createFromFormat('!H:i', substr($schedule->end_time, 0, 5));
        $count = 0;
        while ($start->addMinutes(30)->lessThanOrEqualTo($end)) {
            $next = $start->addMinutes(30);
            $schedule->appointmentTimeSlots()->create([
                'start_time' => $start->format('H:i:s'),
                'end_time' => $next->format('H:i:s'),
                'max_patients' => null,
                // Khung sẵn sàng sử dụng; lịch cha quyết định có được đặt hay không.
                // Khi bật lại lịch ngừng hoạt động, các khung mới không bị kẹt ở trạng thái đóng.
                'status' => 'ACTIVE',
            ]);
            $start = $next;
            $count++;
        }
        return $count;
    }
}
