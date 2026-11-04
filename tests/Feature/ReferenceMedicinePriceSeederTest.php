<?php

namespace Tests\Feature;

use App\Models\Medicine;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ReferenceMedicinePriceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferenceMedicinePriceSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_fills_only_missing_prices_of_matching_medicines_and_preserves_existing_values(): void
    {
        $this->seed(DatabaseSeeder::class);
        Medicine::query()->update(['price_per_unit' => null]);
        $existing = Medicine::where('medicine_code', 'TH001')->firstOrFail();
        $existing->update(['price_per_unit' => 777, 'price_per_box' => 20000]);
        $mismatched = Medicine::where('medicine_code', 'TH004')->firstOrFail();
        $mismatched->update(['unit' => 'lọ']);

        $this->seed(ReferenceMedicinePriceSeeder::class);
        $this->seed(ReferenceMedicinePriceSeeder::class);

        $this->assertEquals(777, $existing->fresh()->price_per_unit);
        $this->assertEquals(20000, $existing->fresh()->price_per_box);
        $this->assertNull($mismatched->fresh()->price_per_unit);
        $this->assertEquals(240, Medicine::where('medicine_code', 'TH003')->value('price_per_unit'));
        $this->assertEquals(2520, Medicine::where('medicine_code', 'TH010')->value('price_per_unit'));
        $this->assertSame(8, Medicine::whereNotNull('price_per_unit')->where('medicine_code', '!=', 'TH001')->count());
    }
}
