<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use RuntimeException;


class DefenseDatasetSeeder extends Seeder
{
    private const INITIAL_PASSWORD = 'Mediway@123';

    private int $inserted = 0;

    public function run(): void
    {
        $summary = DB::transaction(function (): array {
            $before = $this->counts();
            $this->inserted = 0;
            $this->normalizeCurrentDataset();

            $roles = DB::table('roles')->pluck('id', 'name')->all();
            foreach (['Patient', 'Doctor', 'Employee'] as $role) {
                if (! isset($roles[$role])) {
                    throw new RuntimeException("Thiếu role {$role}; seeder dừng và không ghi dữ liệu.");
                }
            }

            $specialties = $this->seedSpecialties();
            $this->seedRooms($specialties);
            $this->seedDoctors($specialties, (int) $roles['Doctor']);
            $this->seedEmployees((int) $roles['Employee']);
            $this->seedPatients((int) $roles['Patient']);
            $this->seedServices();
            $this->seedDiseaseGroupsAndDiseases();
            $this->seedMedicines();

            return ['before' => $before, 'after' => $this->counts()];
        });

        // The larger local demo catalog is created above, so price it after insertion.
        $this->call(ReferenceMedicinePriceSeeder::class);

        if ($this->command) {
            $this->command->info('Đã đồng bộ bộ dữ liệu phục vụ bảo vệ; có thể chạy lại an toàn.');
            $this->command->line('Số bản ghi mới: '.$this->inserted);
            foreach ($summary['after'] as $table => $count) {
                $this->command->line("{$table}: {$summary['before'][$table]} → {$count}");
            }
            $this->command->line('Mật khẩu chung cho các tài khoản được tạo mới: '.self::INITIAL_PASSWORD);
            $this->command->warn('Không tạo thêm lượt khám, lịch hẹn, hàng đợi, chỉ định, đơn thuốc hoặc hóa đơn.');
        }
    }

    private function counts(): array
    {
        $tables = [
            'specialties', 'examination_rooms', 'doctors', 'doctor_details',
            'employees', 'employee_details', 'patients', 'patient_details',
            'medical_services', 'service_result_templates', 'medical_service_result_templates',
            'disease_groups', 'diseases', 'medicines',
        ];

        return collect($tables)->mapWithKeys(fn (string $table): array => [$table => DB::table($table)->count()])->all();
    }

    /** Normalize records created by this seeder without touching operational history. */
    private function normalizeCurrentDataset(): void
    {
        $templateNames = [
            'Xét nghiệm sinh hóa' => 'Phiếu kết quả xét nghiệm sinh hóa',
            'Tổng phân tích nước tiểu' => 'Phiếu kết quả tổng phân tích nước tiểu',
            'Vi sinh và miễn dịch' => 'Phiếu kết quả vi sinh và miễn dịch',
            'Chẩn đoán hình ảnh X-quang' => 'Phiếu kết quả chẩn đoán hình ảnh X-quang',
            'Chẩn đoán hình ảnh CT và MRI' => 'Phiếu kết quả chẩn đoán hình ảnh CT và MRI',
            'Siêu âm' => 'Phiếu kết quả siêu âm',
            'Nội soi' => 'Phiếu kết quả nội soi',
            'Điện tâm đồ' => 'Phiếu kết quả điện tâm đồ',
            'Thăm dò chức năng' => 'Phiếu kết quả thăm dò chức năng',
            'Thủ thuật' => 'Phiếu kết quả thủ thuật',
            'Phục hồi chức năng' => 'Phiếu kết quả phục hồi chức năng',
            'Chăm sóc ngoại trú' => 'Phiếu kết quả chăm sóc ngoại trú',
        ];
        foreach (DB::table('service_result_templates')->get(['id', 'name']) as $template) {
            $separator = strrpos($template->name, ' - ');
            if ($separator === false) {
                continue;
            }
            $category = substr($template->name, $separator + 3);
            $newName = $templateNames[$category] ?? null;
            if ($newName === null || $template->name === $newName) {
                continue;
            }
            $duplicate = DB::table('service_result_templates')->where('name', $newName)->where('id', '<>', $template->id)->first();
            if ($duplicate) {
                throw new RuntimeException("Tên mẫu kết quả '{$newName}' bị trùng với phiên bản đang được sử dụng; không gộp hoặc xóa mẫu.");
            }
            DB::table('service_result_templates')->where('id', $template->id)->update([
                'name' => $newName,
                'updated_at' => now(),
            ]);
        }

        $newRoomCodes = DB::table('examination_rooms')->where('room_code', 'like', 'PK-%')->get(['id', 'room_number']);
        foreach ($newRoomCodes as $room) {
            $code = 'PK'.$room->room_number;
            if (DB::table('examination_rooms')->where('room_code', $code)->where('id', '<>', $room->id)->exists()) {
                throw new RuntimeException("Mã phòng {$code} đã được sử dụng; không đổi mã tự động.");
            }
            DB::table('examination_rooms')->where('id', $room->id)->update([
                'room_code' => $code,
                'name' => 'Phòng khám '.$room->room_number,
                'description' => 'Phòng khám ngoại trú chuyên khoa '.$this->specialtyNameForRoom($room->id).'.',
                'notes' => null,
                'updated_at' => now(),
            ]);
        }

        $doctorRows = DB::table('doctors')->join('specialties', 'specialties.id', '=', 'doctors.specialty_id')
            ->join('doctor_details', 'doctor_details.doctor_id', '=', 'doctors.id')
            ->where('doctors.doctor_code', 'like', 'BS9%')
            ->where('doctor_details.email', 'like', '%@%.mediway.test')
            ->get(['doctors.id', 'doctors.doctor_code', 'doctors.full_name', 'doctors.specialty_id', 'specialties.name as specialty_name']);
        foreach ($doctorRows as $doctor) {
            $sequence = (int) substr($doctor->doctor_code, 3);
            DB::table('doctor_details')->where('doctor_id', $doctor->id)->update([
                'citizen_id_number' => $this->citizenId('031', $sequence),
                'citizen_id_issue_place' => 'Công an thành phố Hà Nội',
                'email' => strtolower($doctor->doctor_code).'@mediway.test',
                'hometown' => $this->hometownProvince($sequence),
                'permanent_address' => $this->personAddress($sequence),
                'current_address' => $this->personAddress($sequence),
                'ethnicity' => 'Kinh',
                'education_level' => 'Đại học',
                'professional_qualification' => 'Bác sĩ chuyên khoa',
                'academic_title_degree' => $sequence % 3 === 0 ? 'Bác sĩ chuyên khoa I' : 'Bác sĩ đa khoa',
                'professional_title' => 'Bác sĩ',
                'current_position' => $sequence % 3 === 0 ? 'Bác sĩ thực hiện dịch vụ' : 'Bác sĩ khám bệnh',
                'current_workplace' => 'Bệnh viện Mediway',
                'specialized_fields' => $doctor->specialty_name,
                'professional_skills' => 'Khám lâm sàng, tư vấn và theo dõi người bệnh trong phạm vi chuyên khoa.',
                'practice_license_number' => 'GPHN-MW-'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
                'practice_scope' => 'Khám bệnh, chữa bệnh chuyên khoa '.$doctor->specialty_name.'.',
                'professional_bio' => 'Bác sĩ phụ trách chuyên môn '.$doctor->specialty_name.' tại Bệnh viện Mediway.',
                'notes' => null,
                'updated_at' => now(),
            ]);
        }

        $employeeRows = DB::table('employees')->join('employee_details', 'employee_details.employee_id', '=', 'employees.id')
            ->where('employees.employee_code', 'like', 'NV9%')
            ->where('employee_details.email', 'like', '%@%.mediway.test')
            ->get(['employees.id', 'employees.employee_code']);
        foreach ($employeeRows as $employee) {
            $sequence = (int) substr($employee->employee_code, 3);
            DB::table('employee_details')->where('employee_id', $employee->id)->update([
                'citizen_id_number' => $this->citizenId('048', $sequence),
                'citizen_id_issue_place' => 'Công an thành phố Hà Nội',
                'email' => strtolower($employee->employee_code).'@mediway.test',
                'hometown' => $this->hometownProvince($sequence + 400),
                'permanent_address' => $this->personAddress($sequence + 400),
                'current_address' => $this->personAddress($sequence + 400),
                'ethnicity' => 'Kinh',
                'education_level' => 'Đại học',
                'professional_qualification' => 'Nghiệp vụ quản lý y tế',
                'position' => 'Nhân viên',
                'work_location' => 'Bệnh viện Mediway',
                'job_description' => 'Hỗ trợ tiếp đón người bệnh, tra cứu lịch hẹn và ghi nhận thanh toán theo phân công trong ca.',
                'professional_skills' => 'Tiếp nhận hồ sơ, hướng dẫn quy trình, thu ngân và đối soát các khoản thu.',
                'notes' => null,
                'updated_at' => now(),
            ]);
        }

        $patientRows = DB::table('patients')->join('patient_details', 'patient_details.patient_id', '=', 'patients.id')
            ->where('patients.patient_code', 'like', 'BN9%')
            ->where('patient_details.email', 'like', '%@%.mediway.test')
            ->get(['patients.id', 'patients.patient_code']);
        foreach ($patientRows as $patient) {
            $sequence = (int) substr($patient->patient_code, 3);
            DB::table('patient_details')->where('patient_id', $patient->id)->update([
                'citizen_id_number' => $this->citizenId('001', $sequence),
                'citizen_id_issue_place' => 'Công an thành phố Hà Nội',
                'email' => strtolower($patient->patient_code).'@mediway.test',
                'hometown' => $this->hometownProvince($sequence + 800),
                'permanent_address' => $this->personAddress($sequence + 800),
                'current_address' => $this->personAddress($sequence + 800),
                'ethnicity' => 'Kinh',
                'occupation' => $this->occupations()[$sequence % count($this->occupations())],
                'emergency_contact_relationship' => 'Người thân',
                'notes' => null,
                'updated_at' => now(),
            ]);
        }

        DB::table('specialties')->whereIn('specialty_code', array_column($this->specialtyCatalog(), 0))->update(['notes' => null]);
        DB::table('service_result_templates')->whereIn('name', array_column($this->resultTemplates(), 'name'))->update(['updated_at' => now()]);
        DB::table('medical_services')->where('service_code', 'like', 'DV9%')
            ->where('description', 'like', '%Dữ liệu dịch vụ và giá chỉ dùng cho %')
            ->get(['id', 'description'])->each(function ($service): void {
            $description = preg_replace('/\s+Dữ liệu dịch vụ và giá chỉ dùng cho .+$/u', '', (string) $service->description);
            DB::table('medical_services')->where('id', $service->id)->update(['description' => $description, 'updated_at' => now()]);
        });
        DB::table('diseases')->whereIn('disease_code', array_column($this->diseaseCatalog(), 0))
            ->where('description', 'like', 'Bản ghi danh mục tham khảo%')->update([
            'description' => 'Phân loại bệnh theo mã ICD-10.',
            'updated_at' => now(),
        ]);
        DB::table('medicines')->where('medicine_code', 'like', 'TH9%')
            ->where('description', 'like', 'Bản ghi hoạt chất/dạng bào chế%')->update([
            'description' => 'Thuốc và dạng bào chế được quản lý trong danh mục dược của cơ sở.',
            'warnings' => 'Sử dụng theo chỉ định của người hành nghề và hướng dẫn sử dụng đã được phê duyệt.',
            'notes' => null,
            'updated_at' => now(),
        ]);
    }

    private function specialtyNameForRoom(int $roomId): string
    {
        return (string) DB::table('examination_rooms')->join('specialties', 'specialties.id', '=', 'examination_rooms.specialty_id')
            ->where('examination_rooms.id', $roomId)->value('specialties.name');
    }

    private function specialtyCatalog(): array
    {
        return [
            ['CKNGT'], ['CKNHI'], ['CKSPK'], ['CKMAT'], ['CKCXK'], ['CKTK'],
            ['CKHH'], ['CKUB'], ['CKNT'], ['CKUT'], ['CKRHM'], ['CKTN'],
        ];
    }

    private function seedSpecialties(): array
    {
        $catalog = [
            ['CKNGT', 'Ngoại tổng quát', 'Khám và theo dõi các vấn đề ngoại khoa thường gặp.', 'CKNGT'],
            ['CKNHI', 'Nhi khoa', 'Khám sức khỏe và bệnh lý ở trẻ em.', 'CKNHI'],
            ['CKSPK', 'Sản phụ khoa', 'Chăm sóc sức khỏe phụ nữ và theo dõi thai sản.', 'CKSPK'],
            ['CKMAT', 'Mắt', 'Khám mắt và các phần phụ của mắt.', 'CKMAT'],
            ['CKCXK', 'Cơ xương khớp', 'Khám cơ, xương, khớp và mô liên kết.', 'CKCXK'],
            ['CKTK', 'Thần kinh', 'Khám hệ thần kinh trung ương và ngoại biên.', 'CKTK'],
            ['CKHH', 'Hô hấp', 'Khám đường hô hấp và chức năng phổi.', 'CKHH'],
            ['CKUB', 'Thận - Tiết niệu', 'Khám thận, bàng quang và hệ tiết niệu.', 'CKUB'],
            ['CKNT', 'Nội tiết', 'Khám các bệnh lý nội tiết và chuyển hóa.', 'CKNT'],
            ['CKUT', 'Ung bướu', 'Khám, tư vấn và theo dõi bệnh lý ung bướu.', 'CKUT'],
            ['CKRHM', 'Răng Hàm Mặt', 'Khám răng, hàm, mặt và chăm sóc sức khỏe răng miệng.', 'CKRHM'],
            ['CKTN', 'Truyền nhiễm', 'Khám và theo dõi các bệnh lý nhiễm trùng.', 'CKTN'],
        ];

        foreach ($catalog as [$code, $name, $description, $scope]) {
            if (DB::table('specialties')->where('specialty_code', $code)->exists()) {
                continue;
            }

            DB::table('specialties')->insert([
                'specialty_code' => $code,
                'name' => $name,
                'description' => $description,
                'image_path' => null,
                'pre_visit_instructions' => 'Mang hồ sơ y tế liên quan nếu có và làm theo hướng dẫn của cơ sở khám chữa bệnh.',
                'status' => 'ACTIVE',
                'is_general' => false,
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->inserted++;
        }

        return DB::table('specialties')->pluck('id', 'specialty_code')->all();
    }

    private function seedRooms(array $specialties): void
    {
        $numbers = [
            'CKDK' => [104, 105, 106],
            'CKTMH' => [201, 202, 203],
            'CKTM' => [301, 302, 303],
            'CKNOI' => [101, 107, 108],
            'CKTH' => [102, 109, 110],
            'CKDL' => [103, 111, 112],
            'CKNGT' => [401, 402, 403],
            'CKNHI' => [404, 405, 406],
            'CKSPK' => [407, 408, 409],
            'CKMAT' => [410, 411, 412],
            'CKCXK' => [501, 502, 503],
            'CKTK' => [504, 505, 506],
            'CKHH' => [507, 508, 509],
            'CKUB' => [510, 511, 512],
            'CKNT' => [603, 604, 605],
            'CKUT' => [606, 607, 608],
            'CKRHM' => [609, 610, 611],
            'CKTN' => [612, 613, 614],
        ];

        foreach ($numbers as $specialtyCode => $roomNumbers) {
            if (! isset($specialties[$specialtyCode])) {
                throw new RuntimeException("Thiếu chuyên khoa {$specialtyCode}; không thể gán phòng khám.");
            }

            foreach ($roomNumbers as $number) {
                $matches = DB::table('examination_rooms')->where('room_number', (string) $number)->get();
                if ($matches->count() > 1) {
                    throw new RuntimeException("Số phòng {$number} đang bị trùng; seeder dừng để không đổi nhầm phòng.");
                }

                $name = "Phòng khám {$number}";
                $room = $matches->first();
                if ($room) {
                    if ((int) $room->specialty_id !== (int) $specialties[$specialtyCode]) {
                        throw new RuntimeException("Phòng {$number} hiện thuộc chuyên khoa khác hoặc chưa được gán; cần rà soát trước khi nạp.");
                    }
                    if ($room->name !== $name) {
                        DB::table('examination_rooms')->where('id', $room->id)->update(['name' => $name, 'updated_at' => now()]);
                        $this->inserted++;
                    }
                    continue;
                }

                $floor = (int) floor($number / 100);
                $building = $floor <= 3 ? 'Nhà A' : 'Nhà B';
                $roomCode = sprintf('PK%d', $number);
                if (DB::table('examination_rooms')->where('room_code', $roomCode)->exists()) {
                    throw new RuntimeException("Mã phòng {$roomCode} đã tồn tại nhưng chưa có số phòng tương ứng.");
                }

                DB::table('examination_rooms')->insert([
                    'room_code' => $roomCode,
                    'specialty_id' => $specialties[$specialtyCode],
                    'name' => $name,
                    'room_number' => (string) $number,
                    'floor' => (string) $floor,
                    'building' => $building,
                    'location_instructions' => "Tòa {$building}, tầng {$floor}, phòng {$number}.",
                    'description' => 'Phòng khám ngoại trú thuộc chuyên khoa '.$specialtyCode.'.',
                    'status' => 'ACTIVE',
                    'notes' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->inserted++;
            }
        }
    }

    private function seedDoctors(array $specialties, int $roleId): void
    {
        $targetPerSpecialty = 4;
        $seq = 1;
        $specialtyRows = DB::table('specialties')->orderBy('id')->get(['id', 'specialty_code', 'name']);
        foreach ($specialtyRows as $specialty) {
            $existing = DB::table('doctors')->where('specialty_id', $specialty->id)->count();
            for ($n = $existing; $n < $targetPerSpecialty; $n++) {
                $seq = $this->nextAvailableSequence('BS9', '0989', $seq);
                $code = 'BS9'.str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
                $phone = '0989'.str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
                $nameIndex = $seq;
                $gender = $seq % 2 === 0 ? 'Nữ' : 'Nam';
                $userId = $this->createUser($code, $phone, $roleId);
                $doctorId = DB::table('doctors')->insertGetId([
                    'user_id' => $userId,
                    'specialty_id' => $specialty->id,
                    'doctor_code' => $code,
                    'full_name' => $this->personName($nameIndex, $gender === 'Nữ'),
                    'date_of_birth' => $this->birthDate($nameIndex, 31, 64),
                    'gender' => $gender,
                    'status' => 'ACTIVE',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $address = $this->personAddress($seq);
                DB::table('doctor_details')->insert([
                    'doctor_id' => $doctorId,
                    'citizen_id_number' => $this->citizenId('031', $seq),
                    'citizen_id_issue_date' => null,
                    'citizen_id_issue_place' => 'Công an thành phố Hà Nội',
                    'email' => strtolower($code).'@mediway.test',
                    'hometown' => $this->hometownProvince($seq),
                    'permanent_address' => $address,
                    'current_address' => $address,
                    'nationality' => 'Việt Nam',
                    'ethnicity' => 'Kinh',
                    'education_level' => 'Đại học',
                    'professional_qualification' => 'Bác sĩ chuyên khoa',
                    'academic_title_degree' => $seq % 3 === 0 ? 'Bác sĩ chuyên khoa I' : 'Bác sĩ đa khoa',
                    'professional_title' => 'Bác sĩ',
                    'current_position' => $seq % 3 === 0 ? 'Bác sĩ thực hiện dịch vụ' : 'Bác sĩ khám bệnh',
                    'current_workplace' => 'Bệnh viện Mediway',
                    'work_start_date' => null,
                    'years_of_experience' => (($seq - 1) % 25) + 1,
                    'specialized_fields' => $specialty->name,
                    'professional_skills' => 'Khám lâm sàng, tư vấn và theo dõi người bệnh trong phạm vi chuyên khoa.',
                    'languages' => 'Tiếng Việt',
                    'practice_license_number' => 'GPHN-MW-'.str_pad((string) $seq, 6, '0', STR_PAD_LEFT),
                    'practice_license_issue_date' => null,
                    'practice_license_issue_place' => null,
                    'practice_license_expiry_date' => null,
                    'practice_scope' => 'Khám bệnh, chữa bệnh chuyên khoa '.$specialty->name.'.',
                    'professional_bio' => 'Bác sĩ phụ trách chuyên môn '.$specialty->name.' tại Bệnh viện Mediway.',
                    'avatar_path' => null,
                    'citizen_id_front_path' => null,
                    'citizen_id_back_path' => null,
                    'practice_license_file_path' => null,
                    'profile_pdf_path' => null,
                    'notes' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->inserted += 3;
                $seq++;
            }
        }
    }

    private function seedEmployees(int $roleId): void
    {
        $targetTotal = 48;
        $missing = max(0, $targetTotal - DB::table('employees')->count());
        $seq = 1;
        $newCount = 0;

        while ($newCount < $missing) {
            $seq = $this->nextAvailableSequence('NV9', '0979', $seq);
            $code = 'NV9'.str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
            $phone = '0979'.str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
            $nameIndex = 100 + $seq;
            $female = $seq % 2 === 0;
            $name = $this->personName($nameIndex, $female);
            $position = 'Nhân viên';
            $address = $this->personAddress($seq + 400);
            $userId = $this->createUser($code, $phone, $roleId);
            $employeeId = DB::table('employees')->insertGetId([
                'user_id' => $userId,
                'employee_code' => $code,
                'full_name' => $name,
                'date_of_birth' => $this->birthDate($nameIndex, 21, 59),
                'gender' => $female ? 'Nữ' : 'Nam',
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('employee_details')->insert([
                'employee_id' => $employeeId,
                'citizen_id_number' => $this->citizenId('048', $seq),
                'citizen_id_issue_date' => null,
                'citizen_id_issue_place' => 'Công an thành phố Hà Nội',
                'email' => strtolower($code).'@mediway.test',
                'hometown' => $this->hometownProvince($seq + 400),
                'permanent_address' => $address,
                'current_address' => $address,
                'nationality' => 'Việt Nam',
                'ethnicity' => 'Kinh',
                'education_level' => 'Đại học',
                'professional_qualification' => 'Nghiệp vụ quản lý y tế',
                'position' => $position,
                'hire_date' => sprintf('%04d-%02d-%02d', 2018 + (($seq - 1) % 9), (($seq - 1) % 12) + 1, (($seq - 1) % 27) + 1),
                'termination_date' => null,
                'years_of_service' => null,
                'primary_shift' => $seq % 2 === 0 ? 'Ca sáng' : 'Ca chiều',
                'work_location' => 'Bệnh viện Mediway',
                'job_description' => 'Hỗ trợ tiếp đón người bệnh, tra cứu lịch hẹn và ghi nhận thanh toán theo phân công trong ca.',
                'professional_skills' => 'Tiếp nhận hồ sơ, hướng dẫn quy trình, thu ngân và đối soát các khoản thu.',
                'avatar_path' => null,
                'citizen_id_front_path' => null,
                'citizen_id_back_path' => null,
                'profile_pdf_path' => null,
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->inserted += 3;
            $newCount++;
            $seq++;
        }
    }

    private function seedPatients(int $roleId): void
    {
        $targetTotal = 260;
        $missing = max(0, $targetTotal - DB::table('patients')->count());
        $seq = 1;

        for ($created = 0; $created < $missing; $created++) {
            $seq = $this->nextAvailableSequence('BN9', '0969', $seq);
            $code = 'BN9'.str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
            $phone = '0969'.str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
            $nameIndex = 200 + $seq;
            $female = $seq % 2 === 0;
            $name = $this->personName($nameIndex, $female);
            $address = $this->personAddress($seq + 800);
            $userId = $this->createUser($code, $phone, $roleId);
            $patientId = DB::table('patients')->insertGetId([
                'user_id' => $userId,
                'patient_code' => $code,
                'full_name' => $name,
                'date_of_birth' => $this->birthDate($nameIndex, 1, 89),
                'gender' => $female ? 'Nữ' : 'Nam',
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $contactName = $this->personName($nameIndex + 500, ! $female);
            DB::table('patient_details')->insert([
                'patient_id' => $patientId,
                'citizen_id_number' => $this->citizenId('001', $seq),
                'citizen_id_issue_date' => null,
                'citizen_id_issue_place' => 'Công an thành phố Hà Nội',
                'email' => strtolower($code).'@mediway.test',
                'hometown' => $this->hometownProvince($seq + 800),
                'permanent_address' => $address,
                'current_address' => $address,
                'nationality' => 'Việt Nam',
                'ethnicity' => 'Kinh',
                'occupation' => $this->occupations()[$seq % count($this->occupations())],
                'blood_type' => $this->bloodTypes()[$seq % count($this->bloodTypes())],
                'emergency_contact_name' => $contactName,
                'emergency_contact_phone' => '0958'.str_pad((string) $seq, 6, '0', STR_PAD_LEFT),
                'emergency_contact_relationship' => 'Người thân',
                'avatar_path' => null,
                'citizen_id_front_path' => null,
                'citizen_id_back_path' => null,
                'profile_pdf_path' => null,
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->inserted += 3;
            $seq++;
        }
    }

    private function seedServices(): void
    {
        $groupIds = DB::table('service_groups')->pluck('id', 'service_group_code')->all();
        $specs = $this->serviceCatalog();
        $templates = $this->resultTemplates();
        $templateIds = [];

        foreach ($templates as $key => $template) {
            $existing = DB::table('service_result_templates')->where('name', $template['name'])->first();
            if ($existing) {
                $templateIds[$key] = $existing->id;
                continue;
            }

            $templateIds[$key] = DB::table('service_result_templates')->insertGetId([
                'name' => $template['name'],
                'description' => $template['description'],
                'fields' => json_encode($template['fields'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'is_active' => true,
                'effective_from' => today()->toDateString(),
                'effective_until' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->inserted++;
        }

        foreach ($specs as $index => [$groupCode, $name, $price, $unit, $duration, $description, $templateKey]) {
            if (! isset($groupIds[$groupCode])) {
                throw new RuntimeException("Thiếu nhóm dịch vụ {$groupCode}; seeder không tự tạo nhóm giả.");
            }
            if (! isset($templateIds[$templateKey])) {
                throw new RuntimeException("Thiếu mẫu kết quả đã gán rõ cho dịch vụ {$name}.");
            }

            $code = 'DV9'.str_pad((string) ($index + 1), 6, '0', STR_PAD_LEFT);
            if (DB::table('medical_services')->where('service_code', $code)->exists()) {
                continue;
            }
            if (DB::table('medical_services')->where('name', $name)->exists()) {
                throw new RuntimeException("Tên dịch vụ '{$name}' đã được dùng với mã khác; dừng để tránh gán nhầm mẫu kết quả.");
            }

            $serviceId = DB::table('medical_services')->insertGetId([
                'service_code' => $code,
                'name' => $name,
                'description' => $description,
                'price' => $price,
                'unit' => $unit,
                'estimated_duration_minutes' => $duration,
                'preparation_instructions' => null,
                'precautions' => null,
                'display_order' => 500 + $index + 1,
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('service_group_medical_service')->insert([
                'service_group_id' => $groupIds[$groupCode],
                'medical_service_id' => $serviceId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('medical_service_result_templates')->insert([
                'medical_service_id' => $serviceId,
                'service_result_template_id' => $templateIds[$templateKey],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->inserted += 3;
        }

        // Assign templates to the original catalog by exact service code.
        // This also covers a fresh database where the legacy FK was empty.
        foreach ($this->baseServiceTemplateMap() as $serviceCode => $templateKey) {
            $serviceId = DB::table('medical_services')->where('service_code', $serviceCode)->value('id');
            if (! $serviceId) {
                throw new RuntimeException("Thiếu dịch vụ {$serviceCode}; không thể gán mẫu kết quả.");
            }
            if (DB::table('medical_service_result_templates')->where('medical_service_id', $serviceId)->exists()) {
                continue;
            }
            if (! isset($templateIds[$templateKey])) {
                throw new RuntimeException("Thiếu mẫu kết quả '{$templateKey}' cho dịch vụ {$serviceCode}.");
            }

            DB::table('medical_service_result_templates')->insert([
                'medical_service_id' => $serviceId,
                'service_result_template_id' => $templateIds[$templateKey],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->inserted++;
        }
    }

    /** Explicit mapping for the original services; never infer by display name. */
    private function baseServiceTemplateMap(): array
    {
        return [
            'DVXN001' => 'BIOCHEM',
            'DVXN002' => 'BIOCHEM',
            'DVXN003' => 'BIOCHEM',
            'DVXN004' => 'URINE',
            'DVXN005' => 'BIOCHEM',
            'DVXN006' => 'BIOCHEM',
            'DVXN007' => 'BIOCHEM',
            'DVCD001' => 'XRAY',
            'DVCD002' => 'XRAY',
            'DVCD003' => 'ULTRASOUND',
            'DVCD004' => 'XRAY',
            'DVCD005' => 'CTMRI',
            'DVCD006' => 'CTMRI',
            'DVSA001' => 'ULTRASOUND',
            'DVSA002' => 'ULTRASOUND',
            'DVSA003' => 'ULTRASOUND',
            'DVTDCN001' => 'ECG',
            'DVTDCN002' => 'ENDOSCOPY',
            'DVTDCN003' => 'FUNCTION',
            'DVTDCN004' => 'FUNCTION',
            'DVTDCN005' => 'FUNCTION',
            'DVNS001' => 'ENDOSCOPY',
            'DVNS002' => 'ENDOSCOPY',
            'DVNS003' => 'ENDOSCOPY',
            'DVTT001' => 'PROCEDURE',
            'DVTT002' => 'PROCEDURE',
            'DVTT003' => 'PROCEDURE',
            'DVTT004' => 'PROCEDURE',
            'DVPHCN001' => 'REHAB',
            'DVPHCN002' => 'REHAB',
            'DVPHCN003' => 'REHAB',
            'DVCSSK001' => 'CARE',
            'DVCSSK002' => 'CARE',
            'DVCSSK003' => 'CARE',
        ];
    }

    private function seedDiseaseGroupsAndDiseases(): void
    {
        $groups = [
            ['ICD01', 'I', 'Một số bệnh nhiễm trùng và ký sinh trùng', 'A00-B99'],
            ['ICD02', 'II', 'U tân sinh', 'C00-D48'],
            ['ICD03', 'III', 'Bệnh máu, cơ quan tạo máu và một số rối loạn liên quan đến cơ chế miễn dịch', 'D50-D89'],
            ['ICD04', 'IV', 'Bệnh nội tiết, dinh dưỡng và chuyển hóa', 'E00-E90'],
            ['ICD05', 'V', 'Rối loạn tâm thần và hành vi', 'F00-F99'],
            ['ICD06', 'VI', 'Bệnh hệ thần kinh', 'G00-G99'],
            ['ICD07', 'VII', 'Bệnh mắt và phần phụ', 'H00-H59'],
            ['ICD08', 'VIII', 'Bệnh tai và xương chũm', 'H60-H95'],
            ['ICD09', 'IX', 'Bệnh hệ tuần hoàn', 'I00-I99'],
            ['ICD10', 'X', 'Bệnh hệ hô hấp', 'J00-J99'],
            ['ICD11', 'XI', 'Bệnh hệ tiêu hóa', 'K00-K93'],
            ['ICD12', 'XII', 'Bệnh da và mô dưới da', 'L00-L99'],
            ['ICD13', 'XIII', 'Bệnh hệ cơ-xương-khớp và mô liên kết', 'M00-M99'],
            ['ICD14', 'XIV', 'Bệnh hệ tiết niệu-sinh dục', 'N00-N99'],
            ['ICD15', 'XV', 'Thai nghén, sinh đẻ và hậu sản', 'O00-O99'],
            ['ICD16', 'XVI', 'Một số tình trạng bắt nguồn từ chu sinh', 'P00-P96'],
            ['ICD17', 'XVII', 'Dị tật bẩm sinh, biến dạng và bất thường nhiễm sắc thể', 'Q00-Q99'],
            ['ICD18', 'XVIII', 'Triệu chứng, dấu hiệu và những bất thường lâm sàng, cận lâm sàng, không phân loại mục khác', 'R00-R99'],
            ['ICD19', 'XIX', 'Tổn thương, ngộ độc và một số hậu quả khác do nguyên nhân bên ngoài', 'S00-T98'],
            ['ICD20', 'XX', 'Nguyên nhân bên ngoài gây bệnh và tử vong', 'V01-Y98'],
            ['ICD21', 'XXI', 'Các yếu tố liên quan đến tình trạng sức khỏe và tiếp cận dịch vụ y tế', 'Z00-Z99'],
            ['ICD22', 'XXII', 'Mã cho mục đích đặc biệt', 'U00-U99'],
        ];

        foreach ($groups as [$code, $chapter, $name, $range]) {
            $exists = DB::table('disease_groups')->where('disease_group_code', $code)->exists();
            DB::table('disease_groups')->updateOrInsert(
                ['disease_group_code' => $code],
                [
                'name' => $name,
                'description' => "ICD-10 Chương {$chapter} ({$range}) theo Thông tư 06/2026/TT-BYT.",
                'status' => 'ACTIVE',
                'notes' => 'Nguồn: https://datafiles.chinhphu.vn/cpp/files/vbpq/2026/4/06-byt-kem.pdf',
                'created_at' => now(),
                'updated_at' => now(),
                ]
            );
            if (! $exists) $this->inserted++;
        }

        $groupIds = DB::table('disease_groups')->pluck('id', 'disease_group_code')->all();
        foreach ($this->diseaseCatalog() as [$code, $groupCode, $name, $alternative]) {
            if (! isset($groupIds[$groupCode])) {
                throw new RuntimeException("Thiếu nhóm bệnh {$groupCode}; dừng để tránh tạo bệnh không có nhóm.");
            }
            $exists = DB::table('diseases')->where('disease_code', $code)->exists();
            DB::table('diseases')->updateOrInsert(
                ['disease_code' => $code],
                [
                'disease_group_id' => $groupIds[$groupCode],
                'name' => $name,
                'alternative_name' => $alternative,
                'description' => 'Mã bệnh ICD-10 theo danh mục ban hành kèm Thông tư 06/2026/TT-BYT.',
                'common_symptoms' => null,
                'professional_notes' => null,
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
                ]
            );
            if (! $exists) $this->inserted++;
        }
    }

    private function seedMedicines(): void
    {
        foreach ($this->medicineCatalog() as $index => [$name, $ingredient, $strength, $form, $unit, $route]) {
            $code = 'TH9'.str_pad((string) ($index + 1), 6, '0', STR_PAD_LEFT);
            if (DB::table('medicines')->where('medicine_code', $code)->exists()) {
                continue;
            }
            DB::table('medicines')->insert([
                'medicine_code' => $code,
                'name' => $name,
                'active_ingredient' => $ingredient,
                'strength' => $strength,
                'dosage_form' => $form,
                'unit' => $unit,
                'route_of_administration' => $route,
                'manufacturer' => null,
                'country_of_origin' => null,
                'description' => 'Thuốc và dạng bào chế được quản lý trong danh mục dược của cơ sở.',
                'indications' => null,
                'contraindications' => null,
                'warnings' => 'Sử dụng theo chỉ định của người hành nghề và hướng dẫn sử dụng đã được phê duyệt.',
                'common_side_effects' => null,
                'storage_instructions' => null,
                'status' => 'ACTIVE',
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->inserted++;
        }
    }

    private function createUser(string $code, string $phone, int $roleId): int
    {
        if (DB::table('users')->where('business_code', $code)->exists() || DB::table('users')->where('phone', $phone)->exists()) {
            throw new RuntimeException("Tài khoản {$code} hoặc số điện thoại {$phone} đã tồn tại; dừng để không gắn nhầm hồ sơ.");
        }

        $attributes = [
            'business_code' => $code,
            'phone' => $phone,
            'password' => Hash::make(self::INITIAL_PASSWORD),
            'role_id' => $roleId,
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('users', 'must_change_password')) {
            $attributes['must_change_password'] = false;
        }

        return DB::table('users')->insertGetId($attributes);
    }

    private function nextAvailableSequence(string $codePrefix, string $phonePrefix, int $sequence): int
    {
        while (true) {
            $code = $codePrefix.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
            $phone = $phonePrefix.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
            $citizenPrefixes = match ($codePrefix) {
                'BS9' => ['doctor_details', '031'],
                'NV9' => ['employee_details', '048'],
                default => ['patient_details', '001'],
            };
            $citizenId = $this->citizenId($citizenPrefixes[1], $sequence);
            $citizenExists = DB::table($citizenPrefixes[0])->where('citizen_id_number', $citizenId)->exists();
            if (! DB::table('users')->where('business_code', $code)->exists()
                && ! DB::table('users')->where('phone', $phone)->exists()
                && ! DB::table(match ($codePrefix) { 'BS9' => 'doctors', 'NV9' => 'employees', default => 'patients' })
                    ->where(match ($codePrefix) { 'BS9' => 'doctor_code', 'NV9' => 'employee_code', default => 'patient_code' }, $code)->exists()
                && ! $citizenExists) {
                return $sequence;
            }
            $sequence++;
        }
    }

    private function personName(int $index, bool $female): string
    {
        $surnames = ['Nguyễn', 'Trần', 'Lê', 'Phạm', 'Hoàng', 'Vũ', 'Đặng', 'Bùi', 'Đỗ', 'Ngô'];
        $middles = $female
            ? ['Thị', 'Minh', 'Thu', 'Ngọc', 'Bảo']
            : ['Văn', 'Minh', 'Quốc', 'Hữu', 'Thanh'];
        $givenNames = $female
            ? ['Anh', 'Linh', 'Hà', 'Trang', 'Hương', 'Mai', 'Thảo', 'Ngân', 'Châu', 'Vy']
            : ['An', 'Bình', 'Dũng', 'Đức', 'Hải', 'Khoa', 'Long', 'Nam', 'Phúc', 'Tuấn'];
        $local = max(0, $index);

        return $surnames[$local % count($surnames)].' '
            .$middles[intdiv($local, 10) % count($middles)].' '
            .$givenNames[intdiv($local, 100) % count($givenNames)];
    }

    private function birthDate(int $index, int $minimumAge, int $maximumAge): string
    {
        $range = $maximumAge - $minimumAge + 1;
        $age = $minimumAge + ($index % $range);
        $month = (($index * 7) % 12) + 1;
        $day = (($index * 11) % 27) + 1;

        return sprintf('%04d-%02d-%02d', 2026 - $age, $month, $day);
    }

    private function citizenId(string $prefix, int $sequence): string
    {
        return $prefix.str_pad((string) $sequence, 9, '0', STR_PAD_LEFT);
    }

    private function personAddress(int $index): string
    {
        $streets = ['Nguyễn Trãi', 'Giải Phóng', 'Lê Trọng Tấn', 'Trần Phú', 'Hoàng Quốc Việt', 'Cầu Giấy', 'Kim Mã', 'Lạc Long Quân'];
        $number = (($index * 7) % 299) + 1;

        return 'Số '.$number.' đường '.$streets[$index % count($streets)].', Hà Nội';
    }

    private function hometownProvince(int $index): string
    {
        $provinces = ['Hà Nội', 'Hải Phòng', 'Đà Nẵng', 'Cần Thơ', 'Lâm Đồng', 'Nghệ An', 'Khánh Hòa', 'Quảng Ninh'];
        return $provinces[$index % count($provinces)];
    }

    private function occupations(): array
    {
        return ['Nhân viên văn phòng', 'Giáo viên', 'Kỹ sư', 'Sinh viên', 'Hưu trí', 'Kinh doanh', 'Lao động tự do', 'Nội trợ'];
    }

    private function bloodTypes(): array
    {
        return ['O+', 'A+', 'B+', 'AB+', 'O-', 'A-', 'B-', 'AB+'];
    }

    private function resultTemplates(): array
    {
        $clinicalFields = [
            ['key' => 'technique', 'label' => 'Kỹ thuật / phương pháp thực hiện', 'type' => 'text', 'required' => false],
            ['key' => 'findings', 'label' => 'Nội dung ghi nhận', 'type' => 'textarea', 'required' => true],
            ['key' => 'conclusion', 'label' => 'Kết luận', 'type' => 'textarea', 'required' => true],
        ];
        $labFields = [
            ['key' => 'specimen', 'label' => 'Loại bệnh phẩm', 'type' => 'text', 'required' => false],
            ['key' => 'measurements', 'label' => 'Các chỉ số xét nghiệm', 'type' => 'measurements', 'required' => true],
            ['key' => 'comment', 'label' => 'Nhận xét', 'type' => 'textarea', 'required' => false],
            ['key' => 'conclusion', 'label' => 'Kết luận', 'type' => 'textarea', 'required' => false],
        ];

        return [
            'BIOCHEM' => ['name' => 'Phiếu kết quả xét nghiệm sinh hóa', 'description' => 'Phiếu ghi nhận các chỉ số xét nghiệm sinh hóa.', 'fields' => $labFields],
            'URINE' => ['name' => 'Phiếu kết quả tổng phân tích nước tiểu', 'description' => 'Phiếu ghi nhận kết quả tổng phân tích nước tiểu.', 'fields' => $labFields],
            'MICRO' => ['name' => 'Phiếu kết quả vi sinh và miễn dịch', 'description' => 'Phiếu ghi nhận kết quả xét nghiệm vi sinh và miễn dịch.', 'fields' => $labFields],
            'XRAY' => ['name' => 'Phiếu kết quả chẩn đoán hình ảnh X-quang', 'description' => 'Phiếu kết quả chẩn đoán hình ảnh X-quang.', 'fields' => $clinicalFields],
            'CTMRI' => ['name' => 'Phiếu kết quả chẩn đoán hình ảnh CT và MRI', 'description' => 'Phiếu kết quả chẩn đoán hình ảnh cắt lớp và cộng hưởng từ.', 'fields' => $clinicalFields],
            'ULTRASOUND' => ['name' => 'Phiếu kết quả siêu âm', 'description' => 'Phiếu ghi nhận kỹ thuật và kết quả siêu âm.', 'fields' => $clinicalFields],
            'ENDOSCOPY' => ['name' => 'Phiếu kết quả nội soi', 'description' => 'Phiếu ghi nhận phạm vi khảo sát, hình ảnh và kết luận nội soi.', 'fields' => $clinicalFields],
            'ECG' => ['name' => 'Phiếu kết quả điện tâm đồ', 'description' => 'Phiếu ghi nhận thông số và kết luận điện tâm đồ.', 'fields' => $clinicalFields],
            'FUNCTION' => ['name' => 'Phiếu kết quả thăm dò chức năng', 'description' => 'Phiếu ghi nhận các chỉ số thăm dò chức năng.', 'fields' => $labFields],
            'PROCEDURE' => ['name' => 'Phiếu kết quả thủ thuật', 'description' => 'Phiếu ghi nhận kỹ thuật, quá trình và kết quả thủ thuật.', 'fields' => $clinicalFields],
            'REHAB' => ['name' => 'Phiếu kết quả phục hồi chức năng', 'description' => 'Phiếu ghi nhận nội dung buổi phục hồi chức năng.', 'fields' => $clinicalFields],
            'CARE' => ['name' => 'Phiếu kết quả chăm sóc ngoại trú', 'description' => 'Phiếu ghi nhận nội dung chăm sóc ngoại trú.', 'fields' => $clinicalFields],
        ];
    }

    private function serviceCatalog(): array
    {
        return [
            ['XN', 'Định lượng AST (GOT)', 55000, 'lần', 25, 'Định lượng enzym AST trong mẫu máu.', 'BIOCHEM'],
            ['XN', 'Định lượng ALT (GPT)', 55000, 'lần', 25, 'Định lượng enzym ALT trong mẫu máu.', 'BIOCHEM'],
            ['XN', 'Định lượng ure máu', 55000, 'lần', 25, 'Định lượng ure trong mẫu máu.', 'BIOCHEM'],
            ['XN', 'Định lượng acid uric máu', 65000, 'lần', 25, 'Định lượng acid uric trong mẫu máu.', 'BIOCHEM'],
            ['XN', 'Định lượng natri máu', 65000, 'lần', 25, 'Định lượng natri trong mẫu máu.', 'BIOCHEM'],
            ['XN', 'Định lượng kali máu', 65000, 'lần', 25, 'Định lượng kali trong mẫu máu.', 'BIOCHEM'],
            ['XN', 'Định lượng calci toàn phần', 65000, 'lần', 25, 'Định lượng calci toàn phần trong mẫu máu.', 'BIOCHEM'],
            ['XN', 'Định lượng albumin máu', 60000, 'lần', 25, 'Định lượng albumin trong mẫu máu.', 'BIOCHEM'],
            ['XN', 'Định lượng protein toàn phần', 60000, 'lần', 25, 'Định lượng protein toàn phần trong mẫu máu.', 'BIOCHEM'],
            ['XN', 'Định lượng GGT', 60000, 'lần', 25, 'Định lượng gamma-glutamyl transferase trong mẫu máu.', 'BIOCHEM'],
            ['XN', 'Định lượng phosphatase kiềm (ALP)', 60000, 'lần', 25, 'Định lượng phosphatase kiềm trong mẫu máu.', 'BIOCHEM'],
            ['XN', 'Định lượng bilirubin toàn phần', 60000, 'lần', 25, 'Định lượng bilirubin toàn phần trong mẫu máu.', 'BIOCHEM'],
            ['XN', 'Định lượng bilirubin trực tiếp', 60000, 'lần', 25, 'Định lượng bilirubin trực tiếp trong mẫu máu.', 'BIOCHEM'],
            ['XN', 'Định lượng troponin I độ nhạy cao', 250000, 'lần', 40, 'Xét nghiệm định lượng troponin I độ nhạy cao.', 'BIOCHEM'],
            ['XN', 'Định lượng ferritin', 140000, 'lần', 35, 'Định lượng ferritin trong mẫu máu.', 'BIOCHEM'],
            ['XN', 'Định lượng TSH', 150000, 'lần', 40, 'Định lượng hormon kích thích tuyến giáp.', 'BIOCHEM'],
            ['XN', 'Định lượng FT4', 150000, 'lần', 40, 'Định lượng thyroxin tự do.', 'BIOCHEM'],
            ['XN', 'Định lượng D-dimer', 220000, 'lần', 40, 'Xét nghiệm định lượng D-dimer.', 'BIOCHEM'],
            ['XN', 'Tổng phân tích nước tiểu 10 thông số', 95000, 'lần', 30, 'Phân tích các chỉ số hóa học và vật lý của nước tiểu.', 'URINE'],
            ['XN', 'Cấy nước tiểu và kháng sinh đồ', 280000, 'lần', 72, 'Nuôi cấy mẫu nước tiểu và ghi nhận kết quả vi sinh.', 'MICRO'],

            ['CDHA', 'X-quang xương bàn tay thẳng - nghiêng', 180000, 'lần', 20, 'Chụp X-quang xương bàn tay theo các tư thế được chỉ định.', 'XRAY'],
            ['CDHA', 'X-quang khớp gối thẳng - nghiêng', 220000, 'lần', 20, 'Chụp X-quang khớp gối theo các tư thế được chỉ định.', 'XRAY'],
            ['CDHA', 'X-quang khung chậu thẳng', 220000, 'lần', 20, 'Chụp X-quang khung chậu tư thế thẳng.', 'XRAY'],
            ['CDHA', 'X-quang cột sống cổ thẳng - nghiêng', 250000, 'lần', 25, 'Chụp X-quang cột sống cổ theo các tư thế được chỉ định.', 'XRAY'],
            ['CDHA', 'X-quang ngực nghiêng', 180000, 'lần', 20, 'Chụp X-quang lồng ngực tư thế nghiêng.', 'XRAY'],
            ['CDHA', 'X-quang xương đùi thẳng - nghiêng', 220000, 'lần', 20, 'Chụp X-quang xương đùi theo các tư thế được chỉ định.', 'XRAY'],
            ['CDHA', 'X-quang cột sống thắt lưng thẳng - nghiêng', 280000, 'lần', 25, 'Chụp X-quang cột sống thắt lưng theo các tư thế được chỉ định.', 'XRAY'],
            ['CDHA', 'X-quang khớp vai thẳng', 200000, 'lần', 20, 'Chụp X-quang khớp vai tư thế thẳng.', 'XRAY'],
            ['CDHA', 'CT lồng ngực không tiêm thuốc cản quang', 1100000, 'lần', 35, 'Chụp cắt lớp vi tính vùng lồng ngực.', 'CTMRI'],
            ['CDHA', 'CT ổ bụng - chậu', 1450000, 'lần', 40, 'Chụp cắt lớp vi tính vùng bụng và chậu.', 'CTMRI'],
            ['CDHA', 'CT xoang cạnh mũi', 850000, 'lần', 30, 'Chụp cắt lớp vi tính vùng xoang cạnh mũi.', 'CTMRI'],
            ['CDHA', 'CT cột sống thắt lưng', 1200000, 'lần', 35, 'Chụp cắt lớp vi tính vùng cột sống thắt lưng.', 'CTMRI'],
            ['CDHA', 'MRI sọ não', 1800000, 'lần', 50, 'Chụp cộng hưởng từ vùng sọ não.', 'CTMRI'],
            ['CDHA', 'MRI cột sống thắt lưng', 1900000, 'lần', 50, 'Chụp cộng hưởng từ vùng cột sống thắt lưng.', 'CTMRI'],
            ['CDHA', 'MRI khớp vai', 1800000, 'lần', 45, 'Chụp cộng hưởng từ khớp vai.', 'CTMRI'],
            ['CDHA', 'MRI khớp háng', 1800000, 'lần', 45, 'Chụp cộng hưởng từ khớp háng.', 'CTMRI'],

            ['SA', 'Siêu âm tuyến vú hai bên', 300000, 'lần', 25, 'Khảo sát mô tuyến vú hai bên bằng siêu âm.', 'ULTRASOUND'],
            ['SA', 'Siêu âm tuyến tiền liệt qua thành bụng', 280000, 'lần', 25, 'Khảo sát tuyến tiền liệt qua thành bụng bằng siêu âm.', 'ULTRASOUND'],
            ['SA', 'Siêu âm Doppler mạch cảnh', 450000, 'lần', 35, 'Khảo sát mạch cảnh bằng siêu âm Doppler.', 'ULTRASOUND'],
            ['SA', 'Siêu âm Doppler tĩnh mạch chi dưới', 500000, 'lần', 40, 'Khảo sát hệ tĩnh mạch chi dưới bằng siêu âm Doppler.', 'ULTRASOUND'],
            ['SA', 'Siêu âm khớp gối', 260000, 'lần', 20, 'Khảo sát khớp gối bằng siêu âm.', 'ULTRASOUND'],
            ['SA', 'Siêu âm phần mềm thành bụng', 250000, 'lần', 20, 'Khảo sát phần mềm thành bụng tại vị trí được chỉ định.', 'ULTRASOUND'],
            ['SA', 'Siêu âm phụ khoa qua đường bụng', 300000, 'lần', 25, 'Khảo sát cơ quan phụ khoa qua đường bụng bằng siêu âm.', 'ULTRASOUND'],
            ['SA', 'Siêu âm Doppler động mạch chi dưới', 500000, 'lần', 40, 'Khảo sát hệ động mạch chi dưới bằng siêu âm Doppler.', 'ULTRASOUND'],

            ['NS', 'Nội soi thực quản - dạ dày - tá tràng', 700000, 'lần', 35, 'Khảo sát đường tiêu hóa trên bằng nội soi.', 'ENDOSCOPY'],
            ['NS', 'Nội soi đại tràng toàn bộ', 980000, 'lần', 50, 'Khảo sát đại tràng bằng nội soi.', 'ENDOSCOPY'],
            ['NS', 'Nội soi trực tràng', 450000, 'lần', 25, 'Khảo sát trực tràng bằng nội soi.', 'ENDOSCOPY'],
            ['NS', 'Nội soi thanh quản', 320000, 'lần', 25, 'Khảo sát thanh quản bằng nội soi.', 'ENDOSCOPY'],
            ['NS', 'Nội soi tai', 220000, 'lần', 15, 'Khảo sát ống tai và màng nhĩ bằng nội soi.', 'ENDOSCOPY'],
            ['NS', 'Nội soi bàng quang chẩn đoán', 1200000, 'lần', 40, 'Khảo sát bàng quang bằng nội soi theo chỉ định.', 'ENDOSCOPY'],

            ['TDCN', 'Siêu âm tim Doppler màu', 450000, 'lần', 35, 'Ghi nhận cấu trúc và dòng máu qua tim bằng siêu âm Doppler.', 'FUNCTION'],
            ['TDCN', 'Điện não đồ thường quy', 350000, 'lần', 30, 'Ghi nhận hoạt động điện não theo quy trình kỹ thuật.', 'FUNCTION'],
            ['TDCN', 'Đo hô hấp ký', 250000, 'lần', 30, 'Ghi nhận các thông số hô hấp ký.', 'FUNCTION'],
            ['TDCN', 'Đo loãng xương bằng phương pháp DEXA', 550000, 'lần', 30, 'Ghi nhận mật độ khoáng xương bằng phương pháp DEXA.', 'FUNCTION'],
            ['TDCN', 'Đo điện cơ và dẫn truyền thần kinh', 700000, 'lần', 45, 'Ghi nhận các thông số điện cơ và dẫn truyền thần kinh.', 'FUNCTION'],
            ['TDCN', 'Đo chỉ số ABI hai chi dưới', 300000, 'lần', 25, 'Ghi nhận chỉ số huyết áp cổ chân - cánh tay.', 'FUNCTION'],

            ['TT', 'Thay băng vết thương sạch', 100000, 'lần', 20, 'Thực hiện chăm sóc và thay băng vết thương ngoại trú.', 'PROCEDURE'],
            ['TT', 'Cắt chỉ vết thương nhỏ', 120000, 'lần', 20, 'Thực hiện cắt chỉ theo chỉ định chuyên môn.', 'PROCEDURE'],
            ['TT', 'Lấy dị vật nông ở da', 250000, 'lần', 25, 'Thực hiện lấy dị vật nông tại vị trí được chỉ định.', 'PROCEDURE'],
            ['TT', 'Rửa vết thương ngoại trú', 120000, 'lần', 20, 'Thực hiện rửa vết thương theo quy trình của cơ sở.', 'PROCEDURE'],
            ['TT', 'Khâu vết thương phần mềm nhỏ', 450000, 'lần', 35, 'Thực hiện thủ thuật khâu vết thương nhỏ theo chỉ định.', 'PROCEDURE'],
            ['TT', 'Chọc hút dịch khớp gối', 500000, 'lần', 30, 'Thực hiện thủ thuật tại khớp gối theo chỉ định.', 'PROCEDURE'],

            ['PHCN', 'Tập phục hồi chức năng khớp vai', 180000, 'buổi', 35, 'Buổi tập phục hồi chức năng khớp vai theo kế hoạch chuyên môn.', 'REHAB'],
            ['PHCN', 'Tập phục hồi chức năng khớp gối', 180000, 'buổi', 35, 'Buổi tập phục hồi chức năng khớp gối theo kế hoạch chuyên môn.', 'REHAB'],
            ['PHCN', 'Tập phục hồi chức năng cột sống', 200000, 'buổi', 40, 'Buổi tập phục hồi chức năng cột sống theo kế hoạch chuyên môn.', 'REHAB'],
            ['PHCN', 'Tập thăng bằng và dáng đi', 180000, 'buổi', 35, 'Buổi tập thăng bằng và dáng đi theo kế hoạch chuyên môn.', 'REHAB'],
            ['PHCN', 'Tập vận động sau bất động chi', 180000, 'buổi', 35, 'Buổi tập vận động phục hồi sau bất động chi.', 'REHAB'],

            ['CSSK', 'Đo huyết áp và ghi nhận chỉ số', 40000, 'lần', 10, 'Ghi nhận huyết áp trong lần khám ngoại trú.', 'CARE'],
            ['CSSK', 'Đo đường huyết mao mạch tại giường', 55000, 'lần', 10, 'Ghi nhận đường huyết mao mạch theo quy trình cơ sở.', 'CARE'],
            ['CSSK', 'Thay băng vết mổ ngoại trú', 150000, 'lần', 25, 'Chăm sóc và thay băng vết mổ ngoại trú.', 'CARE'],
            ['CSSK', 'Tư vấn chăm sóc sức khỏe ban đầu', 80000, 'lần', 20, 'Ghi nhận buổi tư vấn chăm sóc sức khỏe ban đầu.', 'CARE'],
            ['CSSK', 'Theo dõi dấu hiệu sinh tồn ngoại trú', 60000, 'lần', 15, 'Ghi nhận dấu hiệu sinh tồn trong lần chăm sóc ngoại trú.', 'CARE'],
        ];
    }

    private function diseaseCatalog(): array
    {
        return [
            ['A09', 'ICD01', 'Tiêu chảy và viêm dạ dày ruột nghi do nhiễm trùng', 'A09'],
            ['B34.9', 'ICD01', 'Nhiễm vi-rút không xác định', null],
            ['B18.1', 'ICD01', 'Viêm gan vi-rút B mạn tính', null],
            ['B35.1', 'ICD01', 'Nấm móng', null],
            ['C18.9', 'ICD02', 'U ác tính đại tràng, không xác định vị trí', null],
            ['C34.9', 'ICD02', 'U ác tính phế quản và phổi, không xác định vị trí', null],
            ['C50.9', 'ICD02', 'U ác tính vú, không xác định vị trí', null],
            ['C61', 'ICD02', 'U ác tính tuyến tiền liệt', null],
            ['D50.9', 'ICD03', 'Thiếu máu thiếu sắt, không xác định', null],
            ['D64.9', 'ICD03', 'Thiếu máu, không xác định', null],
            ['D69.6', 'ICD03', 'Giảm tiểu cầu, không xác định', null],
            ['E03.9', 'ICD04', 'Suy giáp, không xác định', null],
            ['E78.5', 'ICD04', 'Rối loạn lipid máu, không xác định', null],
            ['E66.9', 'ICD04', 'Béo phì, không xác định', null],
            ['F32.9', 'ICD05', 'Giai đoạn trầm cảm, không xác định', null],
            ['F41.1', 'ICD05', 'Rối loạn lo âu lan tỏa', null],
            ['F51.0', 'ICD05', 'Mất ngủ không do thực thể', null],
            ['G43.9', 'ICD06', 'Migraine, không xác định', null],
            ['G40.9', 'ICD06', 'Động kinh, không xác định', null],
            ['G20', 'ICD06', 'Bệnh Parkinson', null],
            ['G62.9', 'ICD06', 'Bệnh đa dây thần kinh, không xác định', null],
            ['H10.9', 'ICD07', 'Viêm kết mạc, không xác định', null],
            ['H25.9', 'ICD07', 'Đục thủy tinh thể tuổi già, không xác định', null],
            ['H52.4', 'ICD07', 'Lão thị', null],
            ['H61.2', 'ICD08', 'Nút ráy tai', null],
            ['H66.9', 'ICD08', 'Viêm tai giữa, không xác định', null],
            ['H90.5', 'ICD08', 'Giảm thính lực tiếp nhận, không xác định', null],
            ['I25.1', 'ICD09', 'Bệnh tim do xơ vữa động mạch', null],
            ['I48.9', 'ICD09', 'Rung nhĩ và cuồng nhĩ, không xác định', null],
            ['I50.9', 'ICD09', 'Suy tim, không xác định', null],
            ['J18.9', 'ICD10', 'Viêm phổi, không xác định vi sinh vật', null],
            ['J30.9', 'ICD10', 'Viêm mũi dị ứng, không xác định', null],
            ['J45.9', 'ICD10', 'Hen phế quản, không xác định', null],
            ['J20.9', 'ICD10', 'Viêm phế quản cấp, không xác định', null],
            ['K52.9', 'ICD11', 'Viêm dạ dày ruột và đại tràng không nhiễm trùng, không xác định', null],
            ['K80.2', 'ICD11', 'Sỏi túi mật không viêm túi mật', null],
            ['K76.0', 'ICD11', 'Gan nhiễm mỡ, không phân loại nơi khác', null],
            ['L50.9', 'ICD12', 'Mày đay, không xác định', null],
            ['L30.9', 'ICD12', 'Viêm da, không xác định', null],
            ['L40.9', 'ICD12', 'Vảy nến, không xác định', null],
            ['M54.5', 'ICD13', 'Đau thắt lưng', null],
            ['M17.9', 'ICD13', 'Thoái hóa khớp gối, không xác định', null],
            ['M79.1', 'ICD13', 'Đau cơ', null],
            ['M06.9', 'ICD13', 'Viêm khớp dạng thấp, không xác định', null],
            ['M10.9', 'ICD13', 'Gút, không xác định', null],
            ['N39.0', 'ICD14', 'Nhiễm khuẩn đường tiết niệu, không xác định vị trí', null],
            ['N18.9', 'ICD14', 'Bệnh thận mạn, không xác định giai đoạn', null],
            ['N40.1', 'ICD14', 'Tăng sản tuyến tiền liệt lành tính có triệu chứng đường tiểu dưới', null],
            ['N20.0', 'ICD14', 'Sỏi thận', null],
            ['O21.9', 'ICD15', 'Nôn nghén, không xác định', null],
            ['O24.4', 'ICD15', 'Đái tháo đường trong thai kỳ', null],
            ['O80', 'ICD15', 'Chuyển dạ và sinh thường', null],
            ['P07.3', 'ICD16', 'Trẻ sinh non khác', null],
            ['P59.9', 'ICD16', 'Vàng da sơ sinh, không xác định', null],
            ['Q21.1', 'ICD17', 'Thông liên nhĩ', null],
            ['Q90.9', 'ICD17', 'Hội chứng Down, không xác định', null],
            ['R50.9', 'ICD18', 'Sốt, không xác định', null],
            ['R10.9', 'ICD18', 'Đau bụng, không xác định vị trí', null],
            ['R51.9', 'ICD18', 'Đau đầu, không xác định', null],
            ['R06.0', 'ICD18', 'Khó thở', null],
            ['S52.5', 'ICD19', 'Gãy đầu dưới xương quay', null],
            ['S82.0', 'ICD19', 'Gãy xương bánh chè', null],
            ['T14.9', 'ICD19', 'Chấn thương, không xác định', null],
            ['V89.2', 'ICD20', 'Tai nạn giao thông đường bộ, phương tiện không xác định', null],
            ['W19', 'ICD20', 'Ngã, không xác định', null],
            ['X09', 'ICD20', 'Phơi nhiễm khói, lửa và ngọn lửa, không xác định', null],
            ['Z00.0', 'ICD21', 'Khám sức khỏe tổng quát', null],
            ['Z23', 'ICD21', 'Nhu cầu tiêm chủng dự phòng', null],
            ['Z09', 'ICD21', 'Khám theo dõi sau điều trị tình trạng khác', null],
            ['U07.1', 'ICD22', 'COVID-19, vi-rút được xác định', null],
        ];
    }

    private function medicineCatalog(): array
    {
        return [
            ['Paracetamol hỗn dịch uống', 'Paracetamol', '120 mg/5 ml', 'Hỗn dịch uống', 'chai', 'Uống'],
            ['Amoxicillin', 'Amoxicillin', '500 mg', 'Viên nang', 'viên', 'Uống'],
            ['Azithromycin', 'Azithromycin', '500 mg', 'Viên nén', 'viên', 'Uống'],
            ['Doxycycline', 'Doxycycline', '100 mg', 'Viên nang', 'viên', 'Uống'],
            ['Cephalexin', 'Cephalexin', '500 mg', 'Viên nang', 'viên', 'Uống'],
            ['Ciprofloxacin', 'Ciprofloxacin', '500 mg', 'Viên nén', 'viên', 'Uống'],
            ['Metronidazole', 'Metronidazole', '500 mg', 'Viên nén', 'viên', 'Uống'],
            ['Acyclovir', 'Acyclovir', '400 mg', 'Viên nén', 'viên', 'Uống'],
            ['Fluconazole', 'Fluconazole', '150 mg', 'Viên nang', 'viên', 'Uống'],
            ['Albendazole', 'Albendazole', '400 mg', 'Viên nhai', 'viên', 'Uống'],
            ['Losartan', 'Losartan potassium', '100 mg', 'Viên nén', 'viên', 'Uống'],
            ['Amlodipine', 'Amlodipine', '5 mg', 'Viên nén', 'viên', 'Uống'],
            ['Amlodipine', 'Amlodipine', '10 mg', 'Viên nén', 'viên', 'Uống'],
            ['Enalapril', 'Enalapril maleate', '5 mg', 'Viên nén', 'viên', 'Uống'],
            ['Hydrochlorothiazide', 'Hydrochlorothiazide', '25 mg', 'Viên nén', 'viên', 'Uống'],
            ['Atorvastatin', 'Atorvastatin', '20 mg', 'Viên nén', 'viên', 'Uống'],
            ['Simvastatin', 'Simvastatin', '20 mg', 'Viên nén', 'viên', 'Uống'],
            ['Metformin', 'Metformin hydrochloride', '850 mg', 'Viên nén', 'viên', 'Uống'],
            ['Gliclazide giải phóng biến đổi', 'Gliclazide', '30 mg', 'Viên nén giải phóng biến đổi', 'viên', 'Uống'],
            ['Levothyroxine', 'Levothyroxine sodium', '50 microgam', 'Viên nén', 'viên', 'Uống'],
            ['Pantoprazole', 'Pantoprazole', '40 mg', 'Viên nén bao tan trong ruột', 'viên', 'Uống'],
            ['Famotidine', 'Famotidine', '20 mg', 'Viên nén', 'viên', 'Uống'],
            ['Domperidone', 'Domperidone', '10 mg', 'Viên nén', 'viên', 'Uống'],
            ['Oresol', 'Glucose + natri clorid + kali clorid + natri citrat', 'Gói pha dung dịch', 'Bột pha uống', 'gói', 'Uống'],
            ['Ondansetron', 'Ondansetron', '4 mg', 'Viên nén', 'viên', 'Uống'],
            ['Loratadine', 'Loratadine', '10 mg', 'Viên nén', 'viên', 'Uống'],
            ['Salbutamol định liều', 'Salbutamol', '100 microgam/liều', 'Bình xịt định liều', 'bình', 'Hít'],
            ['Budesonide định liều', 'Budesonide', '200 microgam/liều', 'Bình hít định liều', 'bình', 'Hít'],
            ['Prednisolone', 'Prednisolone', '5 mg', 'Viên nén', 'viên', 'Uống'],
            ['Clotrimazole kem bôi', 'Clotrimazole', '1%', 'Kem bôi ngoài da', 'tuýp', 'Bôi ngoài da'],
            ['Miconazole kem bôi', 'Miconazole', '2%', 'Kem bôi ngoài da', 'tuýp', 'Bôi ngoài da'],
            ['Ibuprofen', 'Ibuprofen', '400 mg', 'Viên nén', 'viên', 'Uống'],
            ['Diclofenac gel bôi', 'Diclofenac diethylamine', '1%', 'Gel bôi ngoài da', 'tuýp', 'Bôi ngoài da'],
            ['Acid acetylsalicylic', 'Acid acetylsalicylic', '81 mg', 'Viên nén bao tan trong ruột', 'viên', 'Uống'],
            ['Clopidogrel', 'Clopidogrel', '75 mg', 'Viên nén', 'viên', 'Uống'],
            ['Warfarin', 'Warfarin sodium', '2 mg', 'Viên nén', 'viên', 'Uống'],
            ['Insulin người', 'Insulin người', '100 đơn vị/ml', 'Dung dịch tiêm', 'lọ', 'Tiêm dưới da'],
            ['Sắt sulfat', 'Sắt sulfat', '200 mg', 'Viên nén', 'viên', 'Uống'],
            ['Acid folic', 'Acid folic', '5 mg', 'Viên nén', 'viên', 'Uống'],
            ['Cholecalciferol', 'Vitamin D3', '1000 đơn vị quốc tế', 'Viên nang mềm', 'viên', 'Uống'],
            ['Calci carbonat', 'Calci carbonat', '500 mg', 'Viên nén', 'viên', 'Uống'],
            ['Natri clorid 0,9% dịch truyền', 'Natri clorid', '0,9%', 'Dung dịch truyền tĩnh mạch', 'chai', 'Truyền tĩnh mạch'],
            ['Chlorhexidine dung dịch ngoài da', 'Chlorhexidine gluconate', '0,05%', 'Dung dịch dùng ngoài', 'chai', 'Dùng ngoài'],
            ['Lidocaine', 'Lidocaine hydrochloride', '2%', 'Dung dịch tiêm', 'ống', 'Tiêm'],
            ['Adrenaline', 'Epinephrine', '1 mg/ml', 'Dung dịch tiêm', 'ống', 'Tiêm'],
            ['Furosemide', 'Furosemide', '40 mg', 'Viên nén', 'viên', 'Uống'],
            ['Spironolactone', 'Spironolactone', '25 mg', 'Viên nén', 'viên', 'Uống'],
            ['Bisoprolol', 'Bisoprolol fumarate', '2,5 mg', 'Viên nén', 'viên', 'Uống'],
            ['Nitroglycerin ngậm dưới lưỡi', 'Glyceryl trinitrate', '0,5 mg', 'Viên ngậm dưới lưỡi', 'viên', 'Ngậm dưới lưỡi'],
            ['Ringer lactate', 'Natri clorid + kali clorid + calci clorid + natri lactat', 'Dung dịch truyền', 'Dung dịch truyền tĩnh mạch', 'chai', 'Truyền tĩnh mạch'],
            ['Gentamicin nhỏ mắt', 'Gentamicin', '0,3%', 'Dung dịch nhỏ mắt', 'lọ', 'Nhỏ mắt'],
            ['Carboxymethylcellulose nhỏ mắt', 'Carboxymethylcellulose sodium', '0,5%', 'Dung dịch nhỏ mắt', 'lọ', 'Nhỏ mắt'],
            ['Betamethasone kem bôi', 'Betamethasone valerate', '0,1%', 'Kem bôi ngoài da', 'tuýp', 'Bôi ngoài da'],
            ['Loperamide', 'Loperamide hydrochloride', '2 mg', 'Viên nang', 'viên', 'Uống'],
            ['Lactulose', 'Lactulose', '10 g/15 ml', 'Dung dịch uống', 'chai', 'Uống'],
            ['Docusate sodium', 'Docusate sodium', '100 mg', 'Viên nang', 'viên', 'Uống'],
            ['Vitamin B1', 'Thiamine hydrochloride', '100 mg', 'Viên nén', 'viên', 'Uống'],
            ['Vitamin B complex', 'Vitamin B1 + B6 + B12', 'Hàm lượng phối hợp', 'Viên nén', 'viên', 'Uống'],
            ['Magnesium sulfate', 'Magnesium sulfate', '50%', 'Dung dịch tiêm', 'ống', 'Tiêm'],
            ['Dexamethasone', 'Dexamethasone', '4 mg/ml', 'Dung dịch tiêm', 'ống', 'Tiêm'],
        ];
    }
}
