<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_order_items', function (Blueprint $table): void {
            $table->foreignId('performed_by_doctor_id')
                ->nullable()
                ->after('performed_by_employee_id')
                ->constrained('doctors')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('service_order_items', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('performed_by_doctor_id');
        });
    }
};
