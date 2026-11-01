<?php

return [
    'demo_otp_phone' => env('PORTAL_DEMO_OTP_PHONE', '0902000001'),
    'reset_grant_minutes' => 15,
    'esms' => [
        'enabled' => (bool) env('ESMS_ENABLED', false),
        // Only an explicit false/0 enables real test SMS; missing or blank stays sandboxed.
        'sandbox' => ! in_array(env('ESMS_SANDBOX', true), [false, 0, '0'], true),
        'api_key' => env('ESMS_API_KEY'),
        'secret_key' => env('ESMS_SECRET_KEY'),
    ],
    'news' => [
        ['slug' => 'kham-suc-khoe-dinh-ky', 'title' => 'Chủ động chuẩn bị cho buổi khám', 'excerpt' => 'Những việc bạn có thể chuẩn bị để trao đổi với bác sĩ thuận tiện hơn.', 'category' => 'Hướng dẫn đi khám', 'body' => [
            ['title' => 'Trước buổi khám', 'text' => 'Kiểm tra ngày giờ và phòng khám trong lịch hẹn. Chuẩn bị giấy tờ cần thiết, danh sách thuốc đang dùng và các tài liệu liên quan nếu bạn có.'],
            ['title' => 'Khi gặp bác sĩ', 'text' => 'Ghi lại những vấn đề bạn muốn trao đổi và cung cấp thông tin đầy đủ để bác sĩ có cơ sở tư vấn phù hợp.'],
            ['title' => 'Sau buổi khám', 'text' => 'Thực hiện theo hướng dẫn của bác sĩ và hỏi lại cơ sở khám nếu bạn cần làm rõ thông tin.'],
        ]],
        ['slug' => 'chuan-bi-truoc-khi-kham', 'title' => 'Đặt lịch khám trên Mediway', 'excerpt' => 'Tìm bác sĩ, chọn thời gian và theo dõi lịch hẹn từ tài khoản bệnh nhân.', 'category' => 'Hướng dẫn sử dụng', 'body' => [
            ['title' => 'Tìm bác sĩ và chọn khung giờ', 'text' => 'Đăng nhập tài khoản, mở Lịch khám và chọn Đặt lịch khám. Tìm bác sĩ hoặc chuyên khoa, sau đó chọn khung giờ còn nhận lịch.'],
            ['title' => 'Kiểm tra và xác nhận', 'text' => 'Xem lại thông tin lịch hẹn và bổ sung hồ sơ nếu hệ thống yêu cầu. Chỉ khi bạn xác nhận và hệ thống thông báo thành công, lịch hẹn mới được ghi nhận.'],
            ['title' => 'Theo dõi lịch hẹn', 'text' => 'Trong Lịch khám, chọn Xem tất cả lịch hẹn để xem chi tiết và các thao tác có thể thực hiện với từng lịch.'],
        ]],
        ['slug' => 'cham-soc-suc-khoe-hang-ngay', 'title' => 'Quản lý hồ sơ và thông tin sau khám', 'excerpt' => 'Tìm hiểu nơi xem hồ sơ, kết quả và đơn thuốc trong tài khoản của bạn.', 'category' => 'Hướng dẫn sử dụng', 'body' => [
            ['title' => 'Hồ sơ cá nhân', 'text' => 'Mở Hồ sơ cá nhân để xem thông tin và chỉnh sửa những trường được phép. Một số thông tin định danh được quản lý riêng; hãy liên hệ hỗ trợ nếu cần điều chỉnh.'],
            ['title' => 'Kết quả và đơn thuốc', 'text' => 'Từ tài khoản bệnh nhân, mở Kết quả khám bệnh hoặc Đơn thuốc để xem thông tin có sẵn trong hồ sơ của bạn. Nội dung chỉ xuất hiện khi hệ thống có dữ liệu phù hợp.'],
            ['title' => 'Cần giải đáp?', 'text' => 'Nếu thông tin chưa rõ ràng hoặc bạn có câu hỏi liên quan đến chăm sóc, hãy trao đổi với bác sĩ hoặc cơ sở khám.'],
        ]],
    ],
];
