<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('medical_services')->select('name')->groupBy('name')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Còn tên dịch vụ trùng. Cần quyết định nghiệp vụ trước khi thêm unique; migration không tự xóa dữ liệu.');
        }

        if (! Schema::hasIndex('medical_services', ['name'], 'unique')) {
            Schema::table('medical_services', fn (Blueprint $table) => $table->unique('name', 'medical_services_name_unique'));
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('medical_services', 'medical_services_name_unique')) {
            Schema::table('medical_services', fn (Blueprint $table) => $table->dropUnique('medical_services_name_unique'));
        }
    }
};
