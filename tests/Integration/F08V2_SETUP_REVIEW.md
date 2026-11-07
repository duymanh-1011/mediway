# F08 V2 — gói xem xét, chưa phê duyệt

## Trạng thái

`F08V2_manifest_review_draft.json` là bản nháp sinh từ source, không chọn schema, run hoặc người duyệt. Danh sách FK rỗng và **không được dùng làm manifest phê duyệt**. Hash chứng minh nội dung không đổi, không chứng minh danh tính hay sự đồng ý. Owner phải xem xét nguồn, chọn tên schema chưa từng tồn tại, danh sách FK đầy đủ và xác nhận riêng digest/biên bản; không dựa duy nhất vào biến môi trường.

## Hai giai đoạn

1. **Trước provisioning:** xác nhận owner, tên duy nhất, nội dung và hash migration/DDL, quyền dự kiến; DBA có đủ quyền xem metadata phải xác nhận schema chưa tồn tại. `F08V2PreProvisionReview.py` chỉ kiểm tra hash offline và cố ý trả BLOCKED vì không kiểm tra được sự vắng mặt hay phê duyệt.
2. **Sau provisioning:** dùng kết nối chỉ đọc để kiểm tra server, schema, account thực tế, grants, inventory, InnoDB, migration và FK; DBA/metadata reviewer xác minh độc lập khả năng quan sát và sự vắng mặt của view, trigger, routine, event và writer đồng thời. `F08V2ReadOnlyPreflight.php` cố ý BLOCKED ngay cả khi metadata của fixture account không thấy đối tượng nào, vì tài khoản đó có thể không được thấy đầy đủ. Pre PASS không thay cho post PASS.

## Manifest/schema và FK

Generator rà `Schema::create` và hash toàn bộ file migration; đây là danh sách ứng viên, không phải chứng minh mọi `Schema::table`, SQL động, cột, FK hay thứ tự migration. Chủ trì review phải đối chiếu từng migration thay đổi schema và registry DDL trước khi lập danh sách FK đầy đủ. Fixture cần chuỗi users → roles; employees → users; employee_details → employees/employee_types; doctors → users/specialties; rooms → specialties; schedules → doctors/specialties/rooms; slots → schedules; appointments → patients/doctors/specialties/rooms/slots/statuses; visits → appointment/patient/doctor/specialty/room/status; payables → visit/payment status; transactions → payable/collector; audit → user; registry → run. So sánh cột, nullable, unique và engine với MySQL mới vẫn chưa thực hiện.

Source scan hiện không thấy câu tạo trigger, view, routine hay event rõ ràng trong `database/migrations` hoặc `app`; đây không chứng minh database không có chúng hoặc SQL động không tạo chúng. Xem `information_schema` phải dùng account có đủ quyền hiển thị và kiểm tra writer bên ngoài.

## Quyền dự kiến — chưa cấp

- **DBA/provisioner:** chỉ DBA được owner phê duyệt mới tạo schema và account. Account migration riêng chỉ có quyền DDL/DML tối thiểu trên schema mới; xác định chính xác từ toàn bộ migration trước khi cấp, không `GRANT OPTION`. Registry DDL được review và áp bằng provisioner; fixture account không tạo bảng.
- **Fixture `f08_runner_v2`:** dự kiến `SELECT, INSERT, UPDATE` trên đúng schema mới, bao gồm registry; không global, không schema khác, không role mở rộng. Vì account này ghi được registry, bảo vệ provenance phụ thuộc tách thông tin xác thực, quy trình seed và kiểm soát writer, chưa được chứng minh.
- **Worker `f08_worker_v2`:** dự kiến `SELECT` schema mới và `INSERT`/`UPDATE` chỉ trên các bảng nghiệp vụ cần khóa/ghi; không `INSERT`, `UPDATE` hoặc `DELETE` trên `acceptance_runs`/`acceptance_fixtures`. `F08V2WorkerPrivileges.php` mô tả allowlist dự kiến; cần kiểm thử MySQL thật về quyền `SELECT ... FOR UPDATE`, session và audit trước khi chốt. Worker vẫn khóa.

Các account fixture/worker phải khác provisioner, khác root và chỉ hoạt động với schema mới. Kiểm tra `CURRENT_USER()`, `CURRENT_ROLE()`, `SHOW GRANTS` thực tế bằng từng account. Các dấu `_` trong database grant ở MySQL 8.0.30 cần được escape khi `partial_revokes` OFF; không cấp wildcard hoặc quyền global. Account/host thực tế có thể khác `@localhost` và sẽ bị preflight hiện tại từ chối đến khi được đối chiếu.

## Dự thảo provisioning và xử lý lỗi

`F08V2ProvisionReview.sql` chỉ gồm comment, không thể khởi tạo schema. Không có script provisioning tự động sẵn sàng. Sau khi hoàn thành review, DBA cần chuẩn bị script có kiểm tra schema chưa tồn tại, tạo schema một lần, áp migration từ source đã duyệt không dùng `--seed`, áp registry DDL, cấp quyền riêng, rồi chạy post-provisioning bằng account phù hợp. Không dùng dump, bản ghi nền, ID hoặc truy vấn đến `mediway`/schema F08 cũ. Hiện chưa chứng minh tất cả migration độc lập dữ liệu cũ; đó là điều kiện chặn trước phê duyệt.

Nếu bất kỳ bước thất bại: dừng, bảo toàn schema mới và log, ghi rõ trạng thái hoàn thành từng bước; không tự DROP, xóa, reset hoặc thử lại DDL. Owner/DBA quyết định sau khi xem xét. Không cấp quyền hoặc chạy lệnh thật từ gói nháp này.

## Transaction

Fixture PDO dùng một transaction cho bản ghi và registry trong code, phụ thuộc engine và quyền thực tế. Worker chưa được mở: Laravel có transaction lồng; service có thể chuyển lỗi concurrency thành validation error. Retry ngoài chỉ khả thi khi phân biệt chắc lỗi lock trước commit, rollback đã hoàn tất, và không retry khi kết quả commit không rõ. Chưa có MySQL test chứng minh atomicity, provenance, retry, audit hoặc queue.
