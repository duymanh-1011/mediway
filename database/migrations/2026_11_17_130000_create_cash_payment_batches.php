<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_payment_batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('medical_visit_id')->constrained();
            $table->string('receipt_code', 28)->unique();
            $table->timestamps();
        });

        Schema::table('payment_transactions', function (Blueprint $table): void {
            $table->foreignId('cash_payment_batch_id')->nullable()->constrained();
            $table->unique(['cash_payment_batch_id', 'payable_id'], 'cash_batch_payable_unique');
        });
    }

    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table): void {
            $table->dropForeign(['cash_payment_batch_id']);
            $table->dropUnique('cash_batch_payable_unique');
            $table->dropColumn('cash_payment_batch_id');
        });

        Schema::dropIfExists('cash_payment_batches');
    }
};
