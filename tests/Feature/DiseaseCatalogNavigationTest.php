<?php

namespace Tests\Feature;

use App\Models\Disease;
use App\Models\DiseaseGroup;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiseaseCatalogNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail());
    }

    private function group(): DiseaseGroup
    {
        return DiseaseGroup::where('disease_group_code', 'NBHH')->firstOrFail();
    }

    private function document(string $html): DOMXPath
    {
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return new DOMXPath($document);
    }

    public function test_both_catalogs_share_tabs_on_all_eight_pages(): void
    {
        $disease = Disease::where('disease_code', 'J00')->firstOrFail();
        foreach (['disease-groups' => $this->group(), 'diseases' => $disease] as $module => $record) {
            foreach (['index', 'create', 'show', 'edit'] as $action) {
                $html = $this->get(route('admin.'.$module.'.'.$action, in_array($action, ['show', 'edit']) ? $record : []))->assertOk()->getContent();
                $xpath = $this->document($html);
                $nav = $xpath->query('//main//nav[@class="admin-tabs" and @aria-label="Danh mục bệnh"]');
                $this->assertCount(1, $nav);
                $this->assertCount(2, $xpath->query('./a', $nav->item(0)));
                $active = $xpath->query('./a[@aria-current="page"]', $nav->item(0));
                $this->assertCount(1, $active);
                $this->assertSame(route('admin.'.$module.'.index'), $active->item(0)->getAttribute('href'));
            }
        }
    }

    public function test_group_detail_links_directly_to_add_show_and_edit_disease(): void
    {
        $group = $this->group();
        $disease = Disease::where('disease_code', 'J00')->firstOrFail();
        $this->get(route('admin.disease-groups.show', $group))->assertOk()
            ->assertSee('href="'.route('admin.diseases.create', ['disease_group_id' => $group->id]).'"', false)
            ->assertSee('href="'.route('admin.diseases.show', $disease).'"', false)
            ->assertSee('href="'.route('admin.diseases.edit', $disease).'"', false);
        $this->get(route('admin.diseases.show', $disease))->assertOk()
            ->assertSee('href="'.route('admin.disease-groups.show', $group).'"', false);
    }

    public function test_create_preselects_group_keeps_old_input_and_returns_to_group(): void
    {
        $group = $this->group();
        $other = DiseaseGroup::where('disease_group_code', 'NBTM')->firstOrFail();
        $url = route('admin.diseases.create', ['disease_group_id' => $group->id]);
        $response = $this->get($url)->assertOk()->assertViewHas('selectedDiseaseGroup', fn ($selected): bool => $selected->id === $group->id)
            ->assertSee('href="'.route('admin.disease-groups.show', $group).'"', false);
        $xpath = $this->document($response->getContent());
        $this->assertSame((string) $group->id, $xpath->query('//select[@name="disease_group_id"]/option[@selected]')->item(0)->getAttribute('value'));
        $response = $this->withSession(['_old_input' => ['disease_group_id' => $other->id]])->get($url)->assertOk();
        $xpath = $this->document($response->getContent());
        $this->assertSame((string) $other->id, $xpath->query('//select[@name="disease_group_id"]/option[@selected]')->item(0)->getAttribute('value'));
    }

    public function test_group_filter_and_pagination_never_include_other_groups(): void
    {
        $group = $this->group();
        $other = DiseaseGroup::where('disease_group_code', 'NBTM')->firstOrFail();
        for ($number = 1; $number <= 25; $number++) {
            Disease::create(['disease_code' => 'TEST'.$number, 'disease_group_id' => $group->id, 'name' => 'Bệnh phân trang '.str_pad((string) $number, 2, '0', STR_PAD_LEFT), 'status' => 'ACTIVE']);
        }
        Disease::create(['disease_code' => 'OTHER501', 'disease_group_id' => $other->id, 'name' => 'Ngoài nhóm', 'alternative_name' => 'Bệnh phân trang', 'status' => 'ACTIVE']);
        Disease::create(['disease_code' => 'INACTIVE502', 'disease_group_id' => $group->id, 'name' => 'Bệnh phân trang ngừng', 'status' => 'INACTIVE']);
        $response = $this->get(route('admin.disease-groups.show', [$group, 'keyword' => 'Bệnh phân trang', 'status' => 'ACTIVE', 'page' => 2]))->assertOk()
            ->assertViewHas('diseases', fn ($rows): bool => $rows->total() === 25 && $rows->count() === 10 && $rows->currentPage() === 2)
            ->assertViewHas('totalDiseases', Disease::where('disease_group_id', $group->id)->count())
            ->assertDontSee('Ngoài nhóm')->assertDontSee('Bệnh phân trang ngừng');
        $this->assertStringContainsString('status=ACTIVE', $response->viewData('diseases')->nextPageUrl());
        $this->assertStringContainsString('keyword=', $response->viewData('diseases')->nextPageUrl());
        $this->get(route('admin.disease-groups.show', [$group, 'keyword' => 'INACTIVE502', 'status' => 'INACTIVE']))->assertOk()
            ->assertViewHas('diseases', fn ($rows): bool => $rows->total() === 1 && $rows->first()->disease_code === 'INACTIVE502');
    }

    public function test_inactive_group_remains_readable_without_add_shortcut(): void
    {
        $group = $this->group();
        $group->update(['status' => 'INACTIVE']);
        $this->get(route('admin.disease-groups.show', $group))->assertOk()->assertSee('J00')
            ->assertDontSee('href="'.route('admin.diseases.create', ['disease_group_id' => $group->id]).'"', false);
        $this->get(route('admin.diseases.create', ['disease_group_id' => $group->id]))->assertOk()->assertViewHas('selectedDiseaseGroup', null);
    }

    public function test_creating_from_group_preserves_existing_codes_and_relationships(): void
    {
        $old = Disease::orderBy('id')->get(['id', 'disease_code', 'disease_group_id'])->toArray();
        $group = $this->group();
        $this->post(route('admin.diseases.store'), ['disease_group_id' => $group->id, 'name' => 'Bệnh thêm từ nhóm', 'status' => 'ACTIVE'])->assertSessionHasNoErrors()->assertRedirect();
        $disease = Disease::where('name', 'Bệnh thêm từ nhóm')->sole();
        $this->assertSame($group->id, $disease->disease_group_id);
        $this->assertMatchesRegularExpression('/^B[0-9]{6,}$/', $disease->disease_code);
        $this->assertSame($old, Disease::whereIn('id', array_column($old, 'id'))->orderBy('id')->get(['id', 'disease_code', 'disease_group_id'])->toArray());
    }

    public function test_invalid_group_filters_return_validation_errors(): void
    {
        $this->getJson(route('admin.disease-groups.show', [$this->group(), 'status' => 'UNKNOWN']))->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->getJson(route('admin.disease-groups.show', [$this->group(), 'keyword' => ['bad']]))->assertUnprocessable()->assertJsonValidationErrors('keyword');
    }
}
