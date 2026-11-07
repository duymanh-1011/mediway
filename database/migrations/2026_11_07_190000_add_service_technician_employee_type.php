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
        // The existing foreign key prevents deleting a type already assigned to employees.
        DB::table('employee_types')->where('code', 'SERVICE_TECHNICIAN')->delete();
    }
};
