
<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Laravel Artisan Commands
|--------------------------------------------------------------------------
|
| Giữ nguyên các lệnh Artisan mặc định và bổ sung
| scheduler tự động hủy lịch hẹn quá hạn cho Mediway.
|
*/

// Laravel default command
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


/*
|--------------------------------------------------------------------------
| MEDIWAY - AUTO NO-SHOW
|--------------------------------------------------------------------------
|
| Chỉ hủy lịch hẹn BOOKED khi:
| - Khung giờ đã kết thúc quá thời gian chờ.
| - Bệnh nhân chưa được tiếp nhận.
| - Chưa có lượt khám liên kết.
|
| Những điều kiện nghiệp vụ được kiểm tra trong
| ExpireUnattendedAppointments.
|
*/

// MEDIWAY_AUTO_NO_SHOW_SCHEDULE_BEGIN

Schedule::command('appointments:expire-unattended')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->when(
        fn(): bool => (bool) config(
            'appointments_auto_cancel.enabled',
            false
        )
    );

// MEDIWAY_AUTO_NO_SHOW_SCHEDULE_END
