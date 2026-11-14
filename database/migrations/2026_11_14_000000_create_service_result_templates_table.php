<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_result_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 150)->unique();
            $table->text('description')->nullable();
            $table->json('fields');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('medical_services', function (Blueprint $table): void {
            $table->foreignId('service_result_template_id')->nullable()->after('precautions')
                ->constrained('service_result_templates')->nullOnDelete();
        });

        Schema::table('service_results', function (Blueprint $table): void {
            $table->json('template_snapshot')->nullable();
            $table->json('template_values')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('service_results', function (Blueprint $table): void {
            $table->dropColumn(['template_snapshot', 'template_values']);
        });
        Schema::table('medical_services', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('service_result_template_id');
        });
        Schema::dropIfExists('service_result_templates');
    }
};
