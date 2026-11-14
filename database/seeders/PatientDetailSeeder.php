<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PatientDetailSeeder extends Seeder
{
    public function run(bool $includeExtraAccounts = false): void
    {
        $details = [
            ['BN000001', '001090000001', 'O+', 'Nguyễn Văn Hải', '0915000001', 'Cha', 'Số 19 phố Lê Thánh Tông, phường Hoàn Kiếm, Hà Nội', 'Nhân viên văn phòng'],
            ['BN000002', '001090000002', 'A+', 'Phạm Đức Toàn', '0915000002', 'Chồng', 'Số 6 đường Minh Tảo, phường Xuân Đỉnh, Hà Nội', 'Giáo viên'],
            ['BN000003', '001090000003', 'B+', 'Lê Hoàng Sơn', '0915000003', 'Con trai', 'Số 2 phố Trúc Bạch, phường Ba Đình, Hà Nội', 'Kỹ sư'],
            ['BN000004', '001090000004', 'O+', 'Vũ Thị Hạnh', '0915000004', 'Mẹ', 'Số 144 đường Xuân Thủy, phường Cầu Giấy, Hà Nội', 'Nhân viên thiết kế'],
            ['BN000005', '001090000005', 'AB+', 'Đỗ Quang Minh', '0915000005', 'Anh trai', 'Số 182 đường Lương Thế Vinh, phường Thanh Xuân, Hà Nội', 'Kinh doanh'],
        ];

        if ($includeExtraAccounts) {
            $details = [...$details,
                ['BN000006', '001090000006', 'A-', 'Hoàng Bảo Chi', '0915000006', 'Chị gái', 'Số 25 phố Liễu Giai, phường Ngọc Hà, Hà Nội', 'Kế toán'],
                ['BN000007', '001090000007', 'O+', 'Đinh Thị Vân', '0915000007', 'Vợ', 'Số 657 đường Lạc Long Quân, phường Tây Hồ, Hà Nội', 'Hưu trí'],
                ['BN000008', '001090000008', 'AB+', 'Võ Minh Phúc', '0915000008', 'Cha', 'Số 16 phố Hàng Chuối, phường Hai Bà Trưng, Hà Nội', 'Sinh viên'],
                ['BN000009', '001090000009', 'B+', 'Lê Thu Hiền', '0915000009', 'Mẹ', 'Số 1 phố Hoàng Đạo Thúy, phường Yên Hòa, Hà Nội', 'Nhân viên tư vấn'],
                ['BN000010', '001090000010', 'O-', 'Trần Thanh Hòa', '0915000010', 'Vợ', 'Số 136 đường Nguyễn Ngọc Nại, phường Phương Liệt, Hà Nội', 'Thợ điện'],
            ];
        }

        foreach ($details as [$code, $citizenId, $blood, $contact, $phone, $relationship, $address, $occupation]) {
            $patient = DB::table('patients')
                ->where('patient_code', $code)
                ->first(['id', 'full_name']);

            DB::table('patient_details')->updateOrInsert(
                ['patient_id' => $patient->id],
                [
                    ...SeededContact::forProfile($patient->full_name, $address),
                    'citizen_id_number' => $citizenId,
                    'citizen_id_issue_date' => null,
                    'citizen_id_issue_place' => null,
                    'nationality' => 'Việt Nam',
                    'ethnicity' => 'Kinh',
                    'occupation' => $occupation,
                    'blood_type' => $blood,
                    'emergency_contact_name' => $contact,
                    'emergency_contact_phone' => $phone,
                    'emergency_contact_relationship' => $relationship,
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
