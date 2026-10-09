
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_clinical_notes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('medical_visit_id')
                ->unique()
                ->constrained('medical_visits')
                ->restrictOnDelete();

            $table->text('reason_for_visit')->nullable();
            $table->text('current_symptoms')->nullable();
            $table->text('present_illness_history')->nullable();
            $table->text('relevant_medical_history')->nullable();
            $table->text('physical_examination')->nullable();
            $table->text('initial_assessment')->nullable();
            $table->text('notes')->nullable();

            $table->dateTime('recorded_at');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_clinical_notes');
    }
};
