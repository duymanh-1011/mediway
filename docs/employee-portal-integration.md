# Tích hợp Employee Portal

Employee Portal dùng `resources/views/employee/layout.blade.php`; trang mới kế thừa layout, truyền `$employee` và khai báo `@section('title')`, `@section('content')`. Layout dùng `portal.css`, `portal-consistency.css`, `employee.css`: font Segoe UI/Arial, cỡ chữ 14px, tiêu đề 26px, xanh chính #2563eb, xanh đậm #1d4ed8, nền #f4f7fb, card trắng viền #dbe5ee. Nút dùng `portal-btn portal-btn--primary` hoặc `portal-btn portal-btn--secondary`; form dùng `portal-form`, thông báo dùng `portal-alert`, card dùng `employee-card`. Kiểm tra 320, 375, 390, 768, 1024 và 1440px khi bổ sung trang.

Với danh sách, dùng lớp `employee-table` cho bảng và `employee-table-wrap` cho phần bọc cuộn ngang; bộ lọc dùng `employee-filter` và từng ô dùng `employee-filter__field`. Trạng thái rỗng, đang tải và hộp xác nhận có thể tái dùng `portal-empty`, `portal-loading`, `portal-modal` sẵn trong CSS chung; owner chịu trách nhiệm nội dung, focus và hành vi thực tế của từng chức năng.

Các route nền tảng là `employee.home`, `employee.profile`, `employee.profile.edit`, `employee.profile.update`, `employee.password.update`, `employee.logout`; chúng dùng `EmployeeMiddleware` và `AuthenticateSession`. Vai trò, trạng thái và loại nhân viên được đọc lại ở mỗi request; không lưu quyền vào session. Khi đổi mật khẩu, phiên hiện tại tiếp tục hoạt động và các phiên nhân viên khác bị từ chối ở request tiếp theo.

Trang đăng nhập chung đã hỗ trợ Patient, Employee và Admin. Khi Doctor Portal được owner tích hợp, đăng ký route có tên `doctor.home` cùng middleware kiểm tra quyền Doctor của portal đó. Chỉ khi route tồn tại và tài khoản, role, hồ sơ bác sĩ đang hoạt động, đăng nhập chung mới chuyển đến route này. Hiện chưa có Doctor Portal nên bác sĩ chưa thể hoàn tất đăng nhập; route mẫu trong kiểm thử không phải route triển khai.

| Nhóm | Capability dự kiến | Owner | Trạng thái |
|---|---|---|---|
| Tiếp đón | `appointment.view`, `reception.check_in`, `queue.view` | Linh | Chưa tích hợp route |
| Xét nghiệm | `laboratory.view`, `laboratory.process` | Huy | Chưa tích hợp route |
| Chẩn đoán hình ảnh | `imaging.view`, `imaging.process` | Huy | Chưa tích hợp route |
| Thu ngân | `payment.view`, `payment.collect` | Tạo | Chưa tích hợp route |

Bổ sung route nghiệp vụ bên trong nhóm `employee` hiện có để kế thừa cả hai middleware, rồi thêm `EmployeeMiddleware::class.':laboratory.process'` hoặc capability tương ứng cho route thao tác. Nếu tạo nhóm riêng, phải áp dụng cả `AuthenticateSession::class` lẫn `EmployeeMiddleware`; middleware capability không tự thay thế bảo vệ phiên. Ví dụ:

```php
Route::post('/xet-nghiem/{item}', [LaboratoryController::class, 'update'])
    ->middleware(\App\Http\Middleware\EmployeeMiddleware::class.':laboratory.process')
    ->name('laboratory.update');
```

Đây là ví dụ tích hợp, không phải route đã tồn tại. Trong controller/service, gọi `app(EmployeeAccess::class)->allows($request->user(), 'laboratory.process')` và từ chối khi sai quyền. Owner phải kiểm tra thêm quyền trên từng bản ghi, trạng thái nghiệp vụ và kiểm tra lại khi ghi trong transaction; capability không thay thế các kiểm tra đó. Không gắn các route Admin trực tiếp vào Employee Portal. Employee không có `queue.call`; Admin hiện vẫn có route gọi hàng đợi và cần phối hợp với owner Doctor về quy tắc mới.

Menu chỉ được kết nối sau khi route thật, quyền, phạm vi dữ liệu và owner đã được xác nhận. Các module hiện chưa có route được hiển thị ở trạng thái chưa tích hợp hoặc không có quyền. Các file `routes/web.php`, `EmployeeAccess`, `EmployeeMiddleware`, layout và CSS là thành phần dùng chung; phối hợp với Mạnh trước khi sửa. Linh, Huy, Tạo chịu trách nhiệm trọn vẹn route, giao diện, form, validation, dữ liệu và test nghiệp vụ tương ứng.

Tên route cho menu được đề xuất để các owner dùng nhất quán: Linh `employee.reception.index`, `employee.appointments.index`, `employee.queue.index`; Huy `employee.laboratory.index`, `employee.imaging.index`; Tạo `employee.payments.index`. Đây là các route GET danh sách/tổng quan không có tham số bắt buộc. Menu chỉ tạo liên kết nếu route tương ứng đã đăng ký và loại hiện tại phù hợp; việc xuất hiện trong menu không thay thế middleware kiểm tra quyền và phạm vi của route. Các route này hiện chưa tồn tại.

Quyền điều dưỡng cũ không được cấp quyền Employee Portal; dữ liệu lịch sử vẫn được giữ. Chuyển loại hiện kiểm tra dịch vụ chưa hoàn tất và giao dịch đang chờ gắn trực tiếp với nhân viên; các phân công khác và khóa đồng thời giữa các module cần owner cung cấp hợp đồng trước khi xác nhận an toàn đầy đủ.

## Bản đồ mã hiện có và điểm bàn giao

Các route Admin dưới đây là code hiện có để khảo sát, **không phải** quyền đã cấp cho nhân viên. Tên route Employee của các nghiệp vụ còn chờ owner đề xuất và cả nhóm thống nhất.

| Nghiệp vụ / owner | Route và màn hình hiện có | Controller hiện có | Dữ liệu chính |
|---|---|---|---|
| Đăng nhập, phân quyền / Mạnh | `patient.login`, `patient.login.store`, `employee.home`, `employee.profile` | `Portal\\AuthController`, `Employee\\WorkspaceController`, `EmployeeMiddleware` | `users`, `roles`, `employees`, `employee_details` |
| Đổi loại / Mạnh | `admin.employees.edit`, `admin.employees.update`; `admin/employees/edit` | `Admin\\EmployeeController` | `employees`, `employee_details`, `service_order_items`, `payment_transactions`, `audit_logs` |
| Tiếp nhận / Linh | `admin.appointments.check-in`; màn hình Admin lịch hẹn | `Admin\\AppointmentController::checkIn` | `appointments`, `medical_visits`, `payables` |
| Hàng đợi hành chính / Linh; gọi khám / Huy | `admin.queues.call` và các route Admin hàng đợi | `Admin\\ExaminationRoomQueueController` | `examination_room_queues`, `queue_statuses`, `medical_visits` |
| Xét nghiệm, hình ảnh / Huy | `admin.service-order-items.start`, `admin.service-order-items.complete`, `admin.service-order-items.service-results.store` | `Admin\\ServiceOrderItemController`, `Admin\\ServiceResultController` | `service_order_items`, `service_statuses`, `service_results` |
| Thu ngân / Tạo | `admin.payables.payment-transactions.create`, `admin.payables.payment-transactions.store` | `Admin\\PaymentTransactionController` | `payables`, `payment_transactions`, `examination_room_queues` |

Trạng thái đang có trong seeder: lịch hẹn `BOOKED`, `CHECKED_IN`, `IN_EXAMINATION`, `COMPLETED`, `CANCELLED`; lượt khám `WAITING`, `IN_EXAMINATION`, `WAITING_FOR_SERVICE`, `RETURNING`, `COMPLETED`; dịch vụ `PENDING`, `PROCESSING`, `COMPLETED`, `CANCELLED`; hàng đợi `WAITING`, `CALLED`, `IN_SERVICE`, `COMPLETED`, `SKIPPED`. Owner phải kiểm tra điều kiện chuyển trong code thực tế, không chỉ dựa vào thứ tự danh mục.

Theo quyết định đã chốt với chủ dự án, check-in tạo khoản phí khám; chỉ sau khi ghi nhận thanh toán hợp lệ mới đưa bệnh nhân vào hàng đợi. Bệnh nhân được xem kết quả ngay khi có theo quy tắc đang áp dụng. Hai quy tắc này được giữ nguyên khi tích hợp.

Khi đổi loại, Admin phải nhập lý do; nhật ký ghi người thao tác, loại cũ, loại mới, thời gian và lý do trong cùng transaction. Theo quyết định hiện tại không cần gắn nhân viên tiếp đón vào lịch hẹn/lượt khám. Các owner phải dùng cùng thứ tự khóa với cập nhật loại trước khi triển khai ghi dữ liệu đồng thời. Bản đồ này là đề xuất kỹ thuật theo source hiện tại, chưa thay thế việc bốn thành viên xác nhận hợp đồng.
