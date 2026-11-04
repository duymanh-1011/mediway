<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Giá đối chiếu ngày 04/11/2026; chỉ bổ sung giá còn trống cho đúng thuốc mẫu. */
class ReferenceMedicinePriceSeeder extends Seeder
{
    public function run(): void
    {
        // Chi tiết sản phẩm, nguồn và cách quy đổi nằm trong docs/gia-thuoc-tham-khao.md.
        $prices = [
            ['TH001', 'Paracetamol', '500 mg', 'viên', 360],
            ['TH002', 'Amoxicillin/Clavulanate', '875/125 mg', 'viên', 9821],
            ['TH003', 'Cetirizine', '10 mg', 'viên', 240],
            ['TH004', 'Natri clorid 0,9% xịt mũi', '0,9%', 'chai', 17000],
            ['TH005', 'Losartan', '50 mg', 'viên', 2000],
            ['TH006', 'Omeprazole', '20 mg', 'viên', 833],
            ['TH007', 'Antacid', '10 ml', 'gói', 3500],
            ['TH008', 'Metformin', '500 mg', 'viên', 600],
            ['TH009', 'Hydrocortisone 1%', '1%', 'tuýp', 17990],
            ['TH010', 'Acetylcysteine', '200 mg', 'gói', 2520],
        ];

        DB::transaction(function () use ($prices): void {
            foreach ($prices as [$code, $name, $strength, $unit, $price]) {
                DB::table('medicines')->where('medicine_code', $code)
                    ->where('name', $name)->where('strength', $strength)->where('unit', $unit)
                    ->whereNull('price_per_unit')
                    ->update(['price_per_unit' => $price, 'updated_at' => now()]);
            }
        });
    }
}
