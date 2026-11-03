<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('medicines', function (Blueprint $table) {
            $table->decimal('price_per_unit', 12, 2)->nullable();
            $table->decimal('price_per_blister', 12, 2)->nullable();
            $table->decimal('price_per_box', 12, 2)->nullable();
        });
        Schema::table('prescription_items', function (Blueprint $table) {
            $table->decimal('unit_price', 12, 2)->nullable();
            $table->decimal('line_total', 12, 2)->nullable();
        });
        Schema::table('payables', function (Blueprint $table) {
            $table->foreignId('prescription_id')->nullable()->constrained('prescriptions');
        });
    }

    public function down(): void
    {
        Schema::table('payables', function (Blueprint $table) { $table->dropConstrainedForeignId('prescription_id'); });
        Schema::table('prescription_items', function (Blueprint $table) { $table->dropColumn(['unit_price', 'line_total']); });
        Schema::table('medicines', function (Blueprint $table) { $table->dropColumn(['price_per_unit', 'price_per_blister', 'price_per_box']); });
    }
};
