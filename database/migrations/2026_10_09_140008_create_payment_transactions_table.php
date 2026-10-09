<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();

            $table->string('transaction_code', 40)->unique();

            $table->foreignId('payable_id')
                ->constrained('payables');

            $table->decimal('amount', 12, 2);

            $table->string('payment_method', 40);

            $table->string('transaction_result', 40);

            $table->string('provider_transaction_code', 120)->nullable();

            $table->dateTime('started_at');

            $table->dateTime('paid_at')->nullable();

            $table->dateTime('confirmed_at')->nullable();

            $table->foreignId('collected_by_employee_id')
                ->nullable()
                ->constrained('employees');

            $table->string('payment_content', 255)->nullable();

            $table->text('failure_reason')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
