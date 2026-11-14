<?php

namespace Tests\Feature;

use App\Models\MedicalService;
use App\Models\ServiceGroup;
use App\Models\ServiceResultTemplate;
use App\Models\User;
use Tests\Fixtures\Seeders\WorkflowFixtureSeeder;
use Database\Seeders\ServiceResultTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ServiceResultTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(WorkflowFixtureSeeder::class);
        $this->withoutVite();
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail());
    }

    public function test_admin_can_save_a_template_with_measurement_fields(): void
    {
        $template = ServiceResultTemplate::where('name', 'Phiếu xét nghiệm')->firstOrFail();

        $this->put(route('admin.service-result-templates.update', $template), [
            'name' => $template->name,
            'description' => $template->description,
            'is_active' => '1',
            'fields' => [
                ['key' => 'measurements', 'label' => 'Các chỉ số xét nghiệm', 'type' => 'measurements', 'required' => '1'],
                ['key' => 'comment', 'label' => 'Nhận xét', 'type' => 'textarea', 'required' => '0'],
            ],
        ])->assertRedirect(route('admin.service-result-templates.index'))->assertSessionHasNoErrors();

        $this->assertSame('measurements', $template->fresh()->fields[0]['type']);
    }

    public function test_template_type_outside_supported_set_is_rejected(): void
    {
        $template = ServiceResultTemplate::where('name', 'Phiếu xét nghiệm')->firstOrFail();

        $this->from(route('admin.service-result-templates.edit', $template))
            ->put(route('admin.service-result-templates.update', $template), [
                'name' => $template->name,
                'is_active' => '1',
                'fields' => [['key' => 'result', 'label' => 'Kết quả', 'type' => 'unknown', 'required' => '1']],
            ])->assertSessionHasErrors('fields.0.type');
    }

    public function test_seeding_templates_preserves_admin_edits_and_existing_service_assignments(): void
    {
        $template = ServiceResultTemplate::where('name', 'Phiếu xét nghiệm')->firstOrFail();
        $template->update(['fields' => [['key' => 'custom', 'label' => 'Trường riêng', 'type' => 'text', 'required' => true]]]);
        $service = MedicalService::whereNotNull('service_result_template_id')->firstOrFail();
        $service->update(['service_result_template_id' => $template->id]);

        $this->seed(ServiceResultTemplateSeeder::class);

        $this->assertSame('custom', $template->fresh()->fields[0]['key']);
        $this->assertSame($template->id, $service->fresh()->service_result_template_id);
    }

    public function test_result_template_assignment_defaults_to_preview_and_only_maps_unambiguous_groups(): void
    {
        $lab = ServiceGroup::where('service_group_code', 'XN')->firstOrFail();
        $imaging = ServiceGroup::where('service_group_code', 'CDHA')->firstOrFail();
        $clearMatch = $this->unmappedService('SRVTEMP001', 'Dịch vụ xét nghiệm chưa gán');
        $clearMatch->serviceGroups()->attach($lab);
        $ambiguous = $this->unmappedService('SRVTEMP002', 'Dịch vụ nhiều nhóm chưa gán');
        $ambiguous->serviceGroups()->attach([$lab->id, $imaging->id]);

        $this->assertSame(0, Artisan::call('medical-services:assign-result-templates'));
        $this->assertNull($clearMatch->fresh()->service_result_template_id);
        $this->assertNull($ambiguous->fresh()->service_result_template_id);
        $this->assertStringContainsString('Chưa thay đổi dữ liệu', Artisan::output());

        $this->assertSame(0, Artisan::call('medical-services:assign-result-templates', ['--apply' => true]));
        $this->assertSame(
            ServiceResultTemplate::where('name', 'Phiếu xét nghiệm')->value('id'),
            $clearMatch->fresh()->service_result_template_id
        );
        $this->assertNull($ambiguous->fresh()->service_result_template_id);
    }

    private function unmappedService(string $code, string $name): MedicalService
    {
        return MedicalService::create([
            'service_code' => $code,
            'name' => $name,
            'price' => 100000,
            'unit' => 'lần',
            'status' => 'ACTIVE',
            'display_order' => 0,
        ]);
    }
}
