<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('doctor_details', function (Blueprint $table) {
        $table->id();

        $table->foreignId('doctor_id')
            ->unique()
            ->constrained('doctors');

        $table->string('citizen_id_number', 20)->nullable()->unique();
        $table->date('citizen_id_issue_date')->nullable();
        $table->string('citizen_id_issue_place', 150)->nullable();
        $table->string('email', 150)->nullable();
        $table->string('hometown', 255)->nullable();
        $table->string('permanent_address', 255)->nullable();
        $table->string('current_address', 255)->nullable();
        $table->string('nationality', 80)->nullable();
        $table->string('ethnicity', 80)->nullable();

        $table->string('education_level', 120)->nullable();
        $table->string('professional_qualification', 150)->nullable();
        $table->string('academic_title_degree', 120)->nullable();
        $table->string('professional_title', 120)->nullable();
        $table->string('current_position', 120)->nullable();
        $table->string('current_workplace', 180)->nullable();

        $table->date('work_start_date')->nullable();
        $table->unsignedSmallInteger('years_of_experience')->nullable();

        $table->text('specialized_fields')->nullable();
        $table->text('professional_skills')->nullable();
        $table->text('languages')->nullable();

        $table->string('practice_license_number', 80)
            ->nullable()
            ->unique();

        $table->date('practice_license_issue_date')->nullable();
        $table->string('practice_license_issue_place', 180)->nullable();
        $table->date('practice_license_expiry_date')->nullable();

        $table->text('practice_scope')->nullable();
        $table->text('professional_bio')->nullable();

        $table->string('avatar_path', 500)->nullable();
        $table->string('citizen_id_front_path', 500)->nullable();
        $table->string('citizen_id_back_path', 500)->nullable();
        $table->string('practice_license_file_path', 500)->nullable();
        $table->string('profile_pdf_path', 500)->nullable();

        $table->text('notes')->nullable();

        $table->timestamp('created_at');
        $table->timestamp('updated_at');
    });
}

public function down(): void
{
    Schema::dropIfExists('doctor_details');
}
};
