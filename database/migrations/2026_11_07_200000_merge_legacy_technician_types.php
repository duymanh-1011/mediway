<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const LEGACY_TYPES = [
        ['code' => 'LAB_TECHNICIAN', 'name' => 'Kỹ thuật viên xét nghiệm', 'display_order' => 3],
        ['code' => 'IMAGING_TECHNICIAN', 'name' => 'Kỹ thuật viên chẩn đoán hình ảnh', 'display_order' => 4],
    ];

    public function up(): void
    {
        DB::transaction(function (): void {
            $details = DB::table('employee_details');
            $legacyCodes = array_column(self::LEGACY_TYPES, 'code');
            $legacyPositions = [...array_column(self::LEGACY_TYPES, 'name'), 'Kỹ thuật viên CĐHA'];

            (clone $details)->whereIn('employee_type', $legacyCodes)->update(['employee_type' => 'SERVICE_TECHNICIAN']);
            (clone $details)->where('employee_type', 'SERVICE_TECHNICIAN')->whereIn('position', $legacyPositions)
                ->update(['position' => 'Kỹ thuật viên dịch vụ y tế']);

            foreach (['NV000002' => 'Khoa Xét nghiệm', 'NV000003' => 'Chẩn đoán hình ảnh'] as $code => $previousDepartment) {
                (clone $details)->where('employee_type', 'SERVICE_TECHNICIAN')
                    ->where('employee_id', DB::table('employees')->select('id')->where('employee_code', $code))
                    ->where('department', $previousDepartment)
                    ->update(['department' => 'Dịch vụ y tế']);
            }

            DB::table('employee_types')->whereIn('code', $legacyCodes)->delete();
        });
    }

    public function down(): void
    {
        // Khôi phục danh mục cũ và giữ loại chung cho các hồ sơ đã gộp.
        DB::table('employee_types')->insertOrIgnore(self::LEGACY_TYPES);
    }
};
