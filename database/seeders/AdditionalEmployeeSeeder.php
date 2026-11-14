<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use App\Support\EmployeeAccess;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdditionalEmployeeSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $employees = [
            ['NV000005', '0904000005', 'Phạm Ngọc Trâm', '1998-03-24', 'Nữ', 'RECEPTIONIST', 'Lễ tân', 'Quản trị văn phòng', 'Số 58 đường Phú Xá, phường Phú Thượng, Hà Nội'],
            ['NV000006', '0904000006', 'Lê Minh Tú', '1995-09-10', 'Nam', 'CASHIER', 'Thu ngân', 'Kế toán', 'Số 2 phố Trúc Bạch, phường Ba Đình, Hà Nội'],
            ['NV000007', '0904000007', 'Trần Hải Yến', '1997-12-05', 'Nữ', EmployeeAccess::SERVICE_TECHNICIAN, 'Dịch vụ y tế', 'Kỹ thuật hình ảnh y học', 'Số 144 đường Xuân Thủy, phường Cầu Giấy, Hà Nội'],
            ['NV000008', '0904000008', 'Nguyễn Quốc Bảo', '1992-05-27', 'Nam', EmployeeAccess::SERVICE_TECHNICIAN, 'Dịch vụ y tế', 'Kỹ thuật xét nghiệm y học', 'Số 525 phố Kim Mã, phường Giảng Võ, Hà Nội'],
        ];

        $roleId = Role::where('name', 'Employee')->where('status', 'ACTIVE')->firstOrFail()->id;

        DB::transaction(function () use ($employees, $roleId): void {
            foreach ($employees as [$code, $phone, $name, $dob, $gender, $type, $department, $qualification, $address]) {
                // Dành nguyên mã và số điện thoại cho hồ sơ nào đã sử dụng chúng.
                if (User::where('business_code', $code)->orWhere('phone', $phone)->exists()
                    || Employee::where('employee_code', $code)->exists()) {
                    continue;
                }

                $user = User::create([
                    'business_code' => $code,
                    'phone' => $phone,
                    'password' => 'Mediway@123',
                    'role_id' => $roleId,
                    'status' => 'ACTIVE',
                ]);
                $employee = Employee::create([
                    'employee_code' => $code,
                    'user_id' => $user->id,
                    'full_name' => $name,
                    'date_of_birth' => $dob,
                    'gender' => $gender,
                    'status' => 'ACTIVE',
                ]);
                $employee->detail()->create([
                    ...SeededContact::forProfile($name, $address),
                    'nationality' => 'Việt Nam',
                    'ethnicity' => 'Kinh',
                    'education_level' => 'Đại học',
                    'professional_qualification' => $qualification,
                    'hire_date' => '2024-01-15',
                    'employee_type' => $type,
                    'position' => EmployeeAccess::TYPES[$type],
                    'department' => $department,
                    'work_location' => 'Mediway Demo Hospital',
                ]);
            }
        }, 3);
    }
}
