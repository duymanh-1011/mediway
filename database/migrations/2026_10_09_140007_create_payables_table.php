<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payables', function (Blueprint $table) {
            $table->id();

            $table->string('payable_code', 30)->unique();

            $table->foreignId('medical_visit_id')
                ->constrained('medical_visits');

            $table->foreignId('service_order_item_id')
                ->nullable()
                ->unique()
                ->constrained('service_order_items');

            $table->string('payment_type', 40);

            $table->string('description', 255);

            $table->decimal('amount', 12, 2);

            $table->foreignId('payment_status_id')
                ->constrained('payment_statuses');

            $table->dateTime('due_at')->nullable();

            $table->text('payment_qr_data')->nullable();

            $table->dateTime('generated_at');

            $table->dateTime('paid_at')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payables');
    }
};
