<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

### Thanh toán dịch vụ bằng QR tại quầy

Chạy `npm install`, `npm run build` và `php artisan migrate` để cập nhật dự án. Trên Laragon, thêm `CASHIER_QR_DRIVER=demo` vào `.env` khi `APP_ENV=local`. Sau khi đổi cấu hình, chạy `php artisan config:clear`.

Đăng nhập vào chức năng **Thu ngân**, mở một khoản phí khám hoặc phí dịch vụ và chọn **Thanh toán QR**. Phí khám dùng một mã riêng. Với phí dịch vụ, Mediway tính tổng các dịch vụ được chọn trong cùng lượt khám. Trong chế độ demo, bấm **Mô phỏng đã nhận tiền** để kiểm tra biên lai và trạng thái khoản thu.

Để nhận thanh toán thật, cấu hình `CASHIER_QR_DRIVER=payos`, ba khóa `PAYOS_CLIENT_ID`, `PAYOS_API_KEY`, `PAYOS_CHECKSUM_KEY` và `APP_URL` của máy chủ. Đăng ký URL HTTPS công khai kết thúc bằng `/webhooks/payos` trên trang quản trị payOS. Cài đặt dịch vụ được mô tả trong [tài liệu API payOS](https://payos.vn/docs/api/). Với phiếu **Cần đối soát**, kiểm tra tiền thực nhận tại payOS theo mã tham chiếu trước khi tiếp tục thu.

### Lịch hẹn Patient Portal

Theo cấu hình mặc định, bệnh nhân có thể tự hủy lịch khi còn ít nhất 120 phút trước giờ bắt đầu. Chức năng đổi lịch của bệnh nhân đã được gỡ bỏ. Lịch BOOKED chưa có lượt tiếp nhận được tự hủy khi đã qua giờ bắt đầu lịch hẹn, cộng thời gian chờ nếu có. Với thời gian chờ mặc định 0 phút, lịch 10:00 còn có thể tiếp nhận lúc 10:00:00 và hết hạn ngay sau đó. Có thể cấu hình quyền tự hủy và thời hạn bằng PATIENT_APPOINTMENT_SELF_SERVICE_APPROVED và PATIENT_APPOINTMENT_CANCEL_CUTOFF_MINUTES trong .env.example.

Để tự hủy chạy tự động, môi trường triển khai phải chạy Laravel scheduler mỗi phút (ví dụ `php artisan schedule:run` mỗi phút; môi trường phát triển có thể dùng `php artisan schedule:work`). Lệnh `php artisan appointments:expire-unattended --dry-run` chỉ đếm ứng viên và không thay đổi lịch. Khi bật scheduler trên dữ liệu thật, lịch đủ điều kiện sẽ được hủy và ghi nhật ký.

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
