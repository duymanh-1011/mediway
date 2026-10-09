<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescription_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('prescription_id')
                ->constrained('prescriptions');

            $table->foreignId('medicine_id')
                ->constrained('medicines');

            $table->decimal('quantity', 10, 2);

            $table->string('usage_unit', 50)->nullable();
            $table->string('dose_per_use', 80)->nullable();

            $table->decimal('times_per_day', 5, 2)->nullable();

            $table->unsignedSmallInteger('duration_days')->nullable();

            $table->string('administration_time', 120)->nullable();
            $table->string('usage_method', 120)->nullable();

            $table->text('usage_instructions')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_items');
    }
};
