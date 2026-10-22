<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('medical_services', 'service_group_id')) {
            return;
        }

        DB::table('medical_services')->whereNotNull('service_group_id')->orderBy('id')
            ->chunkById(500, function ($services) {
                $now = now();
                DB::table('service_group_medical_service')->insertOrIgnore($services->map(fn ($service) => [
                    'service_group_id' => $service->service_group_id,
                    'medical_service_id' => $service->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all());
            });

        $missing = DB::table('medical_services as services')->whereNotNull('services.service_group_id')
            ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('service_group_medical_service as pivot')
                ->whereColumn('pivot.medical_service_id', 'services.id')
                ->whereColumn('pivot.service_group_id', 'services.service_group_id'))->exists();
        if ($missing) {
            throw new RuntimeException('Chưa chuyển đủ liên kết nhóm dịch vụ. Cột cũ được giữ nguyên.');
        }

        $dropLegacyColumn = fn () => Schema::table('medical_services', fn (Blueprint $table) => $table->dropConstrainedForeignId('service_group_id'));
        if (DB::getDriverName() === 'sqlite') {
            Schema::withoutForeignKeyConstraints($dropLegacyColumn);
        } else {
            $dropLegacyColumn();
        }
    }

    public function down(): void
    {
        $restoreLegacyColumn = fn () => Schema::table('medical_services', fn (Blueprint $table) => $table
            ->foreignId('service_group_id')->nullable()->constrained('service_groups')->restrictOnDelete());
        if (DB::getDriverName() === 'sqlite') {
            Schema::withoutForeignKeyConstraints($restoreLegacyColumn);
        } else {
            $restoreLegacyColumn();
        }
        DB::table('medical_services')->orderBy('id')->chunkById(500, function ($services) {
            foreach ($services as $service) {
                DB::table('medical_services')->where('id', $service->id)->update([
                    'service_group_id' => DB::table('service_group_medical_service')
                        ->where('medical_service_id', $service->id)->min('service_group_id'),
                ]);
            }
        });
    }
};
