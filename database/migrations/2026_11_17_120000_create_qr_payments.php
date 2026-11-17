<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('medical_visit_id')->constrained();
            $table->foreignId('created_by_employee_id')->constrained('employees');
            $table->string('provider', 12);
            $table->unsignedBigInteger('order_code')->unique();
            $table->string('status', 20);
            $table->unsignedBigInteger('amount');
            $table->string('provider_link_id', 80)->nullable()->unique();
            $table->string('provider_reference', 120)->nullable();
            $table->text('qr_payload')->nullable();
            $table->dateTime('expires_at');
            $table->dateTime('paid_at')->nullable();
            $table->string('review_reference', 120)->nullable();
            $table->unsignedBigInteger('review_amount')->nullable();
            $table->string('review_reason', 255)->nullable();
            $table->timestamps();
            $table->index(['medical_visit_id', 'status']);
            $table->unique(['provider', 'provider_reference']);
        });

        Schema::create('qr_payment_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('qr_payment_id')->constrained();
            $table->foreignId('payable_id')->constrained();
            $table->string('description');
            $table->unsignedBigInteger('amount');
            $table->unique(['qr_payment_id', 'payable_id']);
        });

        Schema::table('payment_transactions', function (Blueprint $table): void {
            $table->foreignId('qr_payment_id')->nullable()->constrained();
            $table->unique(['qr_payment_id', 'payable_id']);
        });
    }

    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table): void {
            $table->dropForeign(['qr_payment_id']);
            $table->dropUnique(['qr_payment_id', 'payable_id']);
            $table->dropColumn('qr_payment_id');
        });
        Schema::dropIfExists('qr_payment_items');
        Schema::dropIfExists('qr_payments');
    }
};
