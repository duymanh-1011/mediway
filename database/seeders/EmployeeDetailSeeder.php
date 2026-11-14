<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmployeeDetailSeeder extends Seeder
{
    public function run(): void
    {
        $details = [
            [
                'code' => 'NV000001',
                'department' => 'Lễ tân',
                'position' => 'Nhân viên tiếp đón',
                'type' => 'RECEPTIONIST',
                'current_address' => 'Số 45 đường Nghĩa Tân, phường Nghĩa Đô, Hà Nội',
                'permanent_address' => 'Số 657 đường Lạc Long Quân, phường Tây Hồ, Hà Nội',
                'qualification' => 'Quản trị văn phòng',
            ],
            [
                'code' => 'NV000002',
                'department' => 'Dịch vụ y tế',
                'position' => 'Kỹ thuật viên dịch vụ y tế',
                'type' => 'SERVICE_TECHNICIAN',
                'current_address' => 'Số 334 đường Nguyễn Trãi, phường Thanh Xuân, Hà Nội',
                'permanent_address' => 'Số 182 đường Lương Thế Vinh, phường Thanh Xuân, Hà Nội',
                'qualification' => 'Kỹ thuật xét nghiệm y học',
            ],
            [
                'code' => 'NV000003',
                'department' => 'Dịch vụ y tế',
                'position' => 'Kỹ thuật viên dịch vụ y tế',
                'type' => 'SERVICE_TECHNICIAN',
                'current_address' => 'Số 16 phố Hàng Chuối, phường Hai Bà Trưng, Hà Nội',
                'permanent_address' => 'Số 19 phố Lê Thánh Tông, phường Hoàn Kiếm, Hà Nội',
                'qualification' => 'Kỹ thuật hình ảnh y học',
            ],
            [
                'code' => 'NV000004',
                'department' => 'Thu ngân',
                'position' => 'Nhân viên thu ngân',
                'type' => 'CASHIER',
                'current_address' => 'Số 231 đường Nguyễn Ngọc Vũ, phường Yên Hòa, Hà Nội',
                'permanent_address' => 'Số 1 phố Hoàng Đạo Thúy, phường Yên Hòa, Hà Nội',
                'qualification' => 'Kế toán',
            ],
        ];

        foreach ($details as $detail) {
            $employee = DB::table('employees')
                ->where('employee_code', $detail['code'])
                ->first(['id', 'full_name']);

            DB::table('employee_details')->updateOrInsert(
                ['employee_id' => $employee->id],
                [
                    ...SeededContact::forProfile($employee->full_name, $detail['current_address'], $detail['permanent_address']),
                    'citizen_id_number' => null,
                    'citizen_id_issue_date' => null,
                    'citizen_id_issue_place' => null,
                    'nationality' => 'Việt Nam',
                    'ethnicity' => 'Kinh',

                    'education_level' => 'Đại học',
                    'professional_qualification' => $detail['qualification'],

                    'department' => $detail['department'],
                    'position' => $detail['position'],
                    'employee_type' => $detail['type'],

                    'hire_date' => '2023-01-01',
                    'termination_date' => null,
                    'years_of_service' => null,
                    'primary_shift' => null,
                    'work_location' => 'Mediway Demo Hospital',

                    'job_description' => null,
                    'professional_skills' => null,

                    'avatar_path' => null,
                    'citizen_id_front_path' => null,
                    'citizen_id_back_path' => null,
                    'profile_pdf_path' => null,

                    'notes' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
