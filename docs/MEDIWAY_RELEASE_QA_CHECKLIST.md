# Mediway — checklist kiểm thử trước khi chốt dự án

Ngày rà soát: 14/11/2026
Phạm vi: mã nguồn trên nhánh khanhlinh, gồm phần thay đổi đang merge từ main.

## Cách đọc trạng thái

- [x] Đã chạy/đối chiếu bằng bằng chứng hiện có.
- [!] Có lỗi hoặc điểm không nhất quán đã xác nhận trong mã/test.
- [ ] Còn phải kiểm thử hoặc xác nhận trước khi phát hành.

## Kết luận hiện tại

**Chưa đủ bằng chứng để chốt phát hành.** Sau các sửa đổi cuối, toàn bộ `tests/Feature` đạt: 601 bài, 583 đạt, 18 bỏ qua, 0 thất bại và 10.414 assertions. 18 bài bỏ qua vẫn cần môi trường MySQL/hai kết nối hoặc `ext-zip`; các kiểm tra staging và nghiệm thu trình duyệt/in vẫn còn mở.

Các test cũ phụ thuộc workspace đã được cập nhật ở những nhóm đã rà soát. Đặt lịch, tiếp nhận, thanh toán, luồng bác sĩ và mẫu kết quả đều đã chạy theo từng nhóm. Chưa chạy migration trên MySQL/staging, chưa thử race condition bằng hai kết nối thật và chưa duyệt từng trang/hộp thoại in bằng trình duyệt.

Checklist theo từng tác nhân và giao diện phía dưới là các mục nghiệm thu thủ công còn lại, không phải khẳng định mọi tình huống đã được kiểm thử tự động. Chỉ đánh dấu hoàn tất khi có bằng chứng chạy trên staging hoặc trình duyệt phù hợp.

## Những gì đã kiểm tra

| Hạng mục | Kết quả |
|---|---|
| Bộ PHP `tests/Feature`, SQLite trong bộ nhớ | 601 bài: 583 đạt, 18 bỏ qua, 0 thất bại; 10.414 assertions. Chạy sau các sửa đổi cuối bằng runner tạm xử lý lỗi `is_readable()` của PHP Windows; runner đã được dọn sau khi chạy. |
| Đặt lịch/chọn khung/giờ tiếp nhận | AppointmentBookingSelectionTest, AppointmentPaymentDateTest, AppointmentReceptionRulesTest: 27/27 đạt. Các test đặt lịch/đổi lịch/mua thuốc bị ảnh hưởng sau cập nhật: 28/28 đạt. |
| Tiếp nhận và phân quyền workspace nhân viên | EmployeePortalFoundationTest + WalkInReceptionTest: 41/41 đạt. |
| Thanh toán | EmployeePayableReadOnlyTest + EmployeePaymentCollectionTest + EmployeePaymentReceiptTest: 43/43 đạt. |
| Luồng bác sĩ khám bệnh | 59/59 đạt ở nhóm D01, khám, chẩn đoán, kê đơn, kết luận, quyền truy cập và hoàn tất. |
| Thực hiện dịch vụ, mẫu kết quả, tệp | EmployeeTechnicalParametersTest: 27/27; ServiceResultTemplate/ServiceCatalog/ClinicalCompletion: 17/17; VisitAttachments + ClinicalDocument: 16/16. |
| Lịch bác sĩ, phân công phòng/chuyên khoa và import | 35 bài: 24 đạt, 11 bỏ qua do thiếu `ext-zip` để đọc Excel. Chưa kiểm thử import XLSX thực tế. |
| Tự hủy lịch, cạnh tranh ghi và ràng buộc dữ liệu | 15 bài: 11 đạt, 4 bỏ qua vì cần MySQL/hai kết nối hoặc DDL MySQL. |
| Hồ sơ bệnh nhân, tích hợp nhóm và hàng đợi | 26/26 đạt. |
| Audit trước phát hành và khóa cấu hình quản trị | 28/28 đạt. |
| Admin, màn hình bảng và workflow | 64 bài, 63 đạt, 1 bỏ qua do thiếu ext-zip. |
| Kiểm thử JavaScript giao diện | 32/32 đạt bằng `node --test tests/*.test.mjs`. |
| Build Vite | Thành công; xử lý 40 modules. |
| MySQL, concurrency thật, migration trên staging | Chưa chạy để tránh tác động database chưa xác nhận là bản sao. |
| Duyệt trình duyệt/điện thoại và hộp thoại in | Chưa thực hiện trong lượt rà soát này. |

Các nhóm JavaScript đạt gồm menu responsive, thông báo lỗi nhập liệu, chọn bác sĩ/chuyên khoa/phòng, lịch đặt khám, bộ chọn dịch vụ, khôi phục ảnh tải lên, đồng bộ thông báo/thanh toán portal và điều hướng lịch sử trình duyệt.

## Các điểm cần xử lý trước khi phát hành

### P0 — Chặn chốt

- [x] Chạy lại toàn bộ 601 test sau bản sửa cuối: 583 đạt, 18 bỏ qua, 0 thất bại; 10.414 assertions.
- [x] Rà soát redirect/workspace ở các nhóm tiếp nhận, thu ngân, bác sĩ khám và bác sĩ dịch vụ đã chạy lại; không tính redirect về màn chọn chức năng là thành công của test nghiệp vụ.
- [ ] Chạy nghiệp vụ trên bản sao MySQL/staging, gồm migrate, dữ liệu lịch cũ, khóa ngoại, race condition và thanh toán. Không chạy lên DB thật trước khi có kế hoạch triển khai và backup được xác nhận.
- [ ] Duyệt thủ công các luồng chính trên desktop và điện thoại, gồm kết quả, hóa đơn, đơn thuốc, phiếu khám và hộp thoại in.

### P1 — Sai khác hoặc rủi ro nghiệp vụ đã xác nhận

- [x] **Cửa sổ đặt lịch:** portal và admin cùng dùng `Appointment::BOOKING_ADVANCE_DAYS = 4`; portal chặn ngoài hôm nay + 4 ngày và giới hạn giờ theo mốc 13:30. Test biên đã cập nhật.
- [x] **Kiểu trường “Bảng chỉ số”:** validator cho phép `measurements`; lưu, render và test kết quả mẫu hoạt động.
- [!] **Gán mẫu kết quả cho dịch vụ hiện có:** đã thêm seeder không ghi đè mẫu và lệnh xem trước/áp dụng có `--apply`. Chưa kiểm kê hoặc chạy áp dụng trên database thực tế; nhóm thiếu, nhiều nhóm hoặc mã chưa biết vẫn cần Admin gán trong cấu hình.
- [!] **Tự hủy lịch:** lệnh tự hủy sau 15 phút và đăng ký scheduler đã có; cần xác nhận cron, timezone và nhật ký trên staging.
- [x] **Test bỏ qua:** đã phân loại lý do trong source test: MySQL/concurrency cần hai kết nối, ext-zip, hoặc DDL không hỗ trợ trên SQLite; các test bị skip vẫn cần chạy ở môi trường phù hợp trước khi phát hành.
- [x] **Admin test về cột bảng:** AdminTableNumberingTest đạt trong nhóm admin sau cập nhật.
- [x] **AdminViewsTest:** test và fixture cho màn chỉnh mẫu đã có; nhóm AdminViewsTest chạy trong batch admin.

### P2 — Cần phân loại cùng các test đang lỗi

- [x] AppointmentBookingSelectionTest và AppointmentReceptionRulesTest đã chạy: 27/27 đạt; giới hạn ngày +4/check-in sớm 60 phút đã có test.
- [x] EmployeePortalFoundationTest đã chạy trong workspace fixture: 41/41 đạt cùng WalkInReceptionTest.
- [x] Các nhóm workflow bác sĩ, tiếp nhận, thanh toán và thực hiện dịch vụ được chạy lại; full suite sau bản sửa cuối đạt, không có lỗi runtime.
- [x] Những test status/403/404/422, xác nhận form và giao dịch đã chạy trong các nhóm đã nêu; full suite sau bản sửa cuối không còn lỗi.

## Ghi chú về test bị bỏ qua

Các bài bị bỏ qua đều cần môi trường khác SQLite bộ nhớ: hai kết nối MySQL để thử cạnh tranh ghi, MySQL riêng để thử ràng buộc CHECK/DDL, hoặc extension `zip` để đọc Excel. Có thêm một bài cần build assets và dừng Vite dev server theo điều kiện của test. Chúng chưa thay thế cho kiểm tra MySQL và XLSX trên môi trường phù hợp.

## Checklist nghiệp vụ theo tác nhân

### Bệnh nhân

- [ ] Đăng ký/đăng nhập bằng điện thoại; đối chiếu CCCD với hồ sơ cũ: liên kết nếu hồ sơ chưa có tài khoản, từ chối nếu đã có tài khoản, tạo hồ sơ mới nếu CCCD chưa tồn tại.
- [ ] Hồ sơ có CCCD, ngày sinh, giới tính; kiểm tra định dạng, trùng CCCD/điện thoại, Unicode và không lộ mật khẩu tạm.
- [ ] Tra cứu bác sĩ theo chuyên khoa/phòng; ca sáng 07:30–11:30, chiều 13:30–17:00; slot 30 phút; không từ chối vì max_patients nhưng vẫn đếm lượt.
- [ ] Đặt được hôm nay và 4 ngày kế; không đặt ngày thứ 5. Trước 13:30 chỉ đặt ca chiều hôm nay; từ 13:30 chỉ đặt ca sáng ngày mai.
- [ ] Thử đặt đồng thời một slot, đặt trùng, đổi/hủy lịch, slot ngừng hoạt động và form cũ; không tạo dữ liệu một phần.
- [ ] Lịch vắng mặt hủy sau 15 phút từ giờ bắt đầu; check-in hợp lệ tối đa sớm 1 giờ; lịch đã tiếp nhận không bị auto-cancel.
- [ ] Chỉ xem kết quả dịch vụ khi hoàn tất; kiểm tra quyền xem file, đơn thuốc, kết luận và lịch sử.
- [ ] Kiểm tra màu trạng thái, nhãn tiếng Việt, empty/loading/error, lọc, phân trang, menu mobile, focus/keyboard và cập nhật thanh toán.

### Nhân viên tiếp đón

- [ ] Đăng nhập thấy hai thẻ chức năng, chỉ chọn một workspace; không mở workspace thu ngân bằng URL hoặc session giả.
- [ ] Chọn hồ sơ hiện có tự điền đúng tên, ngày sinh, giới tính, CCCD đầy đủ, điện thoại và địa chỉ.
- [ ] Tạo hồ sơ mới: CCCD bắt buộc; CCCD trùng liên kết đúng hồ sơ; tài khoản trùng được báo rõ; tài khoản và lượt khám nhất quán.
- [ ] Tiếp nhận trực tiếp chọn phòng khám, lọc phòng/chuyên khoa/bác sĩ; số lượt hiện tại chính xác; không chọn phân công không hoạt động.
- [ ] Check-in đúng ngày và cửa sổ 1 giờ; chống tiếp nhận trùng; lỗi cấu hình không tạo visit, hàng đợi hoặc audit một phần.
- [ ] In phiếu khám: mã bệnh nhân/lượt khám, thông tin bệnh nhân, lý do, phòng, phí, số thứ tự lớn; header/chân phiếu căn giữa và khổ giấy hẹp đúng thiết kế.
- [ ] Mật khẩu tạm chỉ hiện cho nhân viên khi giao; nút in chỉ in nội dung cần giao; không lưu mật khẩu trong log hoặc sau khi rời màn.

### Nhân viên thu ngân

- [ ] Workspace riêng; không truy cập tiếp đón hoặc kỹ thuật bằng URL trực tiếp.
- [ ] Tìm khoản thu theo mã, tên, CCCD, điện thoại; lọc phí khám, dịch vụ, thuốc, trạng thái, phương thức và khoảng ngày.
- [ ] Thu đúng số còn nợ; ngăn thu trùng/sai ngày/hủy trái phép; giao dịch và payable cùng commit hoặc rollback.
- [ ] Phí khám, dịch vụ, thuốc dùng chung cấu trúc hóa đơn; mã thu, người thu, phương thức, tổng tiền, tiền bằng chữ, trạng thái khớp dữ liệu.
- [ ] Kiểm tra in lại, hai request thu đồng thời, thanh toán lỗi, hủy khoản phải thu và audit.

### Bác sĩ khám bệnh

- [ ] Màn chọn chức năng có Bác sĩ khám bệnh và Bác sĩ thực hiện dịch vụ; lựa chọn khóa theo phiên; không đổi role bằng URL.
- [ ] Luồng khám: phòng, hàng đợi, gọi số, bắt đầu, nội dung khám, chẩn đoán, kết luận, kê đơn, chỉ định dịch vụ, hoàn tất và quay lại sau dịch vụ.
- [ ] Trạng thái nhất quán: WAITING, IN_EXAMINATION, WAITING_FOR_SERVICE, RETURNING, COMPLETED giữa backend, badge, thông báo và phiếu.
- [ ] Truy cập đúng lượt; xác nhận quy tắc xem/ghi hồ sơ bác sĩ khác và quyền kê đơn/chỉnh sửa.
- [ ] Kết luận, tái khám/lý do, đơn thuốc và chỉ định khóa/mở đúng thứ tự; request thiếu xác nhận, stale update hoặc sai dữ liệu không làm mất nội dung.
- [ ] Phiếu kết quả và đơn thuốc chung header/thông tin bệnh nhân/chẩn đoán/chữ ký; nội dung kết quả có Kỹ thuật và Kết luận.

### Bác sĩ thực hiện dịch vụ

- [ ] Danh sách đúng workspace/phạm vi; tìm theo mã/tên/điện thoại; bỏ bộ lọc người thực hiện nếu không thuộc thiết kế.
- [ ] Chỉ thực hiện khi khoản thu cho phép; kiểm tra bắt đầu, lưu nháp, xác nhận, hoàn tất và quyền sửa sau lưu.
- [ ] Trước khi lưu có xác nhận; sau lưu chỉ xem kết quả đã lưu; đường sửa kết quả có audit.
- [ ] Phiếu ghi “Kết quả — tên dịch vụ” cỡ lớn căn giữa; Kỹ thuật và Kết luận in đậm, không đánh số; dòng thông tin thụt lề thống nhất.
- [ ] Kết quả khớp phiếu in và portal; kiểm tra file, tiếng Việt, bảng chỉ số/đơn vị/tham chiếu và nội dung dài.
- [ ] Mọi dịch vụ hoạt động có mẫu; kiểm tra dịch vụ không nhóm/nhiều nhóm/mã lạ/mẫu ngừng hoạt động; sửa mẫu không đổi phiếu lịch sử.

### Quản trị

- [ ] CRUD tài khoản, quyền, trạng thái, bệnh nhân, bác sĩ, nhân viên, chuyên khoa, phòng, dịch vụ, nhóm, thuốc, bệnh và trạng thái.
- [ ] Admin chỉ quản lý cấu hình/giám sát; không phải bước duyệt hoặc công bố thao tác nghiệp vụ.
- [ ] Import Excel lịch: preview; chọn phòng/khoa khi thiếu; giữ ngày ngoài file; xung đột chặn toàn bộ lần import.
- [ ] Ca cũ shift_code NULL vẫn giữ 08:00–11:00, không chồng giờ; không đổi nghĩa ACTIVE/INACTIVE; tối đa một ca sáng chuẩn và một ca chiều chuẩn mỗi bác sĩ/ngày.
- [ ] Kiểm tra unique index NULL trên MySQL, giao nhau giờ, transaction khi import đồng thời; index không thay thế kiểm tra overlap.
- [ ] Hàng đợi tăng số theo ngày; hoàn tất rời hàng đợi hiện hành nhưng giữ lịch sử; quay lại sau dịch vụ giữ số cũ; ngày mới reset số.
- [ ] Thay đổi cấu hình/status có xác nhận, validate FK và audit; ngừng/xóa danh mục không làm mất lịch, giao dịch, kết quả lịch sử.

## Checklist đồng bộ giao diện và trạng thái

- [ ] Dùng token màu thống nhất cho thương hiệu, nền, viền, chữ, trạng thái, focus và lỗi giữa admin, portal, employee, doctor và phiếu in.
- [ ] Mỗi trạng thái có một nhãn, màu và quy tắc chuyển dùng chung ở danh sách, chi tiết, dashboard, thông báo và bản in.
- [ ] Đối chiếu font, cỡ chữ, khoảng cách, lề, chiều rộng, tiêu đề, breadcrumb, bảng, biểu mẫu, nút và badge giữa các vai trò.
- [ ] Mọi nút/link có đích đúng quyền, trạng thái loading/disabled; không còn href giả hoặc nhãn tiếng Anh ngoài chủ đích.
- [ ] Desktop/tablet/điện thoại không tràn ngang; bảng cuộn được; menu/modal không che nội dung, giữ focus và hỗ trợ bàn phím.
- [ ] Kiểm tra empty/loading/error/success, phân trang, lọc giữ query, validation tiếng Việt và giữ dữ liệu nhập khi lỗi.
- [ ] In thử phiếu khám, đơn thuốc, kết quả, hóa đơn khám và hóa đơn dịch vụ: giấy, font Việt, lề, ngắt trang, chữ ký, số thứ tự, không in giao diện điều khiển.
- [ ] Kiểm tra tương phản màu, focus, label input, heading và accessible name cho icon/nút.

## Thứ tự hoàn tất đề xuất

1. Sửa validator measurements và thống nhất cửa sổ đặt lịch portal/admin; khóa bằng test biên.
2. Cập nhật test/fixture workspace; phân loại lỗi runtime/assertion còn lại; chạy lại suite PHP mặc định.
3. Trên bản sao DB, lập danh sách dịch vụ chưa gán mẫu; chuẩn bị backfill có preview và rollback. Không chạy trên DB thật trong kiểm thử.
4. Chạy MySQL migration/concurrency/transaction tests trên database riêng; xác nhận lịch cũ và unique index theo hành vi MySQL.
5. Duyệt thủ công mọi màn hình và phiếu in trên desktop/mobile, ghi nhận lỗi còn lại.
6. Trên staging, xác nhận scheduler mỗi phút; auto-cancel 15 phút, check-in sớm 60 phút, timezone, log và backup/khôi phục.
7. Chốt khi P0 đóng, ngoại lệ được giải thích, migrations/triển khai được rà soát và dữ liệu staging được xác nhận.

## Giới hạn của lần rà soát

- Không đọc/ghi database thật, không chạy migration, không xóa dữ liệu, không commit/push/hoàn tất merge.
- Bộ PHP mặc định dùng SQLite trong bộ nhớ; không thay thế kiểm tra MySQL, khóa đồng thời hay unique index MySQL.
- Lượt full suite cuối có 601 bài, 583 đạt, 18 bỏ qua, 0 thất bại và 10.414 assertions. PHP Windows trả `is_readable() = false` với file tồn tại, nên đã dùng runner tạm chỉ thay cách PHPUnit kiểm tra file bootstrap từ `is_readable` sang `is_file`; không thay cấu hình hoặc mã ứng dụng. Runner đã được xóa sau khi chạy.
- `AdminFreezeAuditTest` nằm trong batch audit chạy riêng; batch `PreReleaseAuditTest + AdminFreezeAuditTest` đạt 28/28.
- Workspace đang có merge chưa commit với MERGE_HEAD 825d3118c3c4f075077efe839e552d449ba020d4; các thay đổi đã stage từ trước. Lượt rà soát không hoàn tất merge.
- Chưa xem giao diện qua trình duyệt thật và chưa xác nhận dữ liệu ứng dụng hiện tại đã được gán đủ mẫu kết quả.
