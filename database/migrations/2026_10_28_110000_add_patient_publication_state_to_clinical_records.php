<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['service_results', 'prescriptions'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->string('patient_publication_status', 16)->default('PRIVATE')->index();
            });
        }
    }

    public function down(): void
    {
        foreach (['service_results', 'prescriptions'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropIndex(['patient_publication_status']);
                $table->dropColumn('patient_publication_status');
            });
        }
    }
};
