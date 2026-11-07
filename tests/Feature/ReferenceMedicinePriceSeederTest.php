<?php

namespace Tests\Feature;

use App\Models\Medicine;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ReferenceMedicinePriceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferenceMedicinePriceSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_seed_displays_prices_for_applicable_sale_units_in_admin(): void
    {
        $this->seed(DatabaseSeeder::class);

        $prices = Medicine::orderBy('medicine_code')->get()->mapWithKeys(
            fn (Medicine $medicine) => [$medicine->medicine_code => $medicine->salePrices()]
        );

        $this->assertEquals(['viên' => 9821, 'vỉ' => 68747, 'hộp' => 137494], $prices['TH002']);
        $this->assertEquals(['viên' => 2000, 'vỉ' => 30000, 'hộp' => 120000], $prices['TH005']);
        $this->assertEquals(['gói' => 3500, 'hộp' => 70000], $prices['TH007']);
        $this->assertEquals(['chai' => 17000], $prices['TH004']);
        $this->assertEquals(['tuýp' => 17990], $prices['TH009']);

        $this->withoutVite();
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail())
            ->get(route('admin.medicines.index', ['keyword' => 'TH001']))
            ->assertOk()
            ->assertSeeInOrder(['360 đ / viên', '3.600 đ / vỉ', '36.000 đ / hộp']);
    }

    public function test_fills_only_missing_prices_of_matching_medicines_and_preserves_existing_values(): void
    {
        $this->seed(DatabaseSeeder::class);
        Medicine::query()->update(['price_per_unit' => null, 'price_per_blister' => null, 'price_per_box' => null]);
        $existing = Medicine::where('medicine_code', 'TH001')->firstOrFail();
        $existing->update(['price_per_unit' => 777, 'price_per_box' => 20000]);
        $mismatched = Medicine::where('medicine_code', 'TH005')->firstOrFail();
        $mismatched->update(['unit' => 'lọ']);

        $this->seed(ReferenceMedicinePriceSeeder::class);
        $this->seed(ReferenceMedicinePriceSeeder::class);

        $this->assertEquals(['viên' => 777, 'vỉ' => 7770, 'hộp' => 20000], $existing->fresh()->salePrices());
        $this->assertSame([], $mismatched->fresh()->salePrices());
        $this->assertEquals(240, Medicine::where('medicine_code', 'TH003')->value('price_per_unit'));
        $this->assertEquals(2520, Medicine::where('medicine_code', 'TH010')->value('price_per_unit'));
        $this->assertSame(8, Medicine::whereNotNull('price_per_unit')->where('medicine_code', '!=', 'TH001')->count());
    }
}
