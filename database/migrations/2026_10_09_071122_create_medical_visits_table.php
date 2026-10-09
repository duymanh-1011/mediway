
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_visits', function (Blueprint $table) {
            $table->id();

            $table->string('visit_code', 30)->unique();

            $table->foreignId('appointment_id')
                ->nullable()
                ->unique()
                ->constrained('appointments')
                ->restrictOnDelete();

            $table->foreignId('patient_id')
                ->constrained('patients')
                ->restrictOnDelete();

            $table->foreignId('doctor_id')
                ->constrained('doctors')
                ->restrictOnDelete();

            $table->foreignId('specialty_id')
                ->constrained('specialties')
                ->restrictOnDelete();

            $table->foreignId('examination_room_id')
                ->constrained('examination_rooms')
                ->restrictOnDelete();

            $table->foreignId('visit_status_id')
                ->constrained('visit_statuses')
                ->restrictOnDelete();

            $table->dateTime('checked_in_at');
            $table->dateTime('examination_started_at')->nullable();
            $table->dateTime('examination_ended_at')->nullable();

            $table->string('reception_source', 30);
            $table->text('reception_notes')->nullable();
            $table->string('examination_slip_pdf_path', 500)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_visits');
    }
};
