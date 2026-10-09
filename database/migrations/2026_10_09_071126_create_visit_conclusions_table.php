
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('visit_conclusions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('medical_visit_id')
                ->unique()
                ->constrained('medical_visits')
                ->restrictOnDelete();

            $table->foreignId('doctor_id')
                ->constrained('doctors')
                ->restrictOnDelete();

            $table->text('professional_conclusion');
            $table->string('disposition', 120)->nullable();
            $table->text('treatment_plan')->nullable();
            $table->text('instructions')->nullable();

            $table->boolean('has_follow_up');
            $table->date('follow_up_date')->nullable();
            $table->text('follow_up_reason')->nullable();

            $table->text('notes')->nullable();
            $table->string('conclusion_pdf_path', 500)->nullable();

            $table->dateTime('concluded_at');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_conclusions');
    }
};
