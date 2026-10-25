<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ServiceResultSeeder extends Seeder
{
    public function run(): void
    {
        $results = [
            [
                'service_order_code' => 'CD000001',
                'service_code' => 'DVCD002',
                'result_summary' => 'Niêm mạc xoang hàm dày nhẹ',
                'result_conclusion' => 'Hình ảnh phù hợp viêm xoang',
                'verified_by_business_code' => 'BS000001',
                'resulted_at' => '2026-10-15 08:51:00',
            ],
            [
                'service_order_code' => 'CD000001',
                'service_code' => 'DVTDCN002',
                'result_summary' => 'Niêm mạc mũi phù nề, dịch khe giữa',
                'result_conclusion' => 'Viêm mũi xoang',
                'verified_by_business_code' => 'BS000001',
                'resulted_at' => '2026-10-15 08:59:00',
            ],
            [
                'service_order_code' => 'CD000002',
                'service_code' => 'DVTDCN001',
                'result_summary' => 'Nhịp xoang đều',
                'result_conclusion' => 'Chưa ghi nhận bất thường cấp',
                'verified_by_business_code' => 'BS000002',
                'resulted_at' => '2026-10-15 08:51:00',
            ],
            [
                'service_order_code' => 'CD000002',
                'service_code' => 'DVXN003',
                'result_summary' => 'Đường huyết trong giới hạn tham chiếu',
                'result_conclusion' => 'Chưa ghi nhận tăng đường huyết',
                'verified_by_business_code' => 'BS000002',
                'resulted_at' => '2026-10-15 08:56:00',
            ],
            [
                'service_order_code' => 'CD000003',
                'service_code' => 'DVCD003',
                'result_summary' => 'Chưa thấy bất thường rõ trên siêu âm ổ bụng',
                'result_conclusion' => 'Chưa phát hiện tổn thương đáng chú ý',
                'verified_by_business_code' => 'BS000003',
                'resulted_at' => '2026-10-15 08:48:00',
            ],
        ];

        foreach ($results as $result) {
            $serviceOrderItemId = DB::table('service_order_items as soi')
                ->join(
                    'service_orders as so',
                    'so.id',
                    '=',
                    'soi.service_order_id'
                )
                ->join(
                    'medical_services as ms',
                    'ms.id',
                    '=',
                    'soi.medical_service_id'
                )
                ->where(
                    'so.service_order_code',
                    $result['service_order_code']
                )
                ->where(
                    'ms.service_code',
                    $result['service_code']
                )
                ->value('soi.id');

            $verifiedByUserId = DB::table('users')
                ->where(
                    'business_code',
                    $result['verified_by_business_code']
                )
                ->value('id');

            if (! $serviceOrderItemId || ! $verifiedByUserId) {
                throw new RuntimeException(
                    'Thiếu dữ liệu FK cho kết quả '.
                        $result['service_order_code'].' / '.
                        $result['service_code']
                );
            }

            DB::table('service_results')->updateOrInsert(
                [
                    'service_order_item_id' => $serviceOrderItemId,
                ],
                [
                    'result_summary' => $result['result_summary'],
                    'result_conclusion' => $result['result_conclusion'],
                    'result_parameters' => null,
                    'result_pdf_path' => null,
                    'result_image_path' => null,
                    'verified_by_user_id' => $verifiedByUserId,
                    'resulted_at' => $result['resulted_at'],
                    'notes' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
