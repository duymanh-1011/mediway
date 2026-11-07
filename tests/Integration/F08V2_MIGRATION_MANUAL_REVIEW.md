# F08 V2 — review source migration, chưa phải manifest phê duyệt

## Bốn migration được yêu cầu

| File | up() | down()/helper | Phạm vi cần xác minh |
|---|---|---|---|
| `2026_10_28_110000_add_patient_publication_state_to_clinical_records.php:12` | Vòng lặp cố định `service_results`, `prescriptions`: thêm `patient_publication_status` varchar(16), default PRIVATE, index. | Dòng 21: cùng hai bảng, bỏ index/cột. | Không thấy kết nối thứ hai; SQL thực tế do Schema builder sinh, chưa chạy MySQL. |
| `2026_10_19_202127_strengthen_clinical_data_constraints.php:41` | Đọc và kiểm tra danh sách bảng hằng; đổi default status; thêm unique; trên MySQL đọc `information_schema.table_constraints` theo `DB::getDatabaseName()` và tại dòng 71 sinh ALTER CHECK từ hằng. | Dòng 83 DROP CHECK; xóa unique; helper dòng 98–106 đổi default, SQLite tạm tắt FK. | Tên bảng/biểu thức lấy từ hằng trong file; cần xem SQL MySQL thực sinh ra và quyền metadata. Không chạy `down()`. |
| `2026_10_24_090000_add_employee_type_catalog.php:21` | Đọc `employee_details` hiện có, từ chối loại không biết; tạo catalog, insert 5 giá trị, chuẩn hóa dữ liệu và thêm FK `employee_details.employee_type → employee_types.code` dòng 42. | Dòng 48–49 bỏ FK/bảng. | DDL và update có tác dụng trong schema kết nối; không chứng minh nội dung schema mới cho tới khi inspect. |
| `2026_10_23_120000_add_specialty_to_doctors.php:12` | Thêm `specialties.is_general`, FK nullable `doctors.specialty_id → specialties.id`; insert hoặc update reference CKDK dòng 20–27. | Dòng 33–34 bỏ FK/cột. | Một specialty reference được migration tạo; store/preflight V2 phải đối chiếu chính xác. |

## FK fixture phí khám xác minh từ source

- `users.role_id → roles.id` (`0001_01_01_112624_create_users_table.php:21`); `patients.user_id → users.id` nullable/unique (`2026_10_09_112840_create_patients_table.php:14`); `doctors.user_id` và `employees.user_id → users.id` unique (`2026_10_09_112907_create_doctors_table.php:14`, `2026_10_09_112919_create_employees_table.php:14`).
- `employee_details.employee_id → employees.id` unique (`2026_10_09_112925_create_employee_details_table.php:14`); employee type FK ở trên.
- `doctor_schedules` tham chiếu doctor, specialty, room (`2026_10_09_120000_create_doctor_schedules_table.php:14`); `appointment_time_slots.doctor_schedule_id → doctor_schedules.id` (`2026_10_09_120001_create_appointment_time_slots_table.php:14`).
- `appointments` tham chiếu patient, doctor, specialty, room, slot, appointment status (`2026_10_09_120002_create_appointments_table.php:16`).
- `medical_visits` tham chiếu appointment nullable/unique, patient, doctor, specialty, room, visit status (`2026_10_09_130002_create_medical_visits_table.php:17`).
- `payables` tham chiếu visit, payment status và service item nullable/unique (`2026_10_09_140007_create_payables_table.php:16`); `prescription_id` nullable do `2026_11_02_220000_add_prescription_pricing.php` bổ sung.
- `payment_transactions` tham chiếu payable và collector nullable (`2026_10_09_140008_create_payment_transactions_table.php:16`); `audit_logs.user_id` nullable (`2026_10_09_140009_create_audit_logs_table.php:14`).

Các FK khác trong toàn bộ 53 migration, tên index MySQL thực tế và trạng thái sau ALTER: **UNKNOWN** cho tới khi rà soát đủ và đối chiếu metadata schema mới. Không nhập các dòng ở đây làm danh sách FK đầy đủ cho approval.

## Điểm nguồn ghi dữ liệu / dynamic

- `2026_10_19_155225_create_service_group_medical_service_table.php:54` và `2026_10_19_202126_finalize_service_group_relationship.php:16`: đọc medical_services và ghi pivot trong schema kết nối; `down()` có nhánh khôi phục dữ liệu. Đây không phải sao chép từ schema khác trong source đã đọc, nhưng không nên chạy khi schema có dữ liệu không rõ nguồn gốc.
- `2026_10_23_160000_normalize_legacy_schedule_statuses.php:11`: update doctor_schedules và appointment_time_slots trong schema kết nối.
- Chưa thấy `DB::connection` hoặc `Schema::connection` trong tìm kiếm source migration; điều này không chứng minh runtime connection/config hoặc SQL động an toàn. Danh sách còn lại trong `F08V2_migration_source_report.json` cần review từng dòng.

Không có bằng chứng MySQL, không phê duyệt DDL, và không cho phép chạy setup từ tài liệu này.

## Scope scan: review of all 62 matches

The 62 matches cover 61 distinct source locations: line 83 of strengthen_clinical_data_constraints matches both raw_sql and drop. SAFE_FROM_SOURCE refers only to the static scope described here, not operational safety of rollback or evidence of a MySQL run. All 50 distinct DROP locations occur in down(), which normal migrate does not execute. Prohibit rollback, migrate:fresh and automatic DROP. All DDL must be confined to an approved clean isolated schema.

| File:line | Match | Method | Runs on migrate up? | Schema scope | Writes? | Classification | Risk / condition |
|---|---|---|---|---|---|---|---|
| `0001_01_01_000001_create_cache_table.php:32` | drop | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `0001_01_01_000001_create_cache_table.php:33` | drop | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `0001_01_01_000002_create_jobs_table.php:55` | drop | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `0001_01_01_000002_create_jobs_table.php:56` | drop | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `0001_01_01_000002_create_jobs_table.php:57` | drop | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `0001_01_01_112623_create_roles_table.php:25` | drop | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `0001_01_01_112624_create_users_table.php:51` | drop | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `0001_01_01_112624_create_users_table.php:52` | drop | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `0001_01_01_112624_create_users_table.php:53` | drop | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `2026_10_09_095547_create_specialties_table.php:31` | drop | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `2026_10_09_095604_create_examination_rooms_table.php:33` | drop | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `2026_10_09_095619_create_service_groups_table.php:33` | drop | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `2026_10_09_095628_create_medical_services_table.php:41` | drop | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `2026_10_09_095639_create_disease_groups_table.php:32` | drop | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `2026_10_09_095651_create_diseases_table.php:38` | drop | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `2026_10_09_095700_create_medicines_table.php:44` | drop | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `2026_10_09_095709_create_appointment_statuses_table.php:32` | drop | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `2026_10_09_112634_create_administrators_table.php:33` | drop | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `2026_10_09_112840_create_patients_table.php:32` | drop | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `2026_10_09_112841_create_patient_details_table.php:48` | drop | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `2026_10_09_112859_create_personal_health_records_table.php:44` | drop | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `2026_10_09_112907_create_doctors_table.php:31` | drop | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `2026_10_09_112913_create_doctor_details_table.php:68` | drop | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `2026_10_09_112919_create_employees_table.php:31` | drop | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `2026_10_09_112925_create_employee_details_table.php:60` | drop | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `2026_10_09_120000_create_doctor_schedules_table.php:36` | drop | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `2026_10_09_120001_create_appointment_time_slots_table.php:31` | drop | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `2026_10_09_120002_create_appointments_table.php:51` | drop | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `2026_10_09_130000_create_visit_statuses_table.php:28` | drop | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `2026_10_09_130001_create_queue_statuses_table.php:28` | drop | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `2026_10_09_130002_create_medical_visits_table.php:57` | drop | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `2026_10_09_130003_create_examination_room_queues_table.php:40` | drop | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `2026_10_09_130004_create_visit_clinical_notes_table.php:36` | drop | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `2026_10_09_130005_create_visit_diagnoses_table.php:37` | drop | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `2026_10_09_130006_create_visit_conclusions_table.php:44` | drop | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `2026_10_09_140000_create_service_statuses_table.php:34` | drop | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `2026_10_09_140001_create_payment_statuses_table.php:33` | drop | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `2026_10_09_140002_create_service_orders_table.php:37` | drop | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `2026_10_09_140003_create_service_order_items_table.php:40` | drop | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `2026_10_09_140004_create_service_results_table.php:40` | drop | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `2026_10_09_140005_create_prescriptions_table.php:38` | drop | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `2026_10_09_140006_create_prescription_items_table.php:41` | drop | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `2026_10_09_140007_create_payables_table.php:49` | drop | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `2026_10_09_140008_create_payment_transactions_table.php:49` | drop | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `2026_10_09_140009_create_audit_logs_table.php:45` | drop | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `2026_10_19_155225_create_service_group_medical_service_table.php:79` | drop | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `2026_10_19_202127_strengthen_clinical_data_constraints.php:41` | dynamic_table_or_metadata | up | Yes | Current DB; constant table names | No | SAFE_FROM_SOURCE | Stops on invalid data; require clean schema |
| `2026_10_19_202127_strengthen_clinical_data_constraints.php:50` | dynamic_table_or_metadata | up | Yes | Current DB; constant table names | No | SAFE_FROM_SOURCE | Stops on invalid data; require clean schema |
| `2026_10_19_202127_strengthen_clinical_data_constraints.php:61` | dynamic_table_or_metadata | up | Yes | Current DB; constant table names | DDL | REQUIRES_ISOLATED_RUNTIME_TEST | Verify ALTER/constraint on isolated MySQL |
| `2026_10_19_202127_strengthen_clinical_data_constraints.php:68` | dynamic_table_or_metadata | up | Yes on MySQL | information_schema, filtered by current DB | No | SAFE_FROM_SOURCE | Verify metadata visibility |
| `2026_10_19_202127_strengthen_clinical_data_constraints.php:71` | raw_sql | up | Yes | Current DB; constant table names | DDL | REQUIRES_ISOLATED_RUNTIME_TEST | Verify ALTER/constraint on isolated MySQL |
| `2026_10_19_202127_strengthen_clinical_data_constraints.php:83` | raw_sql | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `2026_10_19_202127_strengthen_clinical_data_constraints.php:83` | drop | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `2026_10_19_202127_strengthen_clinical_data_constraints.php:89` | dynamic_table_or_metadata | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `2026_10_19_202127_strengthen_clinical_data_constraints.php:91` | dynamic_table_or_metadata | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `2026_10_19_202127_strengthen_clinical_data_constraints.php:100` | dynamic_table_or_metadata | changeStatusDefault | Yes, via up | Current DB | DDL | REQUIRES_ISOLATED_RUNTIME_TEST | Verify ALTER default on isolated MySQL |
| `2026_10_19_231253_create_business_code_sequences_table.php:27` | drop | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `2026_10_24_090000_add_employee_type_catalog.php:49` | drop | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `2026_10_27_090000_create_patient_password_reset_challenges_table.php:24` | drop | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `2026_10_28_100000_create_patient_link_requests_table.php:26` | drop | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
| `2026_10_28_110000_add_patient_publication_state_to_clinical_records.php:12` | dynamic_table_or_metadata | up | Yes | Current DB; constant table names | DDL | REQUIRES_ISOLATED_RUNTIME_TEST | Verify ALTER/constraint on isolated MySQL |
| `2026_10_28_110000_add_patient_publication_state_to_clinical_records.php:21` | dynamic_table_or_metadata | down | No | Current DB | DDL | SAFE_FROM_SOURCE | Destructive rollback; never run down on data to preserve |
