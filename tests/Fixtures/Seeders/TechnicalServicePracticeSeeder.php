<?php

namespace Tests\Fixtures\Seeders;

use App\Models\Doctor;
use App\Models\Employee;
use App\Models\ExaminationRoom;
use App\Models\MedicalService;
use App\Models\MedicalVisit;
use App\Models\Patient;
use App\Models\Payable;
use App\Models\PaymentStatus;
use App\Models\PaymentTransaction;
use App\Models\ServiceOrder;
use App\Models\ServiceStatus;
use App\Models\VisitStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TechnicalServicePracticeSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $cases = [
            ['01', 'BN000006', 'BS000004', 'PKTH01', [
                ['DVNS001', 'PENDING', true, null, null],
                ['DVCD003', 'PENDING', true, null, null],
                ['DVXN004', 'PENDING', false, null, null],
            ]],
            ['02', 'BN000007', 'BS000006', 'PKTMH01', [
                ['DVTDCN002', 'PROCESSING', true, 'NV000002', null],
                ['DVCD004', 'PROCESSING', true, 'NV000003', 'Đã lưu mô tả kỹ thuật mẫu.'],
                ['DVTT004', 'COMPLETED', true, 'NV000002', 'Đã ghi nhận kết quả thực hiện mẫu.'],
            ]],
            ['03', 'BN000008', 'BS000003', 'PKNOI01', [
                ['DVPHCN001', 'PENDING', true, null, null],
                ['DVCSSK001', 'PENDING', true, 'NV000003', null],
                ['DVTDCN003', 'PENDING', true, null, null],
            ]],
        ];

        foreach ($cases as [$suffix, $patientCode, $doctorCode, $roomCode, $services]) {
            DB::transaction(function () use ($suffix, $patientCode, $doctorCode, $roomCode, $services): void {
                if (ServiceOrder::where('service_order_code', 'CD-MAUDV-'.$suffix)->exists()) {
                    return;
                }

                $now = now();
                $doctor = Doctor::where('doctor_code', $doctorCode)->firstOrFail();
                $visit = MedicalVisit::create([
                    'visit_code' => 'LK-MAUDV-'.$suffix,
                    'patient_id' => Patient::where('patient_code', $patientCode)->firstOrFail()->id,
                    'doctor_id' => $doctor->id,
                    'specialty_id' => $doctor->specialty_id,
                    'examination_room_id' => ExaminationRoom::where('room_code', $roomCode)->firstOrFail()->id,
                    'visit_status_id' => VisitStatus::where('name', 'WAITING_FOR_SERVICE')->firstOrFail()->id,
                    'checked_in_at' => $now->copy()->subMinutes(45),
                    'examination_started_at' => $now->copy()->subMinutes(30),
                    'reception_source' => 'WALK_IN',
                ]);
                $order = ServiceOrder::create([
                    'service_order_code' => 'CD-MAUDV-'.$suffix,
                    'medical_visit_id' => $visit->id,
                    'ordered_by_doctor_id' => $doctor->id,
                    'ordered_at' => $now->copy()->subMinutes(20),
                    'status' => 'ACTIVE',
                ]);
                $this->seedCharge($visit, $suffix.'-00', 'EXAMINATION_FEE', 'Phí khám bệnh', config('patient_appointments.examination_fee'), true, $now->copy()->subMinutes(40));

                foreach ($services as $index => [$code, $state, $paid, $performer, $summary]) {
                    $service = MedicalService::where('service_code', $code)->firstOrFail();
                    $item = $order->items()->create([
                        'medical_service_id' => $service->id,
                        'service_status_id' => ServiceStatus::where('name', $state)->firstOrFail()->id,
                        'price_at_order' => $service->price,
                        'performed_by_employee_id' => $performer ? Employee::where('employee_code', $performer)->firstOrFail()->id : null,
                        'started_at' => $state !== 'PENDING' ? $now->copy()->subMinutes(10) : null,
                        'completed_at' => $state === 'COMPLETED' ? $now->copy()->subMinutes(2) : null,
                    ]);
                    $chargeSuffix = $suffix.'-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
                    $this->seedCharge($visit, $chargeSuffix, 'SERVICE_FEE', 'Phí dịch vụ '.$service->name, $service->price, $paid, $now->copy()->subMinutes(15), $item->id);
                    if ($summary !== null) {
                        $item->result()->create(['result_summary' => $summary, 'resulted_at' => $now->copy()->subMinutes(3)]);
                    }
                }
            });
        }
    }

    private function seedCharge(
        MedicalVisit $visit,
        string $suffix,
        string $type,
        string $description,
        int|string $amount,
        bool $paid,
        CarbonInterface $at,
        ?int $itemId = null,
    ): void {
        $payable = Payable::create([
            'payable_code' => 'PT-MAUDV-'.$suffix,
            'medical_visit_id' => $visit->id,
            'service_order_item_id' => $itemId,
            'payment_type' => $type,
            'description' => $description,
            'amount' => $amount,
            'payment_status_id' => PaymentStatus::where('name', $paid ? 'PAID' : 'UNPAID')->firstOrFail()->id,
            'generated_at' => $at,
            'paid_at' => $paid ? $at : null,
        ]);
        if ($paid) {
            PaymentTransaction::create([
                'transaction_code' => 'GD-MAUDV-'.$suffix,
                'payable_id' => $payable->id,
                'amount' => $amount,
                'payment_method' => 'CASH',
                'transaction_result' => 'SUCCESS',
                'started_at' => $at,
                'paid_at' => $at,
                'confirmed_at' => $at,
                'collected_by_employee_id' => Employee::where('employee_code', 'NV000004')->firstOrFail()->id,
            ]);
        }
    }
}
