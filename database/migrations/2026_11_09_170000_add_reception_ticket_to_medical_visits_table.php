<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medical_visits', function (Blueprint $table) {
            $table->unsignedInteger('reception_queue_number')->nullable();
            $table->text('reason_for_visit')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('medical_visits', function (Blueprint $table) {
            $table->dropColumn(['reception_queue_number', 'reason_for_visit']);
        });
    }
};
