<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TYPES = [
        'RECEPTIONIST' => 'Nhân viên tiếp đón',
        'NURSE' => 'Điều dưỡng',
        'LAB_TECHNICIAN' => 'Kỹ thuật viên xét nghiệm',
        'IMAGING_TECHNICIAN' => 'Kỹ thuật viên chẩn đoán hình ảnh',
        'CASHIER' => 'Nhân viên thu ngân',
    ];

    public function up(): void
    {
        // Refuse unknown legacy classifications instead of guessing or deleting them.
        $existing = DB::table('employee_details')->whereNotNull('employee_type')->distinct()->pluck('employee_type');
        foreach ($existing as $type) {
            if (trim($type) !== '' && ! array_key_exists(trim($type), self::TYPES)
                && ! in_array(trim($type), self::TYPES, true)) {
                throw new RuntimeException('Có loại nhân viên cũ chưa được phân loại chuẩn. Cần đối chiếu và bổ sung loại phù hợp trước khi chạy migration; không tự thay đổi dữ liệu.');
            }
        }
        Schema::create('employee_types', function (Blueprint $table) {
            $table->string('code', 80)->primary();
            $table->string('name', 120)->unique();
            $table->unsignedSmallInteger('display_order');
        });
        foreach (self::TYPES as $code => $name) {
            DB::table('employee_types')->insert(['code' => $code, 'name' => $name, 'display_order' => array_search($code, array_keys(self::TYPES), true) + 1]);
        }
        foreach ($existing as $type) {
            $trimmed = trim($type);
            $code = $trimmed === '' ? null : (array_search($trimmed, self::TYPES, true) ?: $trimmed);
            DB::table('employee_details')->where('employee_type', $type)->update(['employee_type' => $code]);
        }
        Schema::table('employee_details', function (Blueprint $table) {
            $table->foreign('employee_type', 'employee_details_type_fk')->references('code')->on('employee_types')->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::table('employee_details', fn (Blueprint $table) => $table->dropForeign(DB::getDriverName() === 'sqlite' ? ['employee_type'] : 'employee_details_type_fk'));
        Schema::dropIfExists('employee_types');
    }
};
