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
                'department' => 'Điều dưỡng',
                'position' => 'Điều dưỡng',
                'type' => 'NURSE',
            ],
            [
                'code' => 'NV000002',
                'department' => 'Khoa Xét nghiệm',
                'position' => 'Kỹ thuật viên xét nghiệm',
                'type' => 'LAB_TECHNICIAN',
            ],
            [
                'code' => 'NV000003',
                'department' => 'Chẩn đoán hình ảnh',
                'position' => 'Kỹ thuật viên CĐHA',
                'type' => 'IMAGING_TECHNICIAN',
            ],
            [
                'code' => 'NV000004',
                'department' => 'Thu ngân - Tiếp đón',
                'position' => 'Nhân viên thu ngân',
                'type' => 'CASHIER',
            ],
        ];

        foreach ($details as $detail) {
            $employeeId = DB::table('employees')
                ->where('employee_code', $detail['code'])
                ->value('id');

            DB::table('employee_details')->updateOrInsert(
                ['employee_id' => $employeeId],
                [
                    'citizen_id_number' => null,
                    'citizen_id_issue_date' => null,
                    'citizen_id_issue_place' => null,
                    'email' => null,
                    'hometown' => null,
                    'permanent_address' => null,
                    'current_address' => null,
                    'nationality' => 'Việt Nam',
                    'ethnicity' => 'Kinh',

                    'education_level' => null,
                    'professional_qualification' => null,

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
