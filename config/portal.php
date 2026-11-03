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
        ['slug' => 'kham-suc-khoe-dinh-ky', 'detail_image' => 'images/public/article-kham-suc-khoe-dinh-ky.png', 'detail_image_alt' => 'Người bệnh ghi lại câu hỏi và chuẩn bị thuốc trước buổi khám', 'home_image' => 'images/public/home-kham-suc-khoe-dinh-ky.png', 'image' => 'images/public/prepare-visit.png', 'image_alt' => 'Người bệnh chuẩn bị giấy tờ, thuốc và câu hỏi trước buổi khám', 'title' => 'Chủ động chuẩn bị cho buổi khám', 'excerpt' => 'Những việc bạn có thể chuẩn bị để trao đổi với bác sĩ thuận tiện hơn.', 'category' => 'Hướng dẫn đi khám', 'body' => [
            ['title' => 'Trước buổi khám', 'text' => 'Kiểm tra ngày, giờ và địa điểm trong chi tiết lịch hẹn. Chuẩn bị giấy tờ tùy thân, thông tin bảo hiểm nếu có, kết quả khám trước đây và các tài liệu liên quan. Ghi lại tên, liều lượng thuốc hoặc thực phẩm bổ sung đang sử dụng; thông tin này giúp bác sĩ có thêm cơ sở khi trao đổi cùng bạn.'],
            ['title' => 'Khi gặp bác sĩ', 'text' => 'Ghi lại triệu chứng, thời điểm bắt đầu, những thay đổi gần đây và các câu hỏi bạn muốn trao đổi. Khi gặp bác sĩ, hãy chia sẻ tiền sử bệnh, dị ứng và thuốc đang dùng nếu có. Nếu chưa hiểu hướng dẫn, bạn có thể đề nghị bác sĩ giải thích lại hoặc ghi chú những điểm quan trọng.'],
            ['title' => 'Sau buổi khám', 'text' => 'Đọc lại hướng dẫn sau khám, lưu giữ giấy tờ và thực hiện theo chỉ định của bác sĩ. Kiểm tra tài khoản để xem thông tin đã được cập nhật; kết quả hoặc đơn thuốc chỉ hiển thị khi có dữ liệu phù hợp. Liên hệ cơ sở khám nếu cần làm rõ chỉ định, thời điểm tái khám hoặc có vấn đề phát sinh.'],
        ]],
        ['slug' => 'chuan-bi-truoc-khi-kham', 'detail_image' => 'images/public/article-chuan-bi-truoc-khi-kham.png', 'detail_image_alt' => 'Người bệnh kiểm tra lịch khám trên máy tính', 'home_image' => 'images/public/home-chuan-bi-truoc-khi-kham.png', 'image' => 'images/public/booking-guide.png', 'image_alt' => 'Người bệnh chọn lịch khám trên điện thoại cùng lịch để bàn', 'title' => 'Đặt lịch khám trên Mediway', 'excerpt' => 'Tìm bác sĩ, chọn thời gian và theo dõi lịch hẹn từ tài khoản bệnh nhân.', 'category' => 'Hướng dẫn sử dụng', 'body' => [
            ['title' => 'Tìm bác sĩ và chọn khung giờ', 'text' => 'Đăng nhập tài khoản, mở Lịch khám và chọn Đặt lịch khám. Bạn có thể tìm theo bác sĩ hoặc chuyên khoa, xem thông tin giới thiệu và lịch còn nhận trước khi lựa chọn. Hãy đối chiếu ngày, giờ với kế hoạch cá nhân và chọn khung giờ phù hợp.'],
            ['title' => 'Kiểm tra và xác nhận', 'text' => 'Xem lại bác sĩ, ngày giờ và thông tin bệnh nhân trước khi xác nhận. Nếu hệ thống yêu cầu hoàn thiện hồ sơ, hãy bổ sung các trường cần thiết rồi quay lại bước đặt lịch. Chỉ khi bạn xác nhận và nhận được thông báo thành công, lịch hẹn mới được ghi nhận; hãy kiểm tra lại trong danh sách lịch khám.'],
            ['title' => 'Theo dõi lịch hẹn', 'text' => 'Trong Lịch khám, chọn Xem tất cả lịch hẹn để xem trạng thái và mở chi tiết. Tại đây bạn có thể kiểm tra thời gian, địa điểm và các thao tác đang được phép. Nếu kế hoạch thay đổi, hãy xem lựa chọn hủy hoặc đổi lịch trên chính lịch hẹn đó; khả năng thực hiện phụ thuộc trạng thái và điều kiện của lịch.'],
        ]],
        ['slug' => 'cham-soc-suc-khoe-hang-ngay', 'detail_image' => 'images/public/article-cham-soc-suc-khoe-hang-ngay.png', 'detail_image_alt' => 'Người bệnh xem hồ sơ và kết quả khám tại nhà', 'home_image' => 'images/public/home-cham-soc-suc-khoe-hang-ngay.png', 'image' => 'images/public/records.png', 'image_alt' => 'Tài liệu khám bệnh được chuẩn bị trên bàn', 'title' => 'Quản lý hồ sơ và thông tin sau khám', 'excerpt' => 'Tìm hiểu nơi xem hồ sơ, kết quả và đơn thuốc trong tài khoản của bạn.', 'category' => 'Hướng dẫn sử dụng', 'body' => [
            ['title' => 'Hồ sơ cá nhân', 'text' => 'Mở Hồ sơ cá nhân để kiểm tra họ tên, thông tin liên hệ và những thông tin cần thiết cho việc đặt lịch. Chỉnh sửa các trường được phép và kiểm tra lại trước khi lưu. Một số thông tin định danh được quản lý riêng; xem hướng dẫn trên màn hình hoặc trao đổi với cơ sở khám nếu cần điều chỉnh.'],
            ['title' => 'Kết quả và đơn thuốc', 'text' => 'Từ tài khoản bệnh nhân, mở Kết quả khám bệnh hoặc Đơn thuốc để xem thông tin có sẵn. Chọn nội dung cần xem và đối chiếu với lần khám tương ứng. Dữ liệu chỉ xuất hiện khi đã có thông tin phù hợp trong hệ thống; việc chưa thấy kết quả không đồng nghĩa với một kết luận y khoa.'],
            ['title' => 'Cần giải đáp?', 'text' => 'Nếu nội dung chưa rõ, hãy ghi lại tên thông tin hoặc lần khám liên quan để trao đổi với bác sĩ hay cơ sở khám. Không tự thay đổi thuốc hoặc diễn giải kết quả chỉ dựa vào thông tin trên cổng. Đăng xuất khi dùng thiết bị chung và không chia sẻ mật khẩu hay mã xác thực với người khác.'],
        ]],
        ...require __DIR__.'/health-articles.php',
    ],
];
