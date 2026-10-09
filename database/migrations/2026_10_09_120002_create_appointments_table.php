<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('appointments', function (Blueprint $table) {
        $table->id();

        $table->string('appointment_code', 30)->unique();

        $table->foreignId('patient_id')
            ->constrained('patients');

        $table->foreignId('doctor_id')
            ->constrained('doctors');

        $table->foreignId('specialty_id')
            ->constrained('specialties');

        $table->foreignId('examination_room_id')
            ->constrained('examination_rooms');

        $table->date('appointment_date');

        $table->foreignId('appointment_time_slot_id')
            ->constrained('appointment_time_slots');

        $table->foreignId('appointment_status_id')
            ->constrained('appointment_statuses');

        $table->string('checkin_token', 120)->unique();

        $table->text('reason_for_visit')->nullable();
        $table->text('patient_notes')->nullable();

        $table->dateTime('booked_at');
        $table->dateTime('cancelled_at')->nullable();
        $table->text('cancellation_reason')->nullable();

        $table->timestamps();
    });
}

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
