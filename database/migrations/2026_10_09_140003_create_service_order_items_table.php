<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('service_order_id')
                ->constrained('service_orders');

            $table->foreignId('medical_service_id')
                ->constrained('medical_services');

            $table->foreignId('service_status_id')
                ->constrained('service_statuses');

            $table->decimal('price_at_order', 12, 2);

            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();

            $table->foreignId('performed_by_employee_id')
                ->nullable()
                ->constrained('employees');

            $table->text('execution_notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_order_items');
    }
};
