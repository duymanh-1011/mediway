<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('employee_details', function (Blueprint $table) {
        $table->id();

        $table->foreignId('employee_id')
            ->unique()
            ->constrained('employees');

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

        $table->string('department', 120)->nullable();
        $table->string('position', 120)->nullable();
        $table->string('employee_type', 80)->nullable();

        $table->date('hire_date')->nullable();
        $table->date('termination_date')->nullable();

        $table->decimal('years_of_service', 5, 2)->nullable();

        $table->string('primary_shift', 80)->nullable();
        $table->string('work_location', 180)->nullable();

        $table->text('job_description')->nullable();
        $table->text('professional_skills')->nullable();

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
    Schema::dropIfExists('employee_details');
}
};
