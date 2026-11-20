<?php

namespace Tests\Feature;

use App\Models\Medicine;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DefenseDatasetSeeder;
use Database\Seeders\ReferenceMedicinePriceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferenceMedicinePriceSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_seed_has_reference_prices_for_each_core_sale_unit(): void
    {
        $this->seed(DatabaseSeeder::class);

        $prices = Medicine::orderBy('medicine_code')->get()->mapWithKeys(
            fn (Medicine $medicine) => [$medicine->medicine_code => $medicine->salePrices()]
        );

        $this->assertEquals(['viên' => 360, 'vỉ' => 3600, 'hộp' => 36000], $prices['TH001']);
        $this->assertEquals(['viên' => 13000, 'vỉ' => 91000, 'hộp' => 182000], $prices['TH002']);
        $this->assertEquals(['viên' => 480, 'vỉ' => 4800, 'hộp' => 48000], $prices['TH003']);
        $this->assertEquals(['chai' => 17000], $prices['TH004']);
        $this->assertEquals(['viên' => 380, 'vỉ' => 3800, 'hộp' => 38000], $prices['TH005']);
        $this->assertEquals(['viên' => 350, 'vỉ' => 3500, 'hộp' => 35000], $prices['TH006']);
        $this->assertEquals(['gói' => 2478, 'hộp' => 49560], $prices['TH007']);
        $this->assertEquals(['viên' => 600, 'vỉ' => 6000, 'hộp' => 60000], $prices['TH008']);
        $this->assertEquals(['tuýp' => 17979], $prices['TH009']);
        $this->assertEquals(['gói' => 435, 'hộp' => 8700], $prices['TH010']);

        $this->withoutVite();
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail())
            ->get(route('admin.medicines.index', ['keyword' => 'TH001']))
            ->assertOk()
            ->assertSeeInOrder(['360 đ / viên', '3.600 đ / vỉ', '36.000 đ / hộp']);
    }

    public function test_price_seed_fills_missing_fields_without_overwriting_existing_prices(): void
    {
        $this->seed(DatabaseSeeder::class);
        Medicine::query()->update(['price_per_unit' => null, 'price_per_blister' => null, 'price_per_box' => null]);

        $existing = Medicine::where('medicine_code', 'TH001')->firstOrFail();
        $existing->update(['price_per_unit' => 777, 'price_per_box' => 20000, 'notes' => null]);
        $mismatched = Medicine::where('medicine_code', 'TH005')->firstOrFail();
        $mismatched->update(['unit' => 'lọ']);

        $this->seed(ReferenceMedicinePriceSeeder::class);
        $this->seed(ReferenceMedicinePriceSeeder::class);

        $this->assertEquals(['viên' => 777, 'vỉ' => 7770, 'hộp' => 20000], $existing->fresh()->salePrices());
        $this->assertNull($existing->fresh()->notes, 'A reference-price note must not be attached to a configured unit price.');
        $this->assertSame([], $mismatched->fresh()->salePrices());
        $this->assertEquals(480, Medicine::where('medicine_code', 'TH003')->value('price_per_unit'));
        $this->assertEquals(435, Medicine::where('medicine_code', 'TH010')->value('price_per_unit'));
        $this->assertSame(8, Medicine::whereNotNull('price_per_unit')->where('medicine_code', '!=', 'TH001')->count());
    }

    public function test_defense_catalog_is_priced_when_its_seeder_is_run_directly(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DefenseDatasetSeeder::class);

        $this->assertGreaterThan(0, Medicine::where('medicine_code', 'like', 'TH9%')->count());
        $this->assertSame(0, Medicine::where('medicine_code', 'like', 'TH9%')->whereNull('price_per_unit')->count());
        $this->assertSame(0, Medicine::where('medicine_code', 'like', 'TH9%')->whereNull('notes')->count());
    }
}
