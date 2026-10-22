<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CATALOGS = ['specialties', 'examination_rooms', 'service_groups', 'medical_services', 'disease_groups', 'diseases', 'medicines'];

    private const UNIQUES = [
        'appointment_statuses' => ['name'],
        'appointment_time_slots' => ['doctor_schedule_id', 'start_time', 'end_time'],
        'service_order_items' => ['service_order_id', 'medical_service_id'],
        'visit_diagnoses' => ['medical_visit_id', 'disease_id', 'diagnosis_type'],
    ];

    private const INDEX_NAMES = [
        'appointment_statuses' => 'appointment_statuses_name_unique',
        'appointment_time_slots' => 'appointment_slots_schedule_time_unique',
        'service_order_items' => 'service_items_order_service_unique',
        'visit_diagnoses' => 'visit_diagnoses_visit_disease_type_unique',
    ];

    private const CHECKS = [
        'medical_services' => ['medical_services_price_check' => 'price >= 0 AND MOD(price, 1000) = 0'],
        'doctor_schedules' => ['doctor_schedules_time_check' => 'start_time < end_time'],
        'appointment_time_slots' => ['appointment_slots_time_check' => 'start_time < end_time AND max_patients >= 1'],
        'service_order_items' => ['service_items_values_check' => 'price_at_order >= 0 AND (completed_at IS NULL OR (started_at IS NOT NULL AND completed_at >= started_at))'],
        'payables' => ['payables_amount_check' => 'amount >= 0'],
        'payment_transactions' => ['transactions_amount_check' => 'amount >= 0'],
        'prescription_items' => ['prescription_items_quantity_check' => 'quantity > 0 AND (times_per_day IS NULL OR times_per_day > 0) AND (duration_days IS NULL OR duration_days > 0)'],
        'examination_room_queues' => ['queues_number_check' => 'queue_number > 0'],
        'medical_visits' => ['visits_time_check' => 'examination_ended_at IS NULL OR (examination_started_at IS NOT NULL AND examination_ended_at >= examination_started_at)'],
    ];

    public function up(): void
    {
        foreach (self::UNIQUES as $table => $columns) {
            if (DB::table($table)->select($columns)->groupBy($columns)->havingRaw('COUNT(*) > 1')->exists()) {
                throw new RuntimeException("Bảng {$table} có dữ liệu trùng. Cần xử lý nghiệp vụ trước khi thêm unique; migration không xóa dữ liệu.");
            }
        }
        foreach (self::CHECKS as $table => $checks) {
            foreach ($checks as $expression) {
                if (DB::getDriverName() === 'sqlite') {
                    $expression = str_replace('MOD(price, 1000) = 0', 'price = CAST(price / 1000 AS INTEGER) * 1000', $expression);
                }
                if (DB::table($table)->whereRaw("NOT ({$expression})")->exists()) {
                    throw new RuntimeException("Bảng {$table} có dữ liệu vi phạm ràng buộc. Migration không tự sửa dữ liệu lịch sử.");
                }
            }
        }

        foreach (self::CATALOGS as $table) {
            $this->changeStatusDefault($table, 'ACTIVE');
        }
        foreach (self::UNIQUES as $table => $columns) {
            if (! Schema::hasIndex($table, $columns, 'unique')) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->unique($columns, self::INDEX_NAMES[$table]));
            }
        }

        if (DB::getDriverName() === 'mysql') {
            foreach (self::CHECKS as $table => $checks) {
                foreach ($checks as $name => $expression) {
                    $exists = DB::table('information_schema.table_constraints')->where('constraint_schema', DB::getDatabaseName())
                        ->where('table_name', $table)->where('constraint_name', $name)->exists();
                    if (! $exists) {
                        DB::statement("ALTER TABLE `{$table}` ADD CONSTRAINT `{$name}` CHECK ({$expression})");
                    }
                }
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            foreach (self::CHECKS as $table => $checks) {
                foreach (array_keys($checks) as $name) {
                    DB::statement("ALTER TABLE `{$table}` DROP CHECK `{$name}`");
                }
            }
        }
        foreach (self::UNIQUES as $table => $columns) {
            if ($table !== 'appointment_statuses' && ! Schema::hasIndex($table, [$columns[0]])) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->index($columns[0], $table.'_rollback_fk_index'));
            }
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropUnique(self::INDEX_NAMES[$table]));
        }
        foreach (self::CATALOGS as $table) {
            $this->changeStatusDefault($table, '1');
        }
    }

    private function changeStatusDefault(string $table, string $default): void
    {
        $change = fn () => Schema::table($table, fn (Blueprint $blueprint) => $blueprint->string('status', 20)->default($default)->change());
        if (DB::getDriverName() === 'sqlite') {
            Schema::withoutForeignKeyConstraints($change);
        } else {
            $change();
        }
    }
};
