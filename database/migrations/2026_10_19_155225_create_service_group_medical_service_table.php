<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Bảng trung gian Nhóm dịch vụ - Dịch vụ
        |--------------------------------------------------------------------------
        */

        Schema::create(
            'service_group_medical_service',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId('service_group_id')
                    ->constrained('service_groups')
                    ->restrictOnDelete();

                $table->foreignId('medical_service_id')
                    ->constrained('medical_services')
                    ->restrictOnDelete();

                $table->timestamps();

                $table->unique(
                    [
                        'service_group_id',
                        'medical_service_id',
                    ],
                    'service_group_medical_service_unique'
                );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Chuyển dữ liệu cũ sang bảng trung gian
        |--------------------------------------------------------------------------
        |
        | Mỗi medical_service hiện tại đang có service_group_id.
        | Ta giữ lại quan hệ hiện có bằng cách copy nó sang pivot.
        |--------------------------------------------------------------------------
        */

        $medicalServices =
            DB::table('medical_services')
                ->select([
                    'id',
                    'service_group_id',
                ])
                ->whereNotNull('service_group_id')
                ->get();

        foreach ($medicalServices as $medicalService) {
            DB::table(
                'service_group_medical_service'
            )->insertOrIgnore([
                'service_group_id' => $medicalService->service_group_id,

                'medical_service_id' => $medicalService->id,

                'created_at' => now(),

                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'service_group_medical_service'
        );
    }
};
