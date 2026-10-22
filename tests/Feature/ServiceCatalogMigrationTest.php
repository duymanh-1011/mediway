<?php

namespace Tests\Feature;

use App\Models\MedicalService;
use App\Models\ServiceGroup;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ServiceCatalogMigrationTest extends TestCase
{
    use DatabaseMigrations;

    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDown();
    }

    public function test_name_unique_migration_refuses_duplicates_without_removing_data(): void
    {
        $migration = require database_path('migrations/2026_10_19_223702_add_unique_name_to_medical_services_table.php');
        $migration->down();
        $group = ServiceGroup::create(['service_group_code' => 'CHECK', 'name' => 'Nhóm kiểm thử']);
        foreach (['LEGACY001', 'LEGACY002'] as $code) {
            $service = MedicalService::create(['service_code' => $code, 'name' => 'Tên lịch sử trùng', 'price' => 1000]);
            $service->serviceGroups()->attach($group);
        }
        try {
            $migration->up();
            $this->fail('Migration phải dừng khi tên trùng, không được tự xóa dữ liệu.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Còn tên dịch vụ trùng', $exception->getMessage());
        }
        $this->assertDatabaseCount('medical_services', 2);
        $this->assertDatabaseCount('service_group_medical_service', 2);
        $this->assertFalse(Schema::hasIndex('medical_services', ['name'], 'unique'));
    }

    public function test_forward_migration_preserves_legacy_and_existing_pivot_links(): void
    {
        $first = ServiceGroup::create(['service_group_code' => 'XN', 'name' => 'Xét nghiệm', 'status' => 'ACTIVE']);
        $second = ServiceGroup::create(['service_group_code' => 'HT', 'name' => 'Hỗ trợ', 'status' => 'ACTIVE']);
        $service = MedicalService::create(['service_code' => 'TEST', 'name' => 'Dịch vụ thử', 'price' => 1000, 'status' => 'ACTIVE']);
        $service->serviceGroups()->attach($second);
        Schema::withoutForeignKeyConstraints(fn () => Schema::table('medical_services', fn (Blueprint $table) => $table->foreignId('service_group_id')->nullable()->constrained('service_groups')));
        DB::table('medical_services')->where('id', $service->id)->update(['service_group_id' => $first->id]);
        $migration = require database_path('migrations/2026_10_19_202126_finalize_service_group_relationship.php');
        $migration->up();
        $this->assertFalse(Schema::hasColumn('medical_services', 'service_group_id'));
        $this->assertCount(2, $service->fresh()->serviceGroups);
        $this->assertDatabaseCount('medical_services', 1);
    }
}
