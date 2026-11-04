# F04–F05: tiếp đón và hàng đợi hành chính

## Trạng thái

Các route Employee đã được nạp từ `routes/employee-reception.php` trong `routes/web.php`. Nhân viên tiếp đón có thể xem, lọc và phân trang lịch hẹn, xem chi tiết, xác nhận tiếp nhận và theo dõi hàng đợi chỉ đọc. Chức năng dùng layout Employee và `EmployeeMiddleware`/`EmployeeAccess` hiện có. Đã kiểm tra hai kết nối MySQL đồng thời trên database thử nghiệm riêng; nghiệm thu Doctor Portal vẫn phụ thuộc F09 của Huy.

Mục **Tiếp đón** là trang tổng quan và điểm vào khám trực tiếp; mục **Lịch hẹn và tiếp nhận** là danh sách tra cứu lịch. Người khám trực tiếp có thể tìm hồ sơ đang hoạt động bằng mã bệnh nhân, số điện thoại đăng nhập hoặc CCCD, hoặc tạo hồ sơ mới sau khi xác nhận đã tìm trước. Hồ sơ mới tạo tại quầy chưa có tài khoản đăng nhập. Chỉ chọn được ca bác sĩ đang hoạt động, đúng ngày và đúng thời gian; hệ thống kiểm tra lại khi ghi. Chưa tự quy định sức chứa riêng cho khách trực tiếp, do source không có hợp đồng sức chứa walk-in. Không tạo lịch hẹn giả. Với cùng bệnh nhân, bác sĩ và ngày, hệ thống từ chối tạo lượt trực tiếp mới nếu còn lượt chưa hoàn tất; lịch hẹn cũng không được tiếp nhận trùng với một lượt trực tiếp chưa hoàn tất của cùng bác sĩ.

`WalkInReceptionService::receive` khóa quyền tiếp đón, ca, bác sĩ, bệnh nhân và các quan hệ liên quan; tạo `medical_visits` có `reception_source=WALK_IN`, `appointment_id=NULL`, cùng khoản phí khám `UNPAID`. Mỗi biểu mẫu có mã yêu cầu; cùng một nhân viên gửi lại cùng yêu cầu không tạo thêm hồ sơ hoặc lượt khám. Khi không có CCCD, mã này không thể tự nhận diện cùng một người qua hai yêu cầu khác nhau: nhân viên phải tìm và đối chiếu trước khi tạo. Số thứ tự được cấp bởi luồng thanh toán hiện tại sau khi thu phí, như lịch hẹn. `PatientCodeService` dùng chung chuỗi cấp mã cho đăng ký Patient, tạo hồ sơ Admin và tạo hồ sơ tại quầy; cần Mạnh review thay đổi này và Tạo review thay đổi điều kiện cấp queue trong `PaymentTransactionController`.

## Luồng dữ liệu hiện tại

Patient Portal tạo lịch trong `appointments`. `AppointmentReceptionService::checkIn` là nghiệp vụ dùng chung cho Admin và Employee. Employee phải xác nhận thông tin; trong transaction service khóa và kiểm tra lại user, employee và employee_detail theo thứ tự tương thích Admin đổi loại, rồi khóa ca, khung giờ, lịch, bác sĩ, bệnh nhân, chuyên khoa và phòng. Service kiểm tra lại trạng thái `BOOKED`, điều kiện ngày/ca, liên kết và lượt khám trùng; tạo một `medical_visits` trạng thái `WAITING`, khoản phí khám `UNPAID`, đổi lịch sang `CHECKED_IN` và ghi audit. CSDL đã có unique cho `medical_visits.appointment_id`.

Không cấp số hàng đợi ngay khi tiếp nhận. Sau khi thu phí khám thành công, `Admin\PaymentTransactionController::store` cấp queue `INITIAL_EXAM` trạng thái `WAITING` và khóa phòng khi cấp số. Không sao chép hay thay đổi nghiệp vụ thanh toán. Patient Portal đọc trạng thái từ các bảng chung. Chưa có Doctor Portal trong source được khảo sát: Huy cần xác nhận truy vấn và quyền gọi, bắt đầu khám của bác sĩ. F05 không có route hoặc nút gọi, gọi lại, bỏ qua hay bắt đầu khám.

| Thực thể | Trước | Hành động | Sau | Điều kiện |
|---|---|---|---|---|
| Lịch hẹn | BOOKED | Tiếp nhận | CHECKED_IN | Ca và liên kết hợp lệ, chưa có visit |
| Lượt khám | Chưa có | Tiếp nhận | WAITING | Một lượt cho một lịch |
| Phí khám | Chưa có | Tiếp nhận | UNPAID | Có cấu hình phí và trạng thái |
| Hàng đợi | Chưa có | Thu phí khám thành công | WAITING | Luồng thanh toán hiện có xác nhận hợp lệ |
| Hàng đợi | WAITING | Gọi bệnh nhân | CALLED | Thao tác gọi do bác sĩ/luồng chuyên môn thực hiện; F05 chỉ đọc |

## Route thực tế

Các route có prefix `/nhan-vien`, dùng EmployeeMiddleware và AuthenticateSession. Mỗi route nghiệp vụ kiểm tra thêm capability tương ứng.

| Tên | Method, URI | Quyền | Controller |
|---|---|---|---|
| employee.reception.index | GET /nhan-vien/tiep-don | appointment.view | ReceptionAppointmentController@index |
| employee.appointments.index | GET /nhan-vien/lich-hen | appointment.view | ReceptionAppointmentController@index |
| employee.appointments.filter | POST /nhan-vien/lich-hen/loc | appointment.view | ReceptionAppointmentController@filter |
| employee.appointments.show | GET /nhan-vien/lich-hen/{appointment} | appointment.view | ReceptionAppointmentController@show |
| employee.appointments.check-in | POST /nhan-vien/lich-hen/{appointment}/tiep-nhan | reception.check_in | ReceptionAppointmentController@checkIn |
| employee.queue.index | GET /nhan-vien/hang-doi | queue.view | ReceptionQueueController@index |
| employee.queue.filter | POST /nhan-vien/hang-doi/loc | queue.view | ReceptionQueueController@filter |
| employee.walk-in.create | GET /nhan-vien/tiep-don/truc-tiep | reception.check_in | WalkInReceptionController@create |
| employee.walk-in.search | POST /nhan-vien/tiep-don/truc-tiep/tim-benh-nhan | reception.check_in | WalkInReceptionController@search |
| employee.walk-in.store | POST /nhan-vien/tiep-don/truc-tiep | reception.check_in | WalkInReceptionController@store |
| employee.walk-in.show | GET /nhan-vien/tiep-don/truc-tiep/{visit} | reception.check_in | WalkInReceptionController@show |

Từ khóa được gửi bằng POST và giữ trong session thay vì đưa tên bệnh nhân vào URL phân trang. Bộ lọc mặc định là ngày hiện tại; nút làm mới truy vấn lại dữ liệu. Quyền hiện tại không có phân vùng cơ sở hay phân công tiếp đón theo phòng, vì vậy danh sách bao gồm các lịch/hàng đợi mà quyền `RECEPTIONIST` hiện có cho phép xem; cần thống nhất nếu nhóm bổ sung phân vùng.

## Dữ liệu đọc và ghi

| Bảng/model | Đọc/ghi | Mục đích |
|---|---|---|
| appointments, appointment_statuses, appointment_time_slots, doctor_schedules | Đọc; ghi trạng thái appointments khi tiếp nhận | Lịch và điều kiện tiếp nhận |
| patients, doctors, specialties, examination_rooms | Đọc, khóa trong transaction | Thông tin hành chính và xác minh |
| medical_visits | Đọc/ghi | Một lượt khám cho lịch tiếp nhận |
| examination_room_queues, queue_statuses | Đọc trong F05 | Theo dõi hàng đợi; không ghi trạng thái |
| payables | Tạo phí khám trong service tiếp nhận hiện có | Chờ thu ngân |
| audit_logs | Ghi | Danh tính tài khoản tiếp nhận và thay đổi |
| session | Ghi bộ lọc | Không đưa từ khóa vào URL |

## Kiểm thử

| Tình huống | Kết quả | Trạng thái |
|---|---|---|
| Danh sách, lọc, chi tiết, hàng đợi và phân quyền | Kiểm thử feature SQLite | ĐẠT |
| Tiếp nhận thành công, xác nhận, lặp, lịch hủy, đổi loại, rollback | Kiểm thử feature SQLite | ĐẠT |
| Hồi quy Employee, Admin, Patient và thanh toán sau tiếp nhận | 95 kiểm thử feature SQLite, 1.132 assertion; chạy lại riêng 9 kiểm thử khám trực tiếp sau bổ sung tra cứu | ĐẠT |
| Hai nhân viên đồng thời trên MySQL | MySQL 8.0.30, hai kết nối độc lập, cố ý giữ và tranh khóa cùng lịch; một thành công, một bị từ chối, một visit và một khoản phí | ĐẠT |
| Hai nhân viên tiếp nhận trực tiếp cùng bệnh nhân | MySQL 8.0.30, hai kết nối độc lập: một thành công, một bị từ chối; một visit, một khoản phí | ĐẠT |
| Liên thông Doctor Portal | Chưa có Doctor Portal trong source | BỊ CHẶN DO PHỤ THUỘC |
| Giao diện 320, 375, 390, 768, 1024 và 1440px | Chrome headless: các trang không tràn ngang; kiểm tra ảnh mobile và desktop, dữ liệu chỉ là seed trong SQLite riêng | ĐẠT kiểm tra kích thước |

## Nghiệm thu thủ công

Đăng nhập tài khoản tiếp đón `NV000001` bằng trang đăng nhập chung. Vào Tiếp đón, chọn ngày và tìm mã lịch; mở chi tiết lịch `BOOKED` đúng thời gian, xác nhận thông tin và nhấn Xác nhận tiếp nhận. Kiểm tra lịch `CHECKED_IN`, lượt `WAITING`, phí khám `UNPAID` và chưa có số thứ tự. Sau khi thu ngân ghi nhận thu phí, mở Hàng đợi hành chính và làm mới để đối chiếu. Thử lịch hủy, gửi lại, tài khoản loại khác và đổi loại trong phiên; kiểm tra Patient và Doctor sau khi Doctor Portal sẵn sàng. Nghiệm thu tại 320, 375, 390, 768, 1024 và 1440px.

Không chạy `migrate:fresh` trên cơ sở dữ liệu làm việc. Kiểm thử đồng thời dùng database mới `mediway_f04_test_20261104_01`, không dùng database `mediway`; script `tests/Integration/ReceptionMysqlConcurrency.php` từ chối kết nối ngoài database kiểm thử đã chỉ định và không tự chạy migration hoặc xóa dữ liệu. Database thử nghiệm được giữ lại. Mạnh cần review thay đổi `routes/web.php`, `resources/js/employee.js` và việc Admin chuyển sang service chung; Huy xác nhận hợp đồng đọc và gọi queue; Tạo kiểm thử tiếp nhận → phí → queue.
