<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_results', function (Blueprint $table) {
            $table->id();

            $table->foreignId('service_order_item_id')
                ->unique()
                ->constrained('service_order_items');

            $table->text('result_summary')->nullable();
            $table->text('result_conclusion')->nullable();

            $table->json('result_parameters')->nullable();

            $table->string('result_pdf_path', 500)->nullable();
            $table->string('result_image_path', 500)->nullable();

            $table->foreignId('verified_by_user_id')
                ->nullable()
                ->constrained('users');

            $table->dateTime('resulted_at')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_results');
    }
};
