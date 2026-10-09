<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_orders', function (Blueprint $table) {
            $table->id();

            $table->string('service_order_code', 30)->unique();

            $table->foreignId('medical_visit_id')
                ->constrained('medical_visits');

            $table->foreignId('ordered_by_doctor_id')
                ->constrained('doctors');

            $table->dateTime('ordered_at');

            $table->text('reason')->nullable();
            $table->text('instructions')->nullable();

            $table->string('status', 20);

            $table->string('order_pdf_path', 500)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_orders');
    }
};
