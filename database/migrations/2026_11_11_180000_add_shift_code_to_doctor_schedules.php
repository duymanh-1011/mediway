<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SHIFT_INDEX = 'doctor_schedules_doctor_date_shift_unique';

    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // Date first keeps this index from replacing the index for the doctor_id foreign key.
            DB::statement(
                "ALTER TABLE doctor_schedules ADD COLUMN shift_code ENUM('MORNING', 'AFTERNOON') NULL, "
                .'ADD UNIQUE INDEX '.self::SHIFT_INDEX.' (work_date, doctor_id, shift_code)'
            );

            return;
        }

        Schema::table('doctor_schedules', function (Blueprint $table): void {
            $table->enum('shift_code', ['MORNING', 'AFTERNOON'])->nullable();
            $table->unique(['work_date', 'doctor_id', 'shift_code'], self::SHIFT_INDEX);
        });
    }

    public function down(): void
    {
        if (DB::table('doctor_schedules')->whereNotNull('shift_code')->exists()) {
            throw new RuntimeException('Còn lịch đã được gắn mã ca chuẩn. Dừng rollback để giữ thông tin phân ca.');
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE doctor_schedules DROP INDEX '.self::SHIFT_INDEX.', DROP COLUMN shift_code');

            return;
        }

        Schema::table('doctor_schedules', function (Blueprint $table): void {
            $table->dropUnique(self::SHIFT_INDEX);
            $table->dropColumn('shift_code');
        });
    }
};
