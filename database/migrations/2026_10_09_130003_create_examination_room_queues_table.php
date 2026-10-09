
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('examination_room_queues', function (Blueprint $table) {
            $table->id();

            $table->foreignId('medical_visit_id')
                ->constrained('medical_visits')
                ->restrictOnDelete();

            $table->foreignId('queue_status_id')
                ->constrained('queue_statuses')
                ->restrictOnDelete();

            $table->unsignedInteger('queue_number');
            $table->string('queue_type', 40);

            $table->dateTime('queued_at');
            $table->dateTime('called_at')->nullable();
            $table->dateTime('service_started_at')->nullable();
            $table->dateTime('completed_at')->nullable();

            $table->unsignedSmallInteger('call_count');
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('examination_room_queues');
    }
};
