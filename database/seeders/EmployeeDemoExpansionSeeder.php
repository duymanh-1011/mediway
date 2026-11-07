<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use App\Support\EmployeeAccess;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmployeeDemoExpansionSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $employees = [
            ['NV000005', '0904000005', 'Phạm Ngọc Trâm', 'RECEPTIONIST', 'Lễ tân'],
            ['NV000006', '0904000006', 'Lê Minh Tú', 'CASHIER', 'Thu ngân'],
            ['NV000007', '0904000007', 'Trần Hải Yến', EmployeeAccess::SERVICE_TECHNICIAN, 'Dịch vụ y tế'],
            ['NV000008', '0904000008', 'Nguyễn Quốc Bảo', EmployeeAccess::SERVICE_TECHNICIAN, 'Dịch vụ y tế'],
        ];

        $roleId = Role::where('name', 'Employee')->where('status', 'ACTIVE')->firstOrFail()->id;

        DB::transaction(function () use ($employees, $roleId): void {
            foreach ($employees as [$code, $phone, $name, $type, $department]) {
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
                    'status' => 'ACTIVE',
                ]);
                $employee->detail()->create([
                    'employee_type' => $type,
                    'position' => EmployeeAccess::TYPES[$type],
                    'department' => $department,
                    'work_location' => 'Mediway Demo Hospital',
                ]);
            }
        }, 3);
    }
}
