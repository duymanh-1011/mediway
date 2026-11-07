# Mediway — checkpoint kiểm thử ngày 05/11/2026

**Kết luận: NOT_READY.** Đây là checkpoint, không phải nghiệm thu toàn hệ thống. Chưa hoàn thành kiểm kê từng chức năng, kiểm thử browser theo các viewport, in A4 hoặc F10 từ đầu đến cuối.

## Kiểm thử có bằng chứng

| Phạm vi | Trạng thái | Bằng chứng / giới hạn |
|---|---|---|
| Bảo vệ trạng thái công bố Patient Portal | PASS có phạm vi | `php artisan test tests/Feature/Portal/ClinicalPublicationSecurityTest.php`: 4 test, 30 assertion trên SQLite in-memory. Kiểm tra PRIVATE, PUBLIC, PUBLISHED, quyền sở hữu, PDF thử nghiệm có thật và schema thực sự thiếu cột. Chưa xác minh mọi bề mặt có thể hiển thị thông tin y tế. |
| Kết quả, tài liệu và đơn thuốc | PASS có phạm vi | Bộ ClinicalPublicationSecurityTest, PatientResultsVisibilityTest, VisitAttachmentsTest và PrescriptionCreationTest: 20 test, 221 assertion SQLite. Fixture được công bố rõ ràng; test kiểm tra PRIVATE trước khi công bố. |
| Tranh chấp thanh toán | PASS có phạm vi | `tests/Integration/PaymentDateMysqlConcurrency.php` trên MySQL `mediway_f08_isolated_20261105_5978f4ba`: hai thu ngân, đổi ngày bằng transaction mô phỏng, deadlock một lần và hết retry do ba lock timeout. |
| Vite / Blade / PHP | PASS có phạm vi | `npm run build`, `php artisan view:cache`, `php -l` cho các file PHP mới sửa. Laravel 12.69.3. |
| Full PHPUnit | PASS có SKIP | Lần chạy cuối: 415 test, 410 PASS, 0 FAIL, 5 SKIP, 7.774 assertion; SQLite in-memory. SKIP không được coi là PASS. |
| Browser | UNVERIFIED | Đã mở màn đăng nhập trên ứng dụng sử dụng MySQL cô lập. Chưa thực hiện E2E sau bản sửa hoặc kiểm tra print A4 và viewport. |
| Doctor Portal / F10 | BLOCKED | Checkout chưa thấy route `doctor.home`; chưa kiểm thử luồng bác sĩ thực tế, công bố kết quả và bàn giao liên module. |
| Authentication, Reception, Technician, Admin, Appointment, Database, Performance | UNVERIFIED | Có test hiện hữu nhưng chưa thực hiện toàn bộ ma trận và các điều kiện nghiệm thu yêu cầu. |

## Defect register

### SEC-01 — lộ kết quả và đơn thuốc PRIVATE

- Mức độ P0. Test mới đã tái hiện response trang kết quả chứa nội dung PRIVATE trước sửa.
- Nguyên nhân: truy vấn Portal và endpoint tài liệu chỉ kiểm tra bệnh nhân sở hữu, không kiểm tra công bố.
- Đã sửa truy vấn kết quả, đơn thuốc, tài liệu trực tiếp và thông báo. Thiếu cột công bố: từ chối hiển thị và ghi lỗi vận hành, không thực hiện migration trên database thật.
- PUBLIC và PUBLISHED được phép theo xác nhận trực tiếp trước đó của người dùng; không suy diễn chúng là cùng trạng thái trong cơ sở dữ liệu.
- Cần tiếp tục kiểm thử toàn bộ bề mặt và quy trình công bố thực tế. Không có bằng chứng triển khai tính năng công bố trong source đã kiểm tra.

### SEC-02 — sửa kết quả đã công bố

- Mức độ P1, **chưa sửa / cần owner kỹ thuật và nhóm duyệt**. `TechnicalServiceController::save` chỉ kiểm tra trạng thái PUBLISHED hoặc verified, chưa thấy kiểm tra PUBLIC. Đây là rủi ro đọc mã, chưa có test request tái hiện; không tuyên bố lỗi đã được xác nhận thực nghiệm.

## Chính sách và phụ thuộc

- Auto no-show đã đọc: query giới hạn lịch BOOKED ngày trước và không có MedicalVisit; chưa hoàn tất mọi test cạnh tranh scheduler.
- Quy trình công bố kết quả, QR xác minh tiền về, Doctor Portal và phê duyệt owner còn thiếu. Không triển khai QR hoặc giả lập Doctor Portal.
- Các thay đổi Git staged, unstaged và untracked tồn tại trước checkpoint cần được giữ nguyên và owner rà soát trước tích hợp. Không commit, merge hoặc push.
- Không thao tác trên database `mediway` thật. Kiểm tra MySQL chỉ dùng database thử nghiệm nêu trên.
- Năm test SKIP trong suite SQLite yêu cầu MySQL: cạnh tranh slot, mã nghiệp vụ, ràng buộc CHECK, đặt lịch Portal và đăng ký bệnh nhân. Chưa coi các bài đó là PASS bởi bài kiểm thử MySQL F08 riêng.

## Việc còn lại

1. Hoàn chỉnh ma trận module/route/quyền và kiểm tra các bề mặt PRIVATE còn lại, bao gồm thông báo và tài liệu theo mọi loại dữ liệu.
2. Owner xác minh SEC-02, trạng thái và quy trình công bố; bổ sung test và sửa trong phạm vi được duyệt.
3. Chạy E2E sau đăng nhập bằng tài khoản thử nghiệm; kiểm tra viewport, Back/Forward, biên lai A4 và PDF.
4. Kiểm thử tiếp nhận, kỹ thuật, admin, authentication, database integrity và performance theo ma trận yêu cầu.
5. Tích hợp Doctor Portal thực tế rồi thực hiện F10 và xin xác nhận các owner; hoàn thành kiểm tra tích hợp và regression trước khi xét nghiệm thu.
