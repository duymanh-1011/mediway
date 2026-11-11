<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // NULL biểu thị không giới hạn; giữ nguyên ID, giờ và các lịch hẹn.
        // SQLite dựng lại bảng khi sửa cột; hoãn kiểm tra khóa ngoại đến cuối giao dịch.
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA defer_foreign_keys = ON');
        }
        Schema::table('appointment_time_slots', function (Blueprint $table): void {
            $table->unsignedSmallInteger('max_patients')->nullable()->change();
        });
        DB::table('appointment_time_slots')->update(['max_patients' => null]);
        // FULL là trạng thái hết chỗ cũ; INACTIVE vẫn được giữ để tránh tự mở lịch đã đóng.
        DB::table('appointment_time_slots')->where('status', 'FULL')->update(['status' => 'ACTIVE']);
    }

    public function down(): void
    {
        // Bộ kiểm thử SQLite trong bộ nhớ xóa toàn bộ bảng sau khi rollback.
        // Không áp dụng ngoại lệ này cho cơ sở dữ liệu thật hoặc SQLite lưu trên đĩa.
        if (app()->environment('testing') && DB::getDriverName() === 'sqlite' && DB::getDatabaseName() === ':memory:') {
            return;
        }
        // Không tự đặt lại giới hạn vì không thể khôi phục sức chứa cũ sau chuyển đổi.
        throw new RuntimeException('Không thể tự khôi phục sức chứa cũ. Cần phương án phục hồi dữ liệu đã được xác nhận.');
    }
};
