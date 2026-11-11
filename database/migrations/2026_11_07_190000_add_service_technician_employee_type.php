<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('employee_types')->insertOrIgnore([
            'code' => 'SERVICE_TECHNICIAN',
            'name' => 'Kỹ thuật viên dịch vụ y tế',
            'display_order' => 6,
        ]);
    }

    public function down(): void
    {
        // The merge preserves assigned technicians on rollback, so keep their catalog entry.
        DB::table('employee_types')->where('code', 'SERVICE_TECHNICIAN')
            ->whereNotExists(function ($details): void {
                $details->selectRaw('1')->from('employee_details')
                    ->whereColumn('employee_details.employee_type', 'employee_types.code');
            })
            ->delete();
    }
};
