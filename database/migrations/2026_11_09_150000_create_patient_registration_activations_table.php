<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_registration_activations', function (Blueprint $table) {
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->primary('patient_id');
            $table->char('code_hash', 64);
            $table->timestamp('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_registration_activations');
    }
};
