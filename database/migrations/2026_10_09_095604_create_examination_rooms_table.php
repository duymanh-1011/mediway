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
        Schema::create('examination_rooms', function (Blueprint $table) {
            $table->id();

            $table->string('room_code')->unique();
            $table->string('name');
            $table->string('room_number')->nullable();
            $table->string('floor')->nullable();
            $table->string('building')->nullable();
            $table->text('location_instructions')->nullable();
            $table->text('description')->nullable();
            $table->string('status', 20)->default(true);
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('examination_rooms');
    }
};
