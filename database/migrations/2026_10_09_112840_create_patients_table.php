<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->unique()
                ->constrained('users');

            $table->string('patient_code', 30)->unique();
            $table->string('full_name', 120);
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('status', 20);

            $table->timestamp('created_at');
            $table->timestamp('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
