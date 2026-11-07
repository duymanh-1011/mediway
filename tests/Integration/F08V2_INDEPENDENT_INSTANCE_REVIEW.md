# F08 V2: review instance độc lập

Tài liệu này không phê duyệt chạy lệnh ghi. Trong lượt này không tạo instance, schema, account, migration hoặc fixture. Worker và runner ghi vẫn khóa.

## Phạm vi và giới hạn kiểm tra

Chạy `python tests/Integration/F08V2MigrationScopeScan.py` chỉ đọc source và in kết quả JSON. Các dòng match cần đọc cả `up`, `down` và helper; absence of matches không chứng minh an toàn. `F08V2_MIGRATION_MANUAL_REVIEW.md` ghi bốn migration ưu tiên và các thao tác ghi vào schema hiện tại. Đặc biệt `strengthen_clinical_data_constraints.php:68-71` đọc metadata theo tên database hiện hành và tạo ALTER CHECK động; `:83` thuộc rollback. Các migration normalize, service-group, employee-type và specialty cập nhật hoặc thêm dữ liệu trong schema đang kết nối; chúng chỉ phù hợp khi schema thực sự mới và instance không chứa dữ liệu thật. FK chưa chứng minh được đánh dấu UNKNOWN; phải đối chiếu metadata sau khi owner duyệt setup.

## Trình tự đề xuất — chưa được phép thực thi

1. Owner tạo và kiểm chứng một instance MySQL riêng, với endpoint loopback 127.0.0.1:3307, UUID và data directory riêng, không chung thư mục dữ liệu Laragon; không import và không có `mediway` hay schema cũ. Cổng và UUID không tự chứng minh tính độc lập. Owner kiểm tra thực tế service, filesystem và cấu hình instance.
2. Owner review một tài liệu chứa schema `f08_acceptance_<16 hex>`, endpoint, UUID, đường dẫn data directory thực tế, danh sách hash của toàn bộ migration và tài khoản metadata chỉ đọc. Hash xác minh nội dung chứ không phải chữ ký; owner phê duyệt riêng từng kế hoạch.
3. `F08V2IndependentInstanceReview.php` là bản review **chỉ đọc**: đối chiếu hash source, kết nối metadata account, `CURRENT_USER`, `CURRENT_ROLE`, UUID, port, data directory, `SHOW DATABASES`, `SHOW GRANTS`. Account này chỉ được USAGE và SHOW DATABASES, không có quyền ghi; không dùng production credentials. Không có lệnh tạo database trong script. Script chưa được kiểm chứng với MySQL; SHOW DATABASES không chứng minh được data directory không có dữ liệu cũ hay không có service khác dùng chung thư mục.
4. Chỉ sau phê duyệt riêng về provisioning mới dùng một công cụ tạo schema và áp migration được review, với kết nối bị ghim vào instance và account được phân quyền theo kế hoạch. **Công cụ ghi đó chưa được hoàn thiện**; dừng nếu migration scope chưa rõ, schema tồn tại, hash thay đổi, danh sách schema khác dự kiến hoặc đặc quyền quá rộng. Tuyệt đối không tự DROP khi thất bại. Không dùng migrate:fresh.
5. Sau provisioning cần kiểm tra metadata và nguồn dữ liệu trước seed, không xem PRE check là thay thế POST check. Worker chỉ được xem xét mở khóa sau khi seed, provenance, quyền, ownership, transaction/retry và preflight thực tế đều đạt.

## Metadata sau provisioning

Trên instance/schema mới và với tài khoản metadata phù hợp, kiểm tra bằng SELECT có `TABLE_SCHEMA`/`CONSTRAINT_SCHEMA`/`ROUTINE_SCHEMA`/`EVENT_SCHEMA` bằng tên schema đã duyệt trong các bảng `information_schema.TABLES`, `COLUMNS`, `KEY_COLUMN_USAGE`, `REFERENTIAL_CONSTRAINTS`, `STATISTICS`, `TRIGGERS`, `ROUTINES`, `EVENTS`. So sánh engine, cột, default, nullability, khóa, FK, unique và các object với migration đã review. Phải xác nhận tài khoản có đủ quyền nhìn thấy tất cả object; danh sách rỗng khi thiếu quyền không là bằng chứng không tồn tại. Không truy vấn dữ liệu bệnh nhân.

## Blocker trước khi chạy ghi

- Chưa có instance độc lập được owner duyệt và kiểm chứng thực tế.
- Chưa hoàn tất kiểm tra phạm vi runtime của toàn bộ migration và kết quả metadata; grep/regex không đủ chứng minh không có hành vi ngoài schema.
- Chưa có script provisioning ghi hoàn chỉnh với owner approval, khóa endpoint/credentials, migrate đúng schema và dừng fail-closed.
- Chưa kiểm chứng MySQL, schema/FK thực tế, registry provenance, phân quyền fixture/worker và retry transaction. Không bật worker hoặc chạy acceptance.
