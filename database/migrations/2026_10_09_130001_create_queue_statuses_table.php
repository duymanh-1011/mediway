
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queue_statuses', function (Blueprint $table) {
            $table->id();

            $table->string('name', 80)->unique();
            $table->string('description', 255)->nullable();

            $table->unsignedInteger('process_order');
            $table->boolean('allows_transition');
            $table->boolean('is_active');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_statuses');
    }
};
