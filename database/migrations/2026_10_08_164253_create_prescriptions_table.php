<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();

            $table->string('prescription_code', 30)->unique();

            $table->foreignId('medical_visit_id')
                ->unique()
                ->constrained('medical_visits');

            $table->foreignId('doctor_id')
                ->constrained('doctors');

            $table->dateTime('prescribed_at');

            $table->text('general_instructions')->nullable();
            $table->text('doctor_notes')->nullable();

            $table->string('status', 20);

            $table->string('prescription_pdf_path', 500)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
