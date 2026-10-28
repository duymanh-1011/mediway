<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

final class ClinicalTimeline
{
    /** Called after date validation and again while holding the parent row lock. */
    public static function assertEventTime(?string $value, ?CarbonInterface $earliest, string $field, string $label): void
    {
        if (! $value) {
            return;
        }
        $at = Carbon::parse($value);
        if ($earliest && $at->lt($earliest)) {
            throw ValidationException::withMessages([$field => $label.' không được trước thời điểm bắt đầu nghiệp vụ ('.$earliest->format('d/m/Y H:i').').']);
        }
        if ($at->isFuture()) {
            throw ValidationException::withMessages([$field => $label.' không được sau thời điểm hiện tại. Vui lòng kiểm tra lại ngày và giờ.']);
        }
    }

    public static function assertFollowUp(bool $hasFollowUp, ?string $date, CarbonInterface $concludedAt): void
    {
        if ($hasFollowUp && $date && Carbon::parse($date)->startOfDay()->lt($concludedAt->copy()->startOfDay())) {
            throw ValidationException::withMessages(['follow_up_date' => 'Ngày tái khám không được trước ngày kết luận ('.$concludedAt->format('d/m/Y').').']);
        }
    }
}
