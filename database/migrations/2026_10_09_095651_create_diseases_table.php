<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('diseases', function (Blueprint $table) {
            $table->id();

            $table->string('disease_code')->unique();

            $table->foreignId('disease_group_id')
                ->constrained('disease_groups');

            $table->string('name');
            $table->string('alternative_name')->nullable();
            $table->text('description')->nullable();
            $table->text('common_symptoms')->nullable();
            $table->text('professional_notes')->nullable();
            $table->boolean('status')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diseases');
    }
};
