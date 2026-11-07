<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PatientDetailSeeder extends Seeder
{
    public function run(bool $includePracticeData = false): void
    {
        $details = [
            ['code' => 'BN000001', 'cccd' => '001090000001', 'email' => 'patient1@mediway.test', 'blood' => 'O+',  'contact' => 'Người thân BN01', 'phone' => '0915000001'],
            ['code' => 'BN000002', 'cccd' => '001090000002', 'email' => 'patient2@mediway.test', 'blood' => 'A+',  'contact' => 'Người thân BN02', 'phone' => '0915000002'],
            ['code' => 'BN000003', 'cccd' => '001090000003', 'email' => 'patient3@mediway.test', 'blood' => 'B+',  'contact' => 'Người thân BN03', 'phone' => '0915000003'],
            ['code' => 'BN000004', 'cccd' => '001090000004', 'email' => 'patient4@mediway.test', 'blood' => 'O+',  'contact' => 'Người thân BN04', 'phone' => '0915000004'],
            ['code' => 'BN000005', 'cccd' => '001090000005', 'email' => 'patient5@mediway.test', 'blood' => 'AB+', 'contact' => 'Người thân BN05', 'phone' => '0915000005'],
        ];

        if ($includePracticeData) {
            $details = [...$details,
                ['code' => 'BN000006', 'cccd' => '001090000006', 'email' => 'patient6@mediway.test', 'blood' => 'A-', 'contact' => 'Người thân BN06', 'phone' => '0915000006'],
                ['code' => 'BN000007', 'cccd' => '001090000007', 'email' => 'patient7@mediway.test', 'blood' => 'O+', 'contact' => 'Người thân BN07', 'phone' => '0915000007'],
                ['code' => 'BN000008', 'cccd' => '001090000008', 'email' => 'patient8@mediway.test', 'blood' => 'AB+', 'contact' => 'Người thân BN08', 'phone' => '0915000008'],
                ['code' => 'BN000009', 'cccd' => '001090000009', 'email' => 'patient9@mediway.test', 'blood' => 'B+', 'contact' => 'Người thân BN09', 'phone' => '0915000009'],
                ['code' => 'BN000010', 'cccd' => '001090000010', 'email' => 'patient10@mediway.test', 'blood' => 'O-', 'contact' => 'Người thân BN10', 'phone' => '0915000010'],
            ];
        }

        foreach ($details as $detail) {
            $patientId = DB::table('patients')
                ->where('patient_code', $detail['code'])
                ->value('id');

            DB::table('patient_details')->updateOrInsert(
                ['patient_id' => $patientId],
                [
                    'citizen_id_number' => $detail['cccd'],
                    'citizen_id_issue_date' => null,
                    'citizen_id_issue_place' => null,
                    'email' => $detail['email'],
                    'hometown' => null,
                    'permanent_address' => null,
                    'current_address' => null,
                    'nationality' => 'Việt Nam',
                    'ethnicity' => 'Kinh',
                    'occupation' => null,
                    'blood_type' => $detail['blood'],
                    'emergency_contact_name' => $detail['contact'],
                    'emergency_contact_phone' => $detail['phone'],
                    'emergency_contact_relationship' => 'Người thân',
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
