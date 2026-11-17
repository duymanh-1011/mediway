<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $addEffectiveFrom = ! Schema::hasColumn('service_result_templates', 'effective_from');
        $addEffectiveUntil = ! Schema::hasColumn('service_result_templates', 'effective_until');
        if ($addEffectiveFrom || $addEffectiveUntil) {
            Schema::table('service_result_templates', function (Blueprint $table) use ($addEffectiveFrom, $addEffectiveUntil): void {
                if ($addEffectiveFrom) $table->date('effective_from')->nullable()->after('is_active');
                if ($addEffectiveUntil) $table->date('effective_until')->nullable()->after('effective_from');
            });
        }

        // Legacy templates remain applicable from the start of the recorded history.
        DB::table('service_result_templates')->whereNull('effective_from')->update(['effective_from' => '1970-01-01']);

        if (! Schema::hasTable('medical_service_result_templates')) {
            Schema::create('medical_service_result_templates', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('medical_service_id');
                $table->unsignedBigInteger('service_result_template_id');
                $table->timestamps();
                $table->foreign('medical_service_id', 'msrt_service_fk')->references('id')->on('medical_services')->cascadeOnDelete();
                $table->foreign('service_result_template_id', 'msrt_template_fk')->references('id')->on('service_result_templates')->cascadeOnDelete();
                $table->unique(['medical_service_id', 'service_result_template_id'], 'msrt_service_template_unique');
            });
        } else {
            $columns = Schema::getColumnListing('medical_service_result_templates');
            if (! in_array('medical_service_id', $columns, true) || ! in_array('service_result_template_id', $columns, true)) {
                throw new \RuntimeException('Bảng medical_service_result_templates đã tồn tại nhưng thiếu cột liên kết; cần rà soát schema trước khi tiếp tục.');
            }

            $duplicates = DB::table('medical_service_result_templates')
                ->select('medical_service_id', 'service_result_template_id')
                ->groupBy('medical_service_id', 'service_result_template_id')
                ->havingRaw('COUNT(*) > 1')
                ->exists();
            if ($duplicates) {
                throw new \RuntimeException('Bảng medical_service_result_templates có liên kết trùng; migration dừng để không tự xóa dữ liệu.');
            }

            $foreignKeys = Schema::getForeignKeys('medical_service_result_templates');
            $hasServiceForeignKey = collect($foreignKeys)->contains(fn (array $key): bool => in_array('medical_service_id', $key['columns'] ?? [], true));
            $hasTemplateForeignKey = collect($foreignKeys)->contains(fn (array $key): bool => in_array('service_result_template_id', $key['columns'] ?? [], true));
            $orphanServices = DB::table('medical_service_result_templates as assignments')
                ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('medical_services')->whereColumn('medical_services.id', 'assignments.medical_service_id'))
                ->exists();
            $orphanTemplates = DB::table('medical_service_result_templates as assignments')
                ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('service_result_templates')->whereColumn('service_result_templates.id', 'assignments.service_result_template_id'))
                ->exists();
            if ($orphanServices || $orphanTemplates) {
                throw new \RuntimeException('Bảng medical_service_result_templates có liên kết mồ côi; migration dừng để không thay đổi dữ liệu.');
            }

            Schema::table('medical_service_result_templates', function (Blueprint $table) use ($hasServiceForeignKey, $hasTemplateForeignKey): void {
                if (! $hasServiceForeignKey) $table->foreign('medical_service_id', 'msrt_service_fk')->references('id')->on('medical_services')->cascadeOnDelete();
                if (! $hasTemplateForeignKey) $table->foreign('service_result_template_id', 'msrt_template_fk')->references('id')->on('service_result_templates')->cascadeOnDelete();
                if (! Schema::hasIndex('medical_service_result_templates', ['medical_service_id', 'service_result_template_id'], 'unique')) {
                    $table->unique(['medical_service_id', 'service_result_template_id'], 'msrt_service_template_unique');
                }
            });
        }

        // Preserve every existing explicit FK assignment without using service or template names.
        DB::table('medical_services')
            ->whereNotNull('service_result_template_id')
            ->orderBy('id')
            ->chunkById(500, function ($services): void {
                $now = now();
                $rows = $services->map(fn ($service): array => [
                    'medical_service_id' => $service->id,
                    'service_result_template_id' => $service->service_result_template_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();
                DB::table('medical_service_result_templates')->insertOrIgnore($rows);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_service_result_templates');
        Schema::table('service_result_templates', function (Blueprint $table): void {
            $table->dropColumn(['effective_from', 'effective_until']);
        });
    }
};
