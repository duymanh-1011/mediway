<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_time_slots', function (Blueprint $table) {
            $table->id();

            $table->foreignId('doctor_schedule_id')
                ->constrained('doctor_schedules');

            $table->time('start_time');
            $table->time('end_time');

            $table->unsignedSmallInteger('max_patients');

            $table->string('status', 20);
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_time_slots');
    }
};
