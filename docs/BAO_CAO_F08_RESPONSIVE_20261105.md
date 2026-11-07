# F08 — checkpoint responsive và ma trận kiểm thử 05/11/2026

**Kết luận: F08_NOT_READY; F10_BLOCKED.** Đây là checkpoint mới; chưa hoàn thành mọi trạng thái, thao tác trực quan và screenshot theo yêu cầu.

## Ma trận kiểm thử

Mỗi dòng chỉ đánh giá phạm vi nêu trong bằng chứng; không suy diễn PASS cho mọi biến thể cùng chức năng.

| ID / chức năng | Expected | Actual / HTTP / DB | Test và bằng chứng | Trạng thái |
|---|---|---|---|---|
| F08-01 Danh sách | Thu ngân thấy khoản thực, người khác không có quyền | HTTP 200/403 hoặc redirect theo role | `EmployeePayableReadOnlyTest`, SQLite | PASS |
| F08-02 Tìm theo mã khoản | Chỉ trả khoản phù hợp | HTTP 200, có khoản đúng, không có khoản khác | `EmployeePayableReadOnlyTest`, SQLite | PASS |
| F08-03 Tìm bệnh nhân / người thu | Tên và mã trả đúng mọi tình huống | Chưa có đủ test riêng cho từng trường và dữ liệu biên | Chưa có bằng chứng đầy đủ | UNVERIFIED |
| F08-04 Bộ lọc kết hợp | Mã + loại + trạng thái + khoảng ngày trả đúng một khoản | HTTP 200, tổng một, không có khoản khác | Test mới `test_combined_filters_return_only_the_matching_unpaid_service_fee`, SQLite | PASS |
| F08-05 Phân trang | 10 khoản/trang, chuyển trang giữ filter, không lặp dòng đầu | HTTP 200, per-page 10, URL giữ `date_from`, trang hai khác | Test mới `test_pagination_limits_to_ten_and_retains_filter_query`, SQLite | PASS |
| F08-06 Back/Forward | Phục hồi bộ lọc và nội dung trước/sau | Đã thao tác browser tại 390px, URL và kết quả khớp | Browser MySQL cô lập | PASS |
| F08-07 Chi tiết / ngày giờ | Dữ liệu dễ đọc, không giữ thụt dòng Blade | Trước sửa ngày giờ tách lệch tại 320px; sau sửa nằm cùng dòng. Mô tả dài cũng không bị thụt đầu dòng ngoài ý muốn. | Screenshot trực tiếp trong tool browser trước/sau; `show.blade.php` | PASS |
| F08-08 Trạng thái giao diện | UNPAID/PENDING/PAID/CANCELLED, trước/quá ngày, rỗng | Browser kiểm tra 11 trang/trạng thái tại từng viewport; không tràn ngang cấp document; khoản PAID/CANCELLED không hiện link thu, ngày sai hiển thị cảnh báo | 66 phép đo DOM thực tế, MySQL cô lập. Không đủ screenshot/đánh giá trực quan toàn trang | UNVERIFIED |
| F08-09 Form / validation / success | Thiếu xác nhận không submit; xác nhận hợp lệ thu một lần | Browser tại 320px chặn checkbox chưa chọn; sau xác nhận có thông báo thành công, 1 giao dịch trong lịch sử | Browser với fixture MySQL cô lập; chưa kiểm tra toàn bộ 6 viewport/states | PASS |
| F08-10 DateGuard | Trước/sau ngày bị chặn; cùng ngày được thu, kể cả sau giờ slot | Test boundary, role và 09:00 sau slot 07–08 đạt | `AppointmentPaymentDateTest`, SQLite | PASS |
| F08-11 WALK_IN | Không cần Appointment giả; tạo phí và queue đúng | Test tiếp nhận và workflow đạt | `WalkInReceptionTest`, SQLite | PASS |
| F08-12 Không thu trùng / audit | Một SUCCESS; đúng collector và audit | SQLite hồi quy; MySQL HTTP CASHIER thắng, Admin bị từ chối | `EmployeePaymentCollectionTest`, `F08HttpConcurrency.py --cashier-wins --csrf` | PASS |
| F08-13 Retry / rollback | Ba lần timeout HTTP, lỗi an toàn; không tạo giao dịch, queue, audit | Script PASS trên MySQL cô lập | `F08HttpRetryExhaustion.py` | PASS |
| F08-14 Receipt / IDOR | Guest, patient, sai role không xem biên lai người khác; FAILED/QR không có phiếu CASH | HTTP redirect/403/404 tương ứng | `EmployeePaymentReceiptTest`, SQLite | PASS |
| F08-15 Phạm vi giữa các cashier | Quy định cashier khác có xem biên lai/khoản của nhau hay không | Code hiện cấp quyền `payment.view` toàn bộ; chưa có quy tắc phân cơ sở được nhóm phê duyệt | Cần quyết định nghiệp vụ, không tự giả định IDOR | UNVERIFIED |
| F08-16 N+1 | Số query không tăng theo số dòng | SQLite test so sánh một dòng và trang nhiều dòng trong ngưỡng đã đặt | `test_paginated_payables_do_not_issue_a_query_per_row`; chưa benchmark MySQL | PASS |
| F08-17 PDF | A4 thường 1 trang, nội dung dài không mất | Không sửa CSS receipt trong lượt này; bằng chứng PDFium của checkpoint trước còn nguyên | `BAO_CAO_F08_F10_QA_20261105.md` | PASS |
| F08-18 Responsive đầy đủ | Lưu screenshot từng màn hình và viewport, xem font/menu/bảng/form/trạng thái | Đã đo DOM 66 trường hợp và xem trực quan một số màn hình; chưa lưu đủ screenshot từng cặp màn hình/viewport, chưa xem đủ lỗi/thành công và thao tác tại từng cỡ | Browser automation; không coi đo DOM là kiểm tra trực quan đầy đủ | UNVERIFIED |
| F08-19 Bộ lọc, phân quyền và các trường hợp còn lại | Toàn bộ checklist đã giao được test riêng với dữ liệu và trạng thái phù hợp | Chưa đủ từng tổ hợp, quyền/IDOR, validation, thông báo và kiểm thử giao diện | Cần bổ sung | UNVERIFIED |
| F10-01 Doctor Portal | Bàn giao queue tới bác sĩ rồi thực hiện toàn bộ workflow thực | Checkout thiếu Doctor Portal; không tạo module giả | Đã có test Patient → Reception → CASHIER → INITIAL_EXAM | BLOCKED |

## Sáu viewport

Ở mỗi viewport 320, 375, 390, 768, 1024, 1440: đã dùng browser thực trên MySQL cô lập kiểm tra 11 URL/trạng thái: danh sách, UNPAID, PENDING, PAID, CANCELLED, trước ngày, quá ngày, dữ liệu dài, form, biên lai, kết quả lọc rỗng. Không có phép đo nào phát hiện `document.scrollWidth > window.innerWidth`; các kiểm tra trạng thái đã nêu đều đạt. Bảng có vùng cuộn ngang riêng. Các phép đo này **không chứng minh** toàn bộ chữ, nút và trạng thái trực quan đạt.

Chưa lưu được bộ screenshot thật cho từng màn hình × viewport qua bề mặt browser hiện có; vì vậy từng viewport vẫn **UNVERIFIED đối với yêu cầu nghiệm thu responsive đầy đủ**. Đã trực quan kiểm tra lỗi ngày giờ trước/sau tại 320px, dữ liệu dài, một phần các kích thước khác, validation checkbox và thanh toán thử nghiệm tại 320px.

## Hồi quy lượt này

- `php artisan test` trên 12 file F08/F10 liên quan: **88 PASS, 0 FAIL, 830 assertions**, SQLite in-memory, 28,354 giây; không phải toàn bộ dự án.
- `F08HttpRetryExhaustion.py`: PASS; `F08HttpConcurrency.py --cashier-wins --csrf`: lần đầu HTTP 500 khi chạy cùng `npm run build` do manifest Vite đang bị thay thế; log ghi thiếu manifest. Chạy lại tuần tự sau build: PASS, HTTP CSRF 419 và CASHIER thắng. Không xác định đây là lỗi nghiệp vụ F08.
- `php artisan view:cache` và `npm run build`: PASS. Không sửa CSS receipt, nên PDF không chạy lại.
- MySQL ghi chỉ dùng database `mediway_f08_isolated_20261105_5978f4ba`; dữ liệu UI là fixture giả lập thêm mới. Không ghi database thật, không đổi Git index, không commit/push/merge/reset.

## File thay đổi trong lượt

- `resources/views/employee/payments/show.blade.php`: bỏ giữ khoảng trắng thụt lề trong thẻ thông tin, giữ xuống dòng thật trong mô tả/ghi chú.
- `tests/Feature/EmployeePayableReadOnlyTest.php`: test bộ lọc kết hợp và phân trang.
- `tests/Integration/F08ResponsiveFixture.php`: fixture UI có chốt tên database cô lập.
- Báo cáo mới này.

Để đề xuất F08_ACCEPTED cần hoàn thành screenshot và đánh giá thủ công cho từng màn hình/viewport và trạng thái, các mục UNVERIFIED còn lại và quyết định về phạm vi giữa cashier. F10 vẫn BLOCKED đến khi Doctor Portal thật được bàn giao và tích hợp.
