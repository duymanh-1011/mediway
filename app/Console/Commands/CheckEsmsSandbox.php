<?php

namespace App\Console\Commands;

use App\Services\EsmsDemoPatientOtpSender;
use Illuminate\Console\Command;

class CheckEsmsSandbox extends Command
{
    protected $signature = 'esms:check-sandbox';
    protected $description = 'Kiểm tra kết nối eSMS ở chế độ sandbox, không gửi SMS thật.';

    public function handle(EsmsDemoPatientOtpSender $sender): int
    {
        if (! app()->environment('local') || ! config('portal.esms.enabled')) {
            $this->error('Chỉ chạy trong môi trường local khi ESMS_ENABLED=true.');
            return self::FAILURE;
        }
        $phone = config('portal.demo_otp_phone');
        if (! is_string($phone) || ! preg_match('/^0[0-9]{9}$/', $phone)) {
            $this->error('Cần cấu hình PORTAL_DEMO_OTP_PHONE bằng số điện thoại thử nghiệm hợp lệ.');
            return self::FAILURE;
        }
        if (! $sender->checkSandbox($phone)) {
            $this->error('Không xác nhận được kết nối Sandbox. Kiểm tra khóa, kết nối mạng hoặc hỏi hỗ trợ eSMS.');
            return self::FAILURE;
        }
        $this->info('eSMS đã chấp nhận yêu cầu Sandbox. Không có SMS thật và không thể dùng mã kiểm thử để đổi mật khẩu.');
        return self::SUCCESS;
    }
}
