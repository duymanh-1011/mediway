<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\ExaminationRoom;
use App\Models\MedicalVisit;
use App\Models\Patient;
use App\Models\VisitStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Tạo một lượt khám chỉ trong cơ sở SQLite demo đã đặt tên rõ ràng. */
class DoctorVisitDemoSeeder extends Seeder
{
    public function run(): void
    {
        $database = DB::connection()->getDatabaseName();
        $demoPath = storage_path('app/huy-doctor-demo.sqlite');
        if (DB::getDriverName() !== 'sqlite' || ! is_file($demoPath)
            || realpath($database) !== realpath($demoPath)) {
            throw new \RuntimeException('Chỉ được tạo dữ liệu mẫu trong storage/app/huy-doctor-demo.sqlite.');
        }

        // Dùng hồ sơ mẫu đã có, tuyệt đối không cập nhật tài khoản hay mật khẩu.
        $doctor = Doctor::where('doctor_code', 'BS000001')->where('status', 'ACTIVE')->firstOrFail();
        $patient = Patient::where('patient_code', 'BN000001')->where('status', 'ACTIVE')->firstOrFail();
        $room = ExaminationRoom::where('room_code', 'PKTMH01')->where('status', 'ACTIVE')->firstOrFail();
        $status = VisitStatus::where('name', 'IN_EXAMINATION')->where('is_active', true)->firstOrFail();
        if (! $doctor->specialty_id || (int) $room->specialty_id !== (int) $doctor->specialty_id) {
            throw new \RuntimeException('Bác sĩ và phòng mẫu phải thuộc cùng chuyên khoa.');
        }

        // Chạy lại cùng ngày không ghi đè hồ sơ đã nhập.
        $code = 'LKHUYDEMO'.today()->format('Ymd');
        $visit = MedicalVisit::firstOrCreate(['visit_code' => $code], [
            'appointment_id' => null, 'patient_id' => $patient->id, 'doctor_id' => $doctor->id,
            'specialty_id' => $doctor->specialty_id, 'examination_room_id' => $room->id,
            'visit_status_id' => $status->id, 'checked_in_at' => now(), 'examination_started_at' => now(),
            'reception_source' => 'WALK_IN', 'reception_notes' => 'Dữ liệu mẫu riêng để thử màn hình bác sĩ.',
        ]);
        $this->command?->info('Lượt khám mẫu: '.$visit->visit_code.' · '.$patient->full_name.' · '.$doctor->full_name);
    }
}
