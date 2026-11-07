# Checkpoint F08 / F10 — 05/11/2026

**F08: NOT_READY. F10: BLOCKED.** Ma trận dưới đây liệt kê những nhóm đã kiểm thử trong lượt này và các nhóm còn thiếu; không biểu thị toàn bộ các trường hợp yêu cầu đã được thực hiện.

Hồi quy cuối: 74 PASS, 0 FAIL, 729 assertions trên SQLite in-memory (11 file test F08/F10, khoảng 9,7 giây theo PHPUnit). Baseline trước bổ sung là 45 PASS, 354 assertions. MySQL concurrency chạy độc lập; Vite không được chạy lại trong lượt này. Blade cache và `git diff --check` đạt.

| ID | Chức năng / trường hợp | Expected / actual | HTTP / DB và bằng chứng | Trạng thái |
|---|---|---|---|---|
| F08-01 | Các test đã chạy về thu tiền, quyền, filter, ngày hẹn, WALK_IN | Bộ test liên quan đạt; dữ liệu được kiểm tra trong assertion | PHPUnit SQLite in-memory, xem kết quả cuối lượt | PASS |
| F08-02 | Thanh toán lúc 09:00 sau slot 07:00–08:00 cùng ngày | Cho thu; khoản PAID và một giao dịch SUCCESS | `AppointmentPaymentDateTest`, request CASHIER và DB SQLite | PASS |
| F08-03 | Biên lai 16 giá trị 0–1 tỷ; sai role; giao dịch không hợp lệ | Đúng chữ và số; từ chối role / FAILED / QR | `EmployeePaymentReceiptTest`, 2 test, 83 assertions SQLite | PASS |
| F08-04 | XSS, SQL injection qua tìm kiếm | Escape HTML, không trả kết quả do SQL injection | `EmployeePayableReadOnlyTest`, 11 test, 38 assertions SQLite | PASS |
| F08-05 | Hai CASHIER, deadlock, hết retry | Một SUCCESS; một deadlock được retry; ba lock timeout không tạo giao dịch, queue hoặc audit | `tests/Integration/PaymentDateMysqlConcurrency.php`, MySQL `mediway_f08_isolated_20261105_5978f4ba` | PASS |
| F08-06 | Admin và CASHIER đồng thời; HTTP response sau hết retry | Chưa tái hiện bằng hai request HTTP | Chưa có bằng chứng HTTP / DB tương ứng | UNVERIFIED |
| F08-07 | Cột STT biên lai mobile 320px | Cột STT bị ngắt từng chữ trước sửa; sau sửa hiển thị một dòng | Đã xem trình duyệt trên MySQL cô lập trước và sau | PASS |
| F08-08 | Print A4 / PDF / font / số trang | Hộp thoại in của trình duyệt thử nghiệm bị treo; chưa tạo PDF | Chưa có bằng chứng bản in | UNVERIFIED |
| F08-09 | Responsive 375, 390, 768, 1024, 1440; đầy đủ các màn hình F08 | Chưa kiểm tra đủ | Không suy ra từ kiểm tra 320px | UNVERIFIED |
| F08-10 | Các mục chi tiết bộ lọc, receipt IDOR, CSRF, hiệu năng và mọi trường hợp khác trong yêu cầu | Chưa kiểm thử đầy đủ từng trường hợp | Cần tiếp tục test theo danh sách gốc | UNVERIFIED |
| F10-01 | Patient → Reception → thu phí → queue | Có test riêng cho từng phần; chưa xác minh E2E đầy đủ các bước trong lượt này | SQLite tests và MySQL test thanh toán | UNVERIFIED |
| F10-02 | Doctor → Technician → công bố → Patient | Checkout chưa có route `doctor.home`, chưa có luồng công bố thực tế được xác minh | Không tạo module giả | BLOCKED |

## Lỗi và thay đổi

- UI-01: cột STT trong bảng biên lai ở 320px bị thu hẹp, từng ký tự xuống dòng. Sửa CSS trong `resources/views/employee/payments/receipt.blade.php`; đã xem lại trên trình duyệt.
- Bổ sung test trong `tests/Feature/EmployeePaymentReceiptTest.php`, `AppointmentPaymentDateTest.php` và `EmployeePayableReadOnlyTest.php`.
- Chưa chứng minh hay sửa lỗi module Doctor / Technician của thành viên khác. QR không được triển khai.

## Tích hợp và an toàn

Scheduler no-show hiện có đã có test liên quan trong suite; không thay đổi scheduler. MySQL chỉ dùng database thử nghiệm được nêu. Không chạy migration database thật, không thay đổi Git index, không commit, push hoặc merge.

Để nghiệm thu: hoàn thành ma trận F08, kiểm tra in PDF A4 và các viewport, HTTP retry exhaustion, Admin–CASHIER đồng thời, và E2E với Doctor Portal và quy trình công bố kết quả thực tế cùng xác nhận owner.
