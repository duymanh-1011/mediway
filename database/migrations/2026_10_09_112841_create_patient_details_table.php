<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_details', function (Blueprint $table) {
            $table->id();

            $table->foreignId('patient_id')
                ->unique()
                ->constrained('patients');

            $table->string('citizen_id_number', 20)->nullable()->unique();
            $table->date('citizen_id_issue_date')->nullable();
            $table->string('citizen_id_issue_place', 150)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('hometown', 255)->nullable();
            $table->string('permanent_address', 255)->nullable();
            $table->string('current_address', 255)->nullable();
            $table->string('nationality', 80)->nullable();
            $table->string('ethnicity', 80)->nullable();
            $table->string('occupation', 120)->nullable();
            $table->string('blood_type', 10)->nullable();

            $table->string('emergency_contact_name', 120)->nullable();
            $table->string('emergency_contact_phone', 20)->nullable();
            $table->string('emergency_contact_relationship', 80)->nullable();

            $table->string('avatar_path', 500)->nullable();
            $table->string('citizen_id_front_path', 500)->nullable();
            $table->string('citizen_id_back_path', 500)->nullable();
            $table->string('profile_pdf_path', 500)->nullable();

            $table->text('notes')->nullable();

            $table->timestamp('created_at');
            $table->timestamp('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_details');
    }
};
