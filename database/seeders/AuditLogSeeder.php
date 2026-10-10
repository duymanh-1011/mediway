<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AuditLogSeeder extends Seeder
{
    public function run(): void
    {
        $logs = [
            [
                'user_business_code' => 'BN000001',
                'role_name_snapshot' => 'Patient',
                'action' => 'CREATE',
                'entity_type' => 'appointments',
                'entity_business_code' => 'LH000001',
                'description' => 'Bệnh nhân tạo lịch hẹn',
                'performed_at' => '2026-10-10 09:00:00',
            ],
            [
                'user_business_code' => 'BN000002',
                'role_name_snapshot' => 'Patient',
                'action' => 'CREATE',
                'entity_type' => 'appointments',
                'entity_business_code' => 'LH000002',
                'description' => 'Bệnh nhân tạo lịch hẹn',
                'performed_at' => '2026-10-10 09:10:00',
            ],
            [
                'user_business_code' => null,
                'role_name_snapshot' => null,
                'action' => 'STATUS_CHANGE',
                'entity_type' => 'appointments',
                'entity_business_code' => 'LH000001',
                'description' => 'Kiosk xác nhận check-in',
                'performed_at' => '2026-10-15 07:50:00',
            ],
            [
                'user_business_code' => 'BS000001',
                'role_name_snapshot' => 'Doctor',
                'action' => 'CREATE',
                'entity_type' => 'service_orders',
                'entity_business_code' => 'CD000001',
                'description' => 'Bác sĩ tạo phiếu chỉ định',
                'performed_at' => '2026-10-15 08:26:00',
            ],
            [
                'user_business_code' => 'BS000002',
                'role_name_snapshot' => 'Doctor',
                'action' => 'CREATE',
                'entity_type' => 'service_orders',
                'entity_business_code' => 'CD000002',
                'description' => 'Bác sĩ tạo phiếu chỉ định',
                'performed_at' => '2026-10-15 08:29:00',
            ],
            [
                'user_business_code' => 'BS000003',
                'role_name_snapshot' => 'Doctor',
                'action' => 'CREATE',
                'entity_type' => 'prescriptions',
                'entity_business_code' => 'DT000003',
                'description' => 'Bác sĩ kê đơn thuốc',
                'performed_at' => '2026-10-15 08:47:00',
            ],
            [
                'user_business_code' => 'NV000003',
                'role_name_snapshot' => 'Employee',
                'action' => 'UPDATE',
                'entity_type' => 'service_results',
                'entity_business_code' => 'CD000003/DVCD003',
                'description' => 'Cập nhật kết quả siêu âm',
                'performed_at' => '2026-10-15 08:48:00',
            ],
            [
                'user_business_code' => 'NV000004',
                'role_name_snapshot' => 'Employee',
                'action' => 'STATUS_CHANGE',
                'entity_type' => 'payables',
                'entity_business_code' => 'PT000003',
                'description' => 'Xác nhận thanh toán tại quầy',
                'performed_at' => '2026-10-15 08:55:00',
            ],
            [
                'user_business_code' => 'BS000001',
                'role_name_snapshot' => 'Doctor',
                'action' => 'CREATE',
                'entity_type' => 'visit_conclusions',
                'entity_business_code' => 'LK000001',
                'description' => 'Hoàn tất kết luận lượt khám',
                'performed_at' => '2026-10-15 09:20:00',
            ],
            [
                'user_business_code' => 'QT000001',
                'role_name_snapshot' => 'Administrator',
                'action' => 'LOGIN',
                'entity_type' => 'users',
                'entity_business_code' => 'QT000001',
                'description' => 'Quản trị viên đăng nhập hệ thống',
                'performed_at' => '2026-10-15 10:00:00',
            ],
        ];

        foreach ($logs as $log) {
            $userId = null;

            if ($log['user_business_code'] !== null) {
                $userId = DB::table('users')
                    ->where(
                        'business_code',
                        $log['user_business_code']
                    )
                    ->value('id');

                if (!$userId) {
                    throw new RuntimeException(
                        'Không tìm thấy user ' .
                            $log['user_business_code']
                    );
                }
            }

            $entityId = $this->resolveEntityId(
                $log['entity_type'],
                $log['entity_business_code']
            );

            DB::table('audit_logs')->updateOrInsert(
                [
                    'entity_type' => $log['entity_type'],
                    'entity_business_code' => $log['entity_business_code'],
                    'performed_at' => $log['performed_at'],
                ],
                [
                    'user_id' => $userId,
                    'role_name_snapshot' => $log['role_name_snapshot'],
                    'action' => $log['action'],
                    'entity_id' => $entityId,
                    'old_values' => null,
                    'new_values' => null,
                    'description' => $log['description'],
                    'ip_address' => null,
                    'user_agent' => null,
                    'created_at' => now(),
                ]
            );
        }
    }

    private function resolveEntityId(
        string $entityType,
        string $entityBusinessCode
    ): int {
        $id = match ($entityType) {
            'appointments' => DB::table('appointments')
                ->where('appointment_code', $entityBusinessCode)
                ->value('id'),

            'service_orders' => DB::table('service_orders')
                ->where('service_order_code', $entityBusinessCode)
                ->value('id'),

            'prescriptions' => DB::table('prescriptions')
                ->where('prescription_code', $entityBusinessCode)
                ->value('id'),

            'payables' => DB::table('payables')
                ->where('payable_code', $entityBusinessCode)
                ->value('id'),

            'users' => DB::table('users')
                ->where('business_code', $entityBusinessCode)
                ->value('id'),

            'service_results' => $this->findServiceResultId(
                $entityBusinessCode
            ),

            'visit_conclusions' => $this->findVisitConclusionId(
                $entityBusinessCode
            ),

            default => null,
        };

        if (!$id) {
            throw new RuntimeException(
                'Không tìm thấy entity ' .
                    $entityType . ' / ' .
                    $entityBusinessCode
            );
        }

        return (int) $id;
    }

    private function findServiceResultId(
        string $entityBusinessCode
    ): ?int {
        [$serviceOrderCode, $serviceCode] = explode(
            '/',
            $entityBusinessCode
        );

        $id = DB::table('service_results as sr')
            ->join(
                'service_order_items as soi',
                'soi.id',
                '=',
                'sr.service_order_item_id'
            )
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
                $serviceOrderCode
            )
            ->where(
                'ms.service_code',
                $serviceCode
            )
            ->value('sr.id');

        return $id ? (int) $id : null;
    }

    private function findVisitConclusionId(
        string $visitCode
    ): ?int {
        $id = DB::table('visit_conclusions as vc')
            ->join(
                'medical_visits as mv',
                'mv.id',
                '=',
                'vc.medical_visit_id'
            )
            ->where('mv.visit_code', $visitCode)
            ->value('vc.id');

        return $id ? (int) $id : null;
    }
}
