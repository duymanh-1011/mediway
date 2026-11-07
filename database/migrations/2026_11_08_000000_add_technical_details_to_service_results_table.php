<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_results', function (Blueprint $table) {
            $table->json('technical_details')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('service_results', function (Blueprint $table) {
            $table->dropColumn('technical_details');
        });
    }
};
