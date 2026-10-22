<?php

namespace Tests\Feature;

use App\Models\BusinessCodeGenerator;
use App\Models\DiseaseGroup;
use App\Models\MedicalService;
use App\Models\ServiceGroup;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BusinessCodeGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_catalog_store_assigns_a_code_without_trusting_submitted_code(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail());
        $cases = [
            ['specialties', 'specialty_code', []],
            ['examination-rooms', 'room_code', ['room_number' => 'NEW101']],
            ['service-groups', 'service_group_code', []],
            ['medical-services', 'service_code', ['price' => 1000, 'unit' => 'lần', 'service_group_ids' => [ServiceGroup::where('service_group_code', 'XN')->value('id')]]],
            ['disease-groups', 'disease_group_code', []],
            ['diseases', 'disease_code', ['disease_group_id' => DiseaseGroup::firstOrFail()->id]],
            ['medicines', 'medicine_code', []],
        ];
        foreach ($cases as [$module, $field, $extra]) {
            [$model, $prefix] = BusinessCodeGenerator::CATALOGS[$field];
            $oldCodes = $model::orderBy('id')->pluck($field, 'id')->all();
            $this->post(route('admin.'.$module.'.store'), ['name' => 'Tạo mới '.$module, 'display_order' => 0, 'status' => 'ACTIVE', $field => 'MANUAL999999999', ...$extra])->assertSessionHasNoErrors()->assertRedirect();
            $record = $model::where('name', 'Tạo mới '.$module)->sole();
            $this->assertMatchesRegularExpression('/^'.$prefix.'[0-9]{6,}$/', $record->{$field});
            $this->assertNotSame('MANUAL999999999', $record->{$field});
            $this->assertSame($oldCodes, $model::whereIn('id', array_keys($oldCodes))->orderBy('id')->pluck($field, 'id')->all());
        }
    }

    public function test_create_forms_preview_codes_readonly_without_reserving_them(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail());
        foreach (['specialties', 'examination-rooms', 'service-groups', 'medical-services', 'disease-groups', 'diseases', 'medicines'] as $module) {
            $this->get(route('admin.'.$module.'.create'))->assertOk()->assertSee('aria-readonly="true"', false)->assertSee('Mã dự kiến');
        }
        $this->assertDatabaseCount('business_code_sequences', 0);
    }

    public function test_existing_catalog_codes_cannot_be_changed_through_update(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail());
        $service = MedicalService::where('service_code', 'DVXN001')->firstOrFail();
        $this->put(route('admin.medical-services.update', $service), [
            'service_code' => 'MANUAL999', 'name' => $service->name, 'price' => $service->price,
            'unit' => $service->unit, 'display_order' => $service->display_order, 'status' => $service->status,
            'service_group_ids' => $service->serviceGroups()->pluck('service_groups.id')->all(),
        ])->assertSessionHasErrors('service_code');
        $this->assertSame('DVXN001', $service->fresh()->service_code);
    }

    public function test_next_code_uses_numeric_maximum_not_row_count_or_lexical_order(): void
    {
        MedicalService::create(['service_code' => 'DV9', 'name' => 'Dịch vụ chín', 'price' => 1000]);
        MedicalService::create(['service_code' => 'OLD100000', 'name' => 'Dịch vụ một trăm nghìn', 'price' => 1000]);
        $generator = app(BusinessCodeGenerator::class);
        $this->assertSame('DV100001', $generator->preview('service_code'));
        $this->assertDatabaseCount('business_code_sequences', 0);
        $this->assertSame('DV100001', $generator->next('service_code'));
        $this->assertSame('DV100002', $generator->next('service_code'));
        $this->assertDatabaseHas('medical_services', ['service_code' => 'OLD100000']);
    }

    public function test_counter_does_not_reuse_a_reserved_code_and_detects_later_imports(): void
    {
        $generator = app(BusinessCodeGenerator::class);
        $this->assertSame('DV000001', $generator->next('service_code'));
        $this->assertSame('DV000002', $generator->next('service_code'));
        MedicalService::create(['service_code' => 'IMPORTED999999', 'name' => 'Dịch vụ nhập dữ liệu', 'price' => 1000]);
        $this->assertSame('DV1000000', $generator->next('service_code'));
        $this->assertSame('TH000001', $generator->next('medicine_code'));
        $this->assertDatabaseCount('business_code_sequences', 2);
    }

    public function test_sequence_allocation_rolls_back_with_its_parent_transaction(): void
    {
        try {
            DB::transaction(function (): void {
                app(BusinessCodeGenerator::class)->next('service_code');
                throw new \RuntimeException('rollback fixture');
            });
        } catch (\RuntimeException $exception) {
            $this->assertSame('rollback fixture', $exception->getMessage());
        }
        $this->assertDatabaseCount('business_code_sequences', 0);
    }
}
