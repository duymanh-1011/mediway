<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employee_details') && Schema::hasColumn('employee_details', 'employee_type')) {
            Schema::table('employee_details', function (Blueprint $table): void {
                $table->dropForeign(DB::getDriverName() === 'sqlite' ? ['employee_type'] : 'employee_details_type_fk');
            });
        }

        Schema::dropIfExists('employee_types');

        if (Schema::hasTable('employee_details')) {
            $columns = array_values(array_filter(
                ['department', 'employee_type'],
                fn (string $column): bool => Schema::hasColumn('employee_details', $column)
            ));
            if ($columns !== []) {
                Schema::table('employee_details', fn (Blueprint $table) => $table->dropColumn($columns));
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('employee_types')) {
            Schema::create('employee_types', function (Blueprint $table): void {
                $table->string('code', 80)->primary();
                $table->string('name', 120)->unique();
                $table->unsignedSmallInteger('display_order');
            });

            DB::table('employee_types')->insert([
                ['code' => 'RECEPTIONIST', 'name' => 'Nhân viên tiếp đón', 'display_order' => 1],
                ['code' => 'CASHIER', 'name' => 'Nhân viên thu ngân', 'display_order' => 2],
                ['code' => 'SERVICE_TECHNICIAN', 'name' => 'Kỹ thuật viên dịch vụ y tế', 'display_order' => 3],
            ]);
        }

        if (Schema::hasTable('employee_details')) {
            Schema::table('employee_details', function (Blueprint $table): void {
                if (! Schema::hasColumn('employee_details', 'department')) {
                    $table->string('department', 120)->nullable();
                }
                if (! Schema::hasColumn('employee_details', 'employee_type')) {
                    $table->string('employee_type', 80)->nullable();
                }
            });

            Schema::table('employee_details', function (Blueprint $table): void {
                $table->foreign('employee_type', 'employee_details_type_fk')
                    ->references('code')->on('employee_types')->restrictOnDelete()->restrictOnUpdate();
            });
        }
    }
};
