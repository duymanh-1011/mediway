<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_health_records', function (Blueprint $table) {
            $table->id();

            $table->foreignId('patient_id')
                ->constrained('patients');

            $table->string('patient_code', 30);

            $table->decimal('height_cm', 5, 2)->nullable();
            $table->decimal('weight_kg', 5, 2)->nullable();
            $table->decimal('bmi', 5, 2)->nullable();

            $table->unsignedSmallInteger('systolic_blood_pressure')->nullable();
            $table->unsignedSmallInteger('diastolic_blood_pressure')->nullable();
            $table->unsignedSmallInteger('heart_rate')->nullable();

            $table->decimal('body_temperature', 4, 1)->nullable();
            $table->decimal('spo2', 5, 2)->nullable();

            $table->unsignedSmallInteger('respiratory_rate')->nullable();
            $table->decimal('blood_glucose', 8, 2)->nullable();

            $table->dateTime('recorded_at');

            $table->text('notes')->nullable();

            $table->timestamp('created_at');
            $table->timestamp('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_health_records');
    }
};
