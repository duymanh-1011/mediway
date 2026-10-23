<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('specialties', function (Blueprint $table) {
            $table->boolean('is_general')->default(false);
        });
        Schema::table('doctors', function (Blueprint $table) {
            // Existing clinical records must not be assigned an inferred specialty.
            $table->foreignId('specialty_id')->nullable()->constrained('specialties')->restrictOnDelete();
        });

        $general = DB::table('specialties')->where('specialty_code', 'CKDK')->first();
        if ($general) {
            DB::table('specialties')->where('id', $general->id)->update(['is_general' => true]);
        } else {
            DB::table('specialties')->insert([
                'specialty_code' => 'CKDK', 'name' => 'Đa khoa', 'status' => 'ACTIVE',
                'is_general' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('doctors', fn (Blueprint $table) => $table->dropConstrainedForeignId('specialty_id'));
        Schema::table('specialties', fn (Blueprint $table) => $table->dropColumn('is_general'));
    }
};
