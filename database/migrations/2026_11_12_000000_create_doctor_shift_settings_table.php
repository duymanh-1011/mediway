<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_shift_settings', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id')->primary();
            $table->json('hours')->nullable();
        });

        DB::table('doctor_shift_settings')->insert(['id' => 1]);
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_shift_settings');
    }
};
