<?php

namespace Tests\Feature;

use App\Models\MedicalService;
use App\Models\ServiceGroup;
use App\Models\User;
use Database\Seeders\AdministratorSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ServiceCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, UserSeeder::class, AdministratorSeeder::class]);
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail());
    }

    private function group(string $code): ServiceGroup
    {
        return ServiceGroup::create(['service_group_code' => $code, 'name' => 'Nhóm '.$code, 'status' => 'ACTIVE', 'display_order' => 0]);
    }

    private function serviceData(array $groups): array
    {
        return ['service_code' => 'TEST001', 'name' => 'Xét nghiệm kiểm thử', 'price' => 100000, 'unit' => 'lần', 'status' => 'ACTIVE', 'display_order' => 0, 'service_group_ids' => $groups];
    }

    public function test_one_service_can_belong_to_multiple_groups_and_be_filtered_from_either(): void
    {
        $first = $this->group('XN');
        $second = $this->group('HT');
        $this->post(route('admin.medical-services.store'), $this->serviceData([$first->id, $second->id]))->assertSessionHasNoErrors();
        $service = MedicalService::sole();
        $this->assertCount(2, $service->serviceGroups);
        $this->assertDatabaseCount('medical_services', 1);
        $this->assertFalse(Schema::hasColumn('medical_services', 'service_group_id'));
        foreach ([$first, $second] as $group) {
            $this->get(route('admin.medical-services.index', ['group_id' => $group->id]))->assertOk()->assertSee($service->name);
            $this->get(route('admin.service-groups.show', $group))->assertOk()->assertSee($service->name)->assertSee('Gán dịch vụ');
        }
        $this->get(route('admin.medical-services.create', ['group_id' => $first->id]))->assertOk()->assertViewHas('selectedGroupIds', [$first->id]);
    }

    public function test_assignment_preserves_other_groups_and_is_idempotent(): void
    {
        $first = $this->group('XN');
        $second = $this->group('HT');
        $this->post(route('admin.medical-services.store'), $this->serviceData([$first->id]));
        $service = MedicalService::sole();
        for ($attempt = 0; $attempt < 2; $attempt++) {
            $this->post(route('admin.service-groups.assign', $second), ['medical_service_ids' => [$service->id]])->assertSessionHasNoErrors();
        }
        $this->assertCount(2, $service->fresh()->serviceGroups);
        $this->assertDatabaseCount('medical_services', 1);
        $this->assertDatabaseCount('service_group_medical_service', 2);
    }

    public function test_validation_rejects_duplicate_names_groups_inactive_groups_and_invalid_prices(): void
    {
        $group = $this->group('XN');
        $data = $this->serviceData([$group->id]);
        foreach (['abc', -1000, 1500] as $price) {
            $this->post(route('admin.medical-services.store'), [...$data, 'price' => $price])->assertSessionHasErrors('price');
        }
        $this->post(route('admin.medical-services.store'), [...$data, 'service_group_ids' => [$group->id, $group->id]])->assertSessionHasErrors('service_group_ids.0');
        $this->post(route('admin.medical-services.store'), $data)->assertSessionHasNoErrors();
        $this->post(route('admin.medical-services.store'), [...$data, 'service_code' => 'TEST002'])->assertSessionHasErrors('name');
        $group->update(['status' => 'INACTIVE']);
        $this->post(route('admin.medical-services.store'), [...$data, 'name' => 'Tên mới', 'service_code' => 'TEST002'])->assertSessionHasErrors('service_group_ids.0');
    }

    public function test_update_keeps_its_own_name_but_cannot_take_another_services_name(): void
    {
        $group = $this->group('XN');
        $data = $this->serviceData([$group->id]);
        $this->post(route('admin.medical-services.store'), $data)->assertSessionHasNoErrors();
        $service = MedicalService::sole();
        $data['service_code'] = $service->service_code;
        $this->put(route('admin.medical-services.update', $service), [...$data, 'price' => 200000])->assertSessionHasNoErrors();
        $other = MedicalService::create([...$data, 'service_code' => 'OTHER001', 'name' => 'Dịch vụ đã có']);
        $this->put(route('admin.medical-services.update', $service), [...$data, 'name' => $other->name])->assertSessionHasErrors('name');
        $this->assertSame($data['name'], $service->fresh()->name);
    }

    public function test_deactivation_and_reactivation_preserve_services_and_pivot_history(): void
    {
        $group = $this->group('XN');
        $this->post(route('admin.medical-services.store'), $this->serviceData([$group->id]));
        $service = MedicalService::sole();
        $this->delete(route('admin.medical-services.destroy', $service))->assertSessionHas('success');
        $this->assertSame('INACTIVE', $service->fresh()->status);
        $this->patch(route('admin.medical-services.activate', $service))->assertSessionHas('success');
        $this->assertSame('ACTIVE', $service->fresh()->status);
        $this->delete(route('admin.service-groups.destroy', $group))->assertSessionHas('success');
        $this->assertDatabaseCount('service_group_medical_service', 1);
        $this->patch(route('admin.service-groups.activate', $group))->assertSessionHas('success');
        $this->assertSame('ACTIVE', $group->fresh()->status);
    }
}
