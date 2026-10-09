
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
/*Không đặt unique() trên medical_visit_id, vì một lượt khám có thể có
    nhiều chẩn đoán. Bảng này tham chiếu đến diseases, do đó bảng diseases phải tồn tại trước khi chạy migration.*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_diagnoses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('medical_visit_id')
                ->constrained('medical_visits')
                ->restrictOnDelete();

            $table->foreignId('disease_id')
                ->constrained('diseases')
                ->restrictOnDelete();

            $table->string('diagnosis_type', 40);
            $table->string('severity', 40)->nullable();
            $table->text('diagnosis_description')->nullable();

            $table->dateTime('diagnosed_at');

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_diagnoses');
    }
};
