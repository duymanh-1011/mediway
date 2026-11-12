<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CONSTRAINT = 'doctor_schedules_shift_assignment_check';

    private const LEGACY_CONSTRAINT = 'doctor_schedules_legacy_assignment_check';

    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            $previouslyRolledBack = DB::table('information_schema.table_constraints')
                ->where('constraint_schema', DB::getDatabaseName())
                ->where('table_name', 'doctor_schedules')
                ->where('constraint_name', self::LEGACY_CONSTRAINT)
                ->where('constraint_type', 'CHECK')
                ->exists();
            $change = $previouslyRolledBack
                ? 'DROP CHECK '.self::LEGACY_CONSTRAINT.', '
                : 'MODIFY specialty_id BIGINT UNSIGNED NULL, MODIFY examination_room_id BIGINT UNSIGNED NULL, ';
            DB::statement(
                'ALTER TABLE doctor_schedules '.$change
                .'ADD CONSTRAINT '.self::CONSTRAINT.' CHECK ('.$this->validAssignment().')'
            );

            return;
        }

        $this->changeSqliteColumns(nullable: true);
        foreach (['insert', 'update'] as $action) {
            DB::unprepared(
                'CREATE TRIGGER '.self::CONSTRAINT.'_'.$action." BEFORE $action ON doctor_schedules "
                .'WHEN NOT ('.$this->validAssignment('NEW.').') '
                ."BEGIN SELECT RAISE(ABORT, '".self::CONSTRAINT."'); END"
            );
        }
    }

    public function down(): void
    {
        if (DB::table('doctor_schedules')->whereNull('specialty_id')->orWhereNull('examination_room_id')->exists()) {
            throw new RuntimeException('Còn ca nghỉ chưa phân công. Dừng rollback để giữ nguyên lịch bác sĩ.');
        }

        if (DB::getDriverName() === 'mysql') {
            // MySQL cannot tighten the physical nullability while preserving these foreign keys.
            // A checked constraint restores the original write rules in one ALTER.
            DB::statement(
                'ALTER TABLE doctor_schedules DROP CHECK '.self::CONSTRAINT.', '
                .'ADD CONSTRAINT '.self::LEGACY_CONSTRAINT.' CHECK (specialty_id IS NOT NULL AND examination_room_id IS NOT NULL)'
            );

            return;
        }

        foreach (['insert', 'update'] as $action) {
            DB::unprepared('DROP TRIGGER '.self::CONSTRAINT.'_'.$action);
        }
        $this->changeSqliteColumns(nullable: false);
    }

    private function validAssignment(string $prefix = ''): string
    {
        $shift = $prefix.'shift_code';
        $status = $prefix.'status';
        $specialty = $prefix.'specialty_id';
        $room = $prefix.'examination_room_id';

        return "($specialty IS NOT NULL AND $room IS NOT NULL AND ($shift IS NULL OR $status IN ('ACTIVE', 'INACTIVE'))) "
            ."OR ($shift IS NOT NULL AND $status = 'INACTIVE' AND $specialty IS NULL AND $room IS NULL)";
    }

    private function changeSqliteColumns(bool $nullable): void
    {
        Schema::table('doctor_schedules', function (Blueprint $table) use ($nullable): void {
            $table->unsignedBigInteger('specialty_id')->nullable($nullable)->change();
            $table->unsignedBigInteger('examination_room_id')->nullable($nullable)->change();
            // SQLite rebuilds the table. Declare the existing enum check again.
            $table->enum('shift_code', ['MORNING', 'AFTERNOON'])->nullable()->change();
        });
    }
};
