# F08/F10 — checkpoint tiếp theo ngày 05/11/2026

**F08_NOT_READY — F10_BLOCKED.** Đây là checkpoint mới, không thay thế checkpoint trước. Không tuyên bố hoàn tất mọi chức năng F08 hay kiểm thử đủ các viewport.

## Ma trận kết quả trong lượt này

| ID | Nhóm / test case | Expected và actual | HTTP / DB / bằng chứng | Status |
|---|---|---|---|---|
| 01 | Admin và CASHIER gửi POST đồng thời một khoản | Một SUCCESS, một bị từ chối; lần chạy kiểm chứng Admin thắng, CASHIER nhận thông báo tại chi tiết | `python tests/Integration/F08HttpConcurrency.py`; MySQL cô lập; PAID, một giao dịch, đúng collector, một audit đúng actor, không có queue ở fixture phí dịch vụ, không lộ SQL | PASS |
| 02 | HTTP CASHIER hết retry | Ba lần lock timeout, hiển thị một thông báo an toàn | `python tests/Integration/F08HttpRetryExhaustion.py`; MySQL cô lập; ba sự kiện bắt đầu transaction, UNPAID, 0 giao dịch/audit/queue, GET lại thành công | PASS |
| 03 | Patient đặt lịch → tiếp đón → CASHIER → INITIAL_EXAM | Đúng quan hệ, PAID, một queue và audit | `AppointmentChangeTest::test_patient_booking_reception_and_cashier_create_one_queue`, SQLite, 1 test 12 assertions | PASS |
| 04 | Giao dịch trùng chuyển hướng lỗi | Trước sửa có thể trở lại form đã không còn hợp lệ và 404; sau sửa về chi tiết với thông báo | PHPUnit hồi quy và HTTP concurrency hai server | PASS |
| 05 | PDF biên lai A4 | Edge headless đã xuất PDF một trang, MediaBox khoảng 595 × 842 pt | `F08ReceiptPdfFixture.php` và Edge headless, giao dịch CASH giả lập trong MySQL cô lập | PASS chỉ đối với tạo file, kích thước và số trang |
| 06 | Render ảnh PDF, tiếng Việt, bố cục, nội dung dài | Trình xem headless trả ảnh trống; Windows PDF API thử nghiệm không render được; chưa kiểm tra nội dung dài | Không xác nhận các yêu cầu in | UNVERIFIED |
| 07 | Responsive 320/375/390/768/1024/1440 cho từng màn hình | Chưa thực hiện trong lượt này | Các kiểm tra trước không được tính lại là kiểm tra mới | UNVERIFIED |
| 08 | Toàn bộ checklist chức năng F08, CSRF, receipt IDOR, N+1 | Chưa bao phủ đầy đủ từng trường hợp | Xem test đã chạy; không suy luận từ tổng số test | UNVERIFIED |
| 09 | Doctor → dịch vụ → kỹ thuật → công bố → Patient | Checkout thiếu Doctor Portal thực tế | Không tạo giả route/module | BLOCKED |

## Test và môi trường

- `php artisan test` với 12 file F08/F10 liên quan: **83 PASS, 0 FAIL, 792 assertions**, SQLite in-memory, 19,592 giây. Không phải toàn bộ dự án.
- Hai script HTTP ở trên: thực thi thành công trên database MySQL `mediway_f08_isolated_20261105_5978f4ba`; không dùng `mediway` thật. Test exhaustion sử dụng router Laravel test-only đặt timeout mỗi kết nối là một giây; request đi qua kernel, middleware và route thật.
- `npm run build`, `php artisan view:cache` và PHP syntax các file đã kiểm tra: PASS.
- PDF sinh từ giao dịch CASH SUCCESS giả lập; một trang được đếm theo cấu trúc PDF và MediaBox được kiểm tra. Chưa xem được ảnh render, nên không xác nhận A4 về thẩm mỹ, cắt chữ hay nội dung dài.

## Lỗi đã sửa

F08-RACE-01: một request CASHIER thất bại do khoản đã được thu có thể redirect về form bị từ chối bằng 404. Controller hiện chuyển về chi tiết khoản thu, giữ tham số lọc đã cho phép và thông báo validation; test HTTP xác nhận thông báo hiển thị và không lộ SQL. Không sửa luồng thành công hay QR.

## Phụ thuộc và việc còn lại

- Huy: Doctor Portal thực tế cần nhận hàng đợi `INITIAL_EXAM`, xác nhận bác sĩ được phân công, tiếp tục khám và chỉ định trên cùng `medical_visit_id`.
- Owner kỹ thuật / nhóm: hợp đồng chỉ định, kết quả và công bố PRIVATE/PUBLIC/PUBLISHED; xác minh kết quả được công bố đúng quyền và không sửa trái phép.
- F08: xem ảnh PDF thật, nội dung dài, mọi viewport và màn hình, kiểm tra phần còn lại của ma trận, bao gồm cả thời điểm Admin thua tranh chấp. Chưa xác nhận F08_ACCEPTED.
- Database thật không được sử dụng để ghi; không chạy migration, không commit, push, merge, reset hoặc đổi Git index.
