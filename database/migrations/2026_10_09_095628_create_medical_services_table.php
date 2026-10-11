<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('medical_services', function (Blueprint $table) {
            $table->id();

            $table->string('service_code')->unique();

            $table->foreignId('service_group_id')
                ->constrained('service_groups');

            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2);
            $table->string('unit')->nullable();
            $table->unsignedInteger('estimated_duration_minutes')->nullable();
            $table->text('preparation_instructions')->nullable();
            $table->text('precautions')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->string('status', 20)->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_services');
    }
};
