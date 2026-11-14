# Bàn giao đợt 1: nội dung khám của Huy

## Khảo sát Admin

| Nội dung hiện có | Phần bác sĩ cần | Hướng tích hợp |
| --- | --- | --- |
| Thông tin lượt khám, bệnh nhân, lịch hẹn, bác sĩ và phòng | Xem đúng lượt được phân công | Dùng `medical_visits` và các quan hệ hiện có; kiểm tra quyền trước khi nạp dữ liệu |
| Nội dung khám | Nhập và xem lại theo trạng thái | Dùng `visit_clinical_notes` và quy tắc hiện có, không dùng route Admin làm quyền bác sĩ |
| Chẩn đoán | Tạo hoặc chỉnh sửa khi được phép | Dùng danh mục bệnh và `visit_diagnoses` |
| Chỉ định, trạng thái, kết quả dịch vụ | Tạo chỉ định và xem đúng kết quả | Dùng dữ liệu chung, không tạo trùng khoản thu; thống nhất chuyển trạng thái với Tạo và Mạnh |
| Đơn thuốc | Kê và xem đơn | Dùng `prescriptions`, `prescription_items`, danh mục thuốc và quy tắc khoản thu hiện có |
| Kết luận | Ghi kết luận và kiểm tra hồ sơ | Dùng `visit_conclusions`; Tạo chịu trách nhiệm thao tác hoàn tất |
| Hàng đợi, quản trị và thanh toán | Không mặc nhiên có quyền sửa | Phần của Tạo, Mạnh hoặc chức năng hiện hữu; không sao chép toàn bộ quyền Admin |

## Hợp đồng kiểm tra nội dung

`App\Services\DoctorVisitReadiness::check($visit)` trả về `ready` và `reasons`. Quy tắc hiện tại đối chiếu với `MedicalVisitController` của Admin: cần có chẩn đoán, kết luận và không còn dịch vụ chưa xử lý. Không bắt buộc kê đơn hoặc tự thêm điều kiện chuyên môn mới.

Hàm này chỉ đọc, không thay đổi hồ sơ hay hàng đợi. `ready = true` không đồng nghĩa được phép hoàn tất: bên tích hợp phải kiểm tra bác sĩ được phân công, loại bác sĩ, trạng thái lượt khám và các quyền khác. Cần kiểm tra lại trong giao dịch cùng khóa dữ liệu khi thực sự hoàn tất, không dùng kết quả đã lưu trước đó làm căn cứ duy nhất.

## Điểm cần bàn giao trước khi mở giao diện

- Đã tạo layout Doctor và đăng nhập chung theo yêu cầu. Cần thống nhất với Mạnh việc phân biệt loại bác sĩ: role `Doctor` hiện có chưa đủ để suy ra loại bác sĩ khám bệnh.
- Tạo chuyển ID lượt khám sau khi bắt đầu khám và gọi kiểm tra nội dung chuyên môn khi hoàn tất. Route cụ thể phải thống nhất; không tin ID bệnh nhân hoặc dữ liệu gửi từ trình duyệt để xác định quyền.
- Mạnh và Tạo thống nhất luồng tạo chỉ định, chuyển trạng thái và bác sĩ thực hiện dịch vụ. Admin hiện có thay đổi trạng thái khi tạo chỉ định, nên không được viết thêm luồng cập nhật độc lập.
- Trang của Huy dùng lại các thành phần và stylesheet Employee Portal; cần phối hợp với Mạnh để hợp nhất với nền tảng chung.

Đã thêm route bác sĩ và trang xem lượt khám được phân công. Chưa triển khai các thao tác nhập khám, chẩn đoán, chỉ định hoặc kê đơn; không thay đổi cơ sở dữ liệu. Kiểm thử chỉ dùng SQLite tạm; kết quả và đơn thuốc không yêu cầu bước công bố theo yêu cầu đã chốt.
