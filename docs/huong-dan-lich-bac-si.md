# Hướng dẫn lịch làm việc bác sĩ

## Chuẩn bị

- PHP cần bật phần mở rộng ZIP để đọc và tạo tệp Excel. Kiểm tra bằng `php -m | findstr /i zip` trong PowerShell. Nếu không hiện `zip`, bật `extension=zip` trong tệp được chỉ bởi `php --ini`, rồi mở lại PowerShell.
- Cài các thư viện theo `composer.lock` bằng `composer install`; biên dịch giao diện bằng `npm run build` khi cần.
- Kiểm tra bản sao lưu cơ sở dữ liệu và kết nối `.env` trước khi áp dụng thay đổi cấu trúc. Các lệnh dưới đây không tự chạy khi mở trang web.

Migration `2026_11_10_000000_make_appointment_slots_unlimited.php` đổi sức chứa tất cả khung thành không giới hạn, chuyển trạng thái `FULL` cũ sang `ACTIVE`, giữ nguyên mã khung, thời gian và liên kết lịch hẹn. Các khung `INACTIVE` vẫn đóng. Không thể khôi phục sức chứa cũ bằng `migrate:rollback`; muốn phục hồi cần bản sao lưu. Nếu đã xem xét và chủ động quyết định áp dụng trên cơ sở dữ liệu đang kết nối, chạy riêng:

```powershell
php artisan migrate --path=database/migrations/2026_11_10_000000_make_appointment_slots_unlimited.php
```

Không dùng `migrate:fresh`, `migrate:refresh`, `db:seed` hoặc `composer run setup` trên cơ sở dữ liệu cần giữ. Chỉ chạy migration sau khi xác nhận đúng cơ sở dữ liệu và đã sao lưu.

## Tạo lịch và nhập Excel

Vào **Quản trị → Lịch làm việc bác sĩ**. Chọn **Thêm lịch làm việc**, nhập bác sĩ, chuyên khoa, phòng và ngày, rồi chọn ca. Sáng 07:00–11:30 tạo chín khung; chiều 13:30–16:30 tạo sáu khung. Mỗi khung dài 30 phút. Tạo riêng hai ca nếu bác sĩ làm cả ngày.

Để nhập nhiều lịch, chọn **Tải mẫu Excel**. Giữ nguyên sáu cột và tiêu đề:

| Mã bác sĩ | Ngày làm việc | Ca | Mã chuyên khoa | Mã phòng | Ghi chú |
|---|---|---|---|---|---|
| BS000001 | 15/12/2026 | Sáng | CKTMH | PKTMH01 | Có thể để trống |

Các mã trong ví dụ chỉ dùng khi chúng thực sự tồn tại và phù hợp trong cơ sở dữ liệu của bạn. Nhập ngày theo dạng `dd/mm/yyyy` hoặc ô ngày Excel; cột ca ghi `Sáng` hoặc `Chiều`. Một hàng biểu diễn một ca, không để hàng trống hoặc dùng công thức. Tối đa 1.000 hàng và tệp `.xlsx` 2 MB. Nhập lịch hợp lệ sẽ có hiệu lực ngay. Khi có lỗi, trang báo số dòng và không lưu bất kỳ dòng nào.

Lịch chưa có hẹn có thể đổi sang ca khác; hệ thống kiểm tra trùng và tạo lại khung. Lịch đã có hẹn không được đổi bác sĩ, chuyên khoa, phòng, ngày hay ca. Lịch cũ có giờ khác ca chuẩn được giữ giờ thực tế khi chọn **Giữ giờ hiện tại**.

## Bệnh nhân và tiếp nhận

Bệnh nhân chọn bác sĩ và khung giờ tương lai đủ điều kiện. Nhiều bệnh nhân có thể đăng ký cùng khung; một bệnh nhân không được đăng ký trùng chính khung đó. Khung giờ là giờ đăng ký đến khám, không đảm bảo bắt đầu khám ngay. Thứ tự khám vẫn theo số hàng đợi được cấp sau tiếp nhận và thanh toán theo quy trình hiện tại.

## Chạy kiểm thử

Bộ kiểm thử mặc định dùng SQLite trong bộ nhớ; cần bật `pdo_sqlite` và `zip`:

```powershell
php -d extension=pdo_sqlite -d extension=zip vendor/phpunit/phpunit/phpunit tests/Feature/DoctorShiftServiceTest.php tests/Feature/DoctorScheduleImportServiceTest.php tests/Feature/DoctorScheduleImportValidationTest.php tests/Feature/DoctorScheduleUnlimitedMigrationTest.php tests/Feature/Portal/UnlimitedAppointmentBookingTest.php
```

Kiểm thử đồng thời trên MySQL chỉ được chạy trên cơ sở dữ liệu thử nghiệm riêng được cấu hình cho bài kiểm thử, tuyệt đối không trỏ vào `mediway` chính.
