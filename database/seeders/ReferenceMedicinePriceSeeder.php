<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Chỉ bổ sung các mức giá còn trống cho đúng thuốc mẫu. */
class ReferenceMedicinePriceSeeder extends Seeder
{
    public function run(): void
    {
        // Mã, tên, hàm lượng, đơn vị, giá cơ bản, số đơn vị trong một vỉ và một hộp.
        $prices = [
            ['TH001', 'Paracetamol', '500 mg', 'viên', 360, 10, 100],
            ['TH002', 'Amoxicillin/Clavulanate', '875/125 mg', 'viên', 9821, 7, 14],
            ['TH003', 'Cetirizine', '10 mg', 'viên', 240, 10, 100],
            ['TH004', 'Natri clorid 0,9% xịt mũi', '0,9%', 'chai', 17000, null, null],
            ['TH005', 'Losartan', '50 mg', 'viên', 2000, 15, 60],
            ['TH006', 'Omeprazole', '20 mg', 'viên', 833, 10, 30],
            ['TH007', 'Antacid', '10 ml', 'gói', 3500, null, 20],
            ['TH008', 'Metformin', '500 mg', 'viên', 600, 15, 60],
            ['TH009', 'Hydrocortisone 1%', '1%', 'tuýp', 17990, null, null],
            ['TH010', 'Acetylcysteine', '200 mg', 'gói', 2520, null, 50],
        ];

        DB::transaction(function () use ($prices): void {
            foreach ($prices as [$code, $name, $strength, $unit, $price, $unitsPerBlister, $unitsPerBox]) {
                $medicine = DB::table('medicines')->where('medicine_code', $code)
                    ->where('name', $name)->where('strength', $strength)->where('unit', $unit)
                    ->lockForUpdate()->first();

                if ($medicine === null) {
                    continue;
                }

                $changes = $medicine->price_per_unit === null ? ['price_per_unit' => $price] : [];
                $unitPrice = $medicine->price_per_unit ?? $price;

                foreach (['price_per_blister' => $unitsPerBlister, 'price_per_box' => $unitsPerBox] as $field => $quantity) {
                    if ($quantity !== null && $medicine->$field === null) {
                        $packPrice = (int) round($unitPrice * $quantity);

                        if ($packPrice >= 1 && $packPrice <= 99999999) {
                            $changes[$field] = $packPrice;
                        }
                    }
                }

                if ($changes !== []) {
                    DB::table('medicines')->where('id', $medicine->id)
                        ->update($changes + ['updated_at' => now()]);
                }
            }
        });
    }
}
