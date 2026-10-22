<?php

namespace Tests\Feature;

use App\Models\Disease;
use App\Models\DiseaseGroup;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLookupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail());
    }

    public function test_lookup_is_bounded_and_searches_records_beyond_the_first_page(): void
    {
        $group = DiseaseGroup::firstOrFail();
        for ($index = 1; $index <= 500; $index++) {
            Disease::create(['disease_code' => 'LOOK'.$index, 'name' => 'Bệnh tìm kiếm '.str_pad((string) $index, 3, '0', STR_PAD_LEFT), 'disease_group_id' => $group->id, 'status' => 'ACTIVE']);
        }
        $this->getJson(route('admin.lookups', ['type' => 'diseases']))->assertOk()->assertJsonCount(20, 'results')->assertJsonPath('has_more', true);
        $last = Disease::where('disease_code', 'LOOK500')->firstOrFail();
        $this->getJson(route('admin.lookups', ['type' => 'diseases', 'q' => 'LOOK500']))->assertOk()->assertJsonCount(1, 'results')->assertJsonPath('results.0.value', (string) $last->id);
        $this->withoutVite()->get(route('admin.diagnoses.index', ['disease_id' => $last->id]))->assertOk()->assertViewHas('diseases', fn ($options) => $options->count() <= 21 && $options->contains('id', $last->id));
    }

    public function test_scope_is_applied_to_both_name_and_code_matches(): void
    {
        $disease = Disease::firstOrFail();
        $disease->update(['status' => 'INACTIVE']);
        $this->getJson(route('admin.lookups', ['type' => 'diseases', 'scope' => 'active', 'q' => $disease->disease_code]))->assertOk()->assertJsonCount(0, 'results');
        $this->getJson(route('admin.lookups', ['type' => 'diseases', 'scope' => 'all', 'q' => $disease->disease_code]))->assertOk()->assertJsonCount(1, 'results');
    }

    public function test_lookup_rejects_arbitrary_tables_and_requires_admin_authentication(): void
    {
        $this->getJson(route('admin.lookups', ['type' => 'users; DROP TABLE users']))->assertUnprocessable();
        auth()->logout();
        $this->get(route('admin.lookups', ['type' => 'diseases']))->assertRedirect(route('admin.login'));
    }
}
