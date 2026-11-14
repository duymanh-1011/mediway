<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DoctorDetailSeeder extends Seeder
{
    public function run(bool $includeExtraAccounts = false): void
    {
        $details = [
            [
                'code' => 'BS000001',
                'qualification' => 'Bác sĩ chuyên khoa',
                'degree' => 'Bác sĩ CKI',
                'position' => 'Bác sĩ điều trị',
                'field' => 'Tai Mũi Họng',
                'license' => 'GPHN-BS-0001',
                'experience' => 14,
                'address' => 'Số 258 đường Võ Chí Công, phường Tây Hồ, Hà Nội',
            ],
            [
                'code' => 'BS000002',
                'qualification' => 'Bác sĩ chuyên khoa',
                'degree' => 'Bác sĩ CKI',
                'position' => 'Bác sĩ điều trị',
                'field' => 'Tim mạch',
                'license' => 'GPHN-BS-0002',
                'experience' => 13,
                'address' => 'Số 136 đường Nguyễn Ngọc Nại, phường Phương Liệt, Hà Nội',
            ],
            [
                'code' => 'BS000003',
                'qualification' => 'Bác sĩ chuyên khoa',
                'degree' => 'Bác sĩ CKI',
                'position' => 'Bác sĩ điều trị',
                'field' => 'Nội tổng quát',
                'license' => 'GPHN-BS-0003',
                'experience' => 12,
                'address' => 'Số 336 đường Nguyễn Trãi, phường Thanh Xuân, Hà Nội',
            ],
        ];

        if ($includeExtraAccounts) {
            $details = [...$details,
                [
                    'code' => 'BS000004', 'qualification' => 'Bác sĩ chuyên khoa', 'degree' => 'Bác sĩ CKI',
                    'position' => 'Bác sĩ điều trị', 'field' => 'Tiêu hóa', 'license' => 'GPHN-BS-0004', 'experience' => 11,
                    'address' => 'Số 58 đường Phú Xá, phường Phú Thượng, Hà Nội',
                ],
                [
                    'code' => 'BS000005', 'qualification' => 'Bác sĩ chuyên khoa', 'degree' => 'Bác sĩ CKI',
                    'position' => 'Bác sĩ điều trị', 'field' => 'Da liễu', 'license' => 'GPHN-BS-0005', 'experience' => 9,
                    'address' => 'Số 85 phố Dịch Vọng Hậu, phường Cầu Giấy, Hà Nội',
                ],
                [
                    'code' => 'BS000006', 'qualification' => 'Bác sĩ chuyên khoa', 'degree' => 'Bác sĩ CKI',
                    'position' => 'Bác sĩ điều trị', 'field' => 'Tai Mũi Họng', 'license' => 'GPHN-BS-0006', 'experience' => 12,
                    'address' => 'Số 525 phố Kim Mã, phường Giảng Võ, Hà Nội',
                ],
            ];
        }

        foreach ($details as $detail) {
            $doctor = DB::table('doctors')
                ->where('doctor_code', $detail['code'])
                ->first(['id', 'full_name']);

            DB::table('doctor_details')->updateOrInsert(
                ['doctor_id' => $doctor->id],
                [
                    ...SeededContact::forProfile($doctor->full_name, $detail['address']),
                    'citizen_id_number' => null,
                    'citizen_id_issue_date' => null,
                    'citizen_id_issue_place' => null,
                    'nationality' => 'Việt Nam',
                    'ethnicity' => 'Kinh',
                    'education_level' => 'Đại học',

                    'professional_qualification' => $detail['qualification'],
                    'academic_title_degree' => $detail['degree'],
                    'professional_title' => 'Bác sĩ',
                    'current_position' => $detail['position'],
                    'current_workplace' => 'Mediway Demo Hospital',

                    'work_start_date' => null,
                    'years_of_experience' => $detail['experience'],
                    'specialized_fields' => $detail['field'],

                    'professional_skills' => null,
                    'languages' => 'Tiếng Việt',

                    'practice_license_number' => $detail['license'],
                    'practice_license_issue_date' => null,
                    'practice_license_issue_place' => null,
                    'practice_license_expiry_date' => null,
                    'practice_scope' => $detail['field'],

                    'professional_bio' => null,
                    'avatar_path' => null,
                    'citizen_id_front_path' => null,
                    'citizen_id_back_path' => null,
                    'practice_license_file_path' => null,
                    'profile_pdf_path' => null,

                    'notes' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
