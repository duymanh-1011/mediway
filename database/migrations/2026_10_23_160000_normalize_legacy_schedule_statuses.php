<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::table('doctor_schedules')->where('status', 'OPEN')->update(['status' => 'ACTIVE']);
            DB::table('appointment_time_slots')->where('status', 'AVAILABLE')->update(['status' => 'ACTIVE']);
        });
    }

    public function down(): void
    {
        // Do not turn new ACTIVE records back into obsolete codes on rollback.
    }
};
