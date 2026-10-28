<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DoctorDetailSeeder extends Seeder
{
    public function run(): void
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
            ],
            [
                'code' => 'BS000002',
                'qualification' => 'Bác sĩ chuyên khoa',
                'degree' => 'Bác sĩ CKI',
                'position' => 'Bác sĩ điều trị',
                'field' => 'Tim mạch',
                'license' => 'GPHN-BS-0002',
                'experience' => 13,
            ],
            [
                'code' => 'BS000003',
                'qualification' => 'Bác sĩ chuyên khoa',
                'degree' => 'Bác sĩ CKI',
                'position' => 'Bác sĩ điều trị',
                'field' => 'Nội tổng quát',
                'license' => 'GPHN-BS-0003',
                'experience' => 12,
            ],
        ];

        foreach ($details as $detail) {
            $doctorId = DB::table('doctors')
                ->where('doctor_code', $detail['code'])
                ->value('id');

            DB::table('doctor_details')->updateOrInsert(
                ['doctor_id' => $doctorId],
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

                    'professional_qualification' => $detail['qualification'],
                    'academic_title_degree' => $detail['degree'],
                    'professional_title' => null,
                    'current_position' => $detail['position'],
                    'current_workplace' => 'Mediway Demo Hospital',

                    'work_start_date' => null,
                    'years_of_experience' => $detail['experience'],
                    'specialized_fields' => $detail['field'],

                    'professional_skills' => null,
                    'languages' => null,

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
