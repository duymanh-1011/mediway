<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointment_time_slots', function (Blueprint $table): void {
            $table->unsignedSmallInteger('max_patients')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::table('appointment_time_slots')->whereNull('max_patients')->exists()) {
            throw new RuntimeException('Còn khung không giới hạn sức chứa. Dừng rollback để giữ nguyên dữ liệu khung giờ.');
        }

        Schema::table('appointment_time_slots', function (Blueprint $table): void {
            $table->unsignedSmallInteger('max_patients')->change();
        });
    }
};
