<?php

namespace Database\Seeders;

use App\Models\MedicalVisit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ImagingPracticeSeeder extends Seeder
{
    public function run(): void
    {
        // Dữ liệu thực hành chỉ được tạo khi chủ động bật cờ trên môi trường local/testing.
        if (! app()->environment(['local', 'testing']) || ! filter_var(env('IMAGING_PRACTICE_SEED_ENABLED', false), FILTER_VALIDATE_BOOL)) {
            throw new RuntimeException('Chỉ dùng ở môi trường local/testing và cần bật IMAGING_PRACTICE_SEED_ENABLED=true.');
        }
        if (DB::connection()->getDriverName() === 'mysql' && ! in_array(config('database.connections.mysql.host'), ['127.0.0.1', 'localhost'], true)) {
            throw new RuntimeException('Chỉ tạo dữ liệu thực hành trên MySQL cục bộ.');
        }

        DB::transaction(function (): void {
            // Chỉ tạo trên lượt khám mẫu đã có, không tạo hay sửa hồ sơ bệnh nhân.
            $visit = MedicalVisit::with(['patient', 'status', 'appointment'])->where('visit_code', 'LK000004')->lockForUpdate()->first();
            if (! $visit || $visit->patient?->patient_code !== 'BN000004' || $visit->status?->name !== 'WAITING_FOR_SERVICE') {
                throw new RuntimeException('Không tìm thấy lượt khám mẫu LK000004 của BN000004 đang chờ dịch vụ.');
            }
            $visit->assertServiceDataWritable();
            $code = 'CDHA-MAU-001';
            if (DB::table('service_orders')->where('service_order_code', $code)->exists()) {
                $this->command?->info('Bộ chỉ định mẫu đã tồn tại; không thay đổi dữ liệu đã lưu.');
                return;
            }
            $pending = DB::table('service_statuses')->where('name', 'PENDING')->where('is_active', true)->first();
            $paid = DB::table('payment_statuses')->where('name', 'PAID')->where('allows_service_execution', true)->first();
            if (! $pending || ! $paid) throw new RuntimeException('Thiếu trạng thái dịch vụ hoặc thanh toán cần thiết.');
            $services = DB::table('medical_services as m')->join('service_group_medical_service as rel', 'rel.medical_service_id', '=', 'm.id')
                ->join('service_groups as g', 'g.id', '=', 'rel.service_group_id')
                ->where('g.service_group_code', 'CDHA')->whereIn('m.service_code', ['DVCD001', 'DVCD002', 'DVCD003'])
                ->select('m.id', 'm.name', 'm.service_code', 'm.price')->get()->keyBy('service_code');
            if ($services->count() !== 3) throw new RuntimeException('Thiếu danh mục ba dịch vụ chẩn đoán hình ảnh mẫu.');

            $now = now();
            $orderId = DB::table('service_orders')->insertGetId([
                'service_order_code' => $code, 'medical_visit_id' => $visit->id,
                'ordered_by_doctor_id' => $visit->doctor_id, 'ordered_at' => $now,
                'reason' => 'DỮ LIỆU THỰC HÀNH - chỉ định hình ảnh mẫu', 'status' => 'ACTIVE',
                'created_at' => $now, 'updated_at' => $now,
            ]);
            foreach (['DVCD001', 'DVCD002', 'DVCD003'] as $index => $serviceCode) {
                $service = $services[$serviceCode];
                $suffix = str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT);
                $itemId = DB::table('service_order_items')->insertGetId([
                    'service_order_id' => $orderId, 'medical_service_id' => $service->id,
                    'service_status_id' => $pending->id, 'price_at_order' => $service->price,
                    'performed_by_employee_id' => null, 'execution_notes' => 'DỮ LIỆU THỰC HÀNH',
                    'created_at' => $now, 'updated_at' => $now,
                ]);
                // Thanh toán dưới đây là bản ghi mô phỏng, không phải giao dịch tiền thật.
                $payableId = DB::table('payables')->insertGetId([
                    'payable_code' => 'PT-CDHA-MAU-'.$suffix, 'medical_visit_id' => $visit->id,
                    'service_order_item_id' => $itemId, 'payment_type' => 'SERVICE_FEE',
                    'description' => 'THỰC HÀNH - '.$service->name, 'amount' => $service->price,
                    'payment_status_id' => $paid->id, 'generated_at' => $now, 'paid_at' => $now,
                    'notes' => 'DỮ LIỆU THỰC HÀNH - thanh toán mô phỏng', 'created_at' => $now, 'updated_at' => $now,
                ]);
                DB::table('payment_transactions')->insert([
                    'transaction_code' => 'GD-CDHA-MAU-'.$suffix, 'payable_id' => $payableId,
                    'amount' => $service->price, 'payment_method' => 'CASH', 'transaction_result' => 'SUCCESS',
                    'started_at' => $now, 'paid_at' => $now, 'confirmed_at' => $now,
                    'payment_content' => 'DỮ LIỆU THỰC HÀNH - thanh toán mô phỏng',
                    'notes' => 'Không phải giao dịch tiền thật', 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
            $this->command?->info('Đã tạo 3 chỉ định hình ảnh thực hành cho lượt khám LK000004.');
        });
    }
}
