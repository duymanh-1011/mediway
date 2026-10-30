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
        ['slug' => 'kham-suc-khoe-dinh-ky', 'title' => 'Khám sức khỏe định kỳ giúp phát hiện sớm nguy cơ', 'excerpt' => 'Nội dung mẫu cho phiên bản đầu tiên của cổng bệnh nhân.', 'category' => 'Kiến thức sức khỏe', 'published_at' => '27/10/2026', 'body' => 'Đây là bài viết mẫu phục vụ giao diện Patient Portal. Nội dung biên tập chính thức sẽ được bổ sung sau khi có quy trình duyệt tin.'],
        ['slug' => 'chuan-bi-truoc-khi-kham', 'title' => 'Chuẩn bị gì trước khi đến khám', 'excerpt' => 'Một vài lưu ý cơ bản để buổi khám diễn ra thuận lợi.', 'category' => 'Hướng dẫn', 'published_at' => '26/10/2026', 'body' => 'Đây là bài viết mẫu phục vụ giao diện Patient Portal. Người bệnh nên mang theo giấy tờ cần thiết và đến đúng giờ hẹn.'],
        ['slug' => 'cham-soc-suc-khoe-hang-ngay', 'title' => 'Ba thói quen chăm sóc sức khỏe hằng ngày', 'excerpt' => 'Gợi ý đơn giản giúp duy trì một nhịp sống lành mạnh.', 'category' => 'Lối sống', 'published_at' => '25/10/2026', 'body' => 'Đây là bài viết mẫu phục vụ giao diện Patient Portal. Hãy trao đổi trực tiếp với bác sĩ khi cần tư vấn chuyên môn.'],
    ],
];
