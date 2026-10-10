<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MedicineSeeder extends Seeder
{
    public function run(): void
    {
        $medicines = [
            ['TH001', 'Paracetamol',               'Paracetamol',                            '500 mg',     'Viên nén',      'viên', 'Uống'],
            ['TH002', 'Amoxicillin/Clavulanate',   'Amoxicillin + Clavulanate',              '875/125 mg', 'Viên nén',      'viên', 'Uống'],
            ['TH003', 'Cetirizine',                 'Cetirizine',                             '10 mg',      'Viên nén',      'viên', 'Uống'],
            ['TH004', 'Natri clorid 0,9% xịt mũi', 'Natri clorid',                           '0,9%',       'Dung dịch xịt', 'chai', 'Xịt mũi'],
            ['TH005', 'Losartan',                   'Losartan potassium',                     '50 mg',      'Viên nén',      'viên', 'Uống'],
            ['TH006', 'Omeprazole',                 'Omeprazole',                             '20 mg',      'Viên nang',     'viên', 'Uống'],
            ['TH007', 'Antacid',                    'Aluminium hydroxide + Magnesium hydroxide','10 ml',    'Hỗn dịch',      'gói',  'Uống'],
            ['TH008', 'Metformin',                  'Metformin',                              '500 mg',     'Viên nén',      'viên', 'Uống'],
            ['TH009', 'Hydrocortisone 1%',          'Hydrocortisone',                         '1%',         'Kem bôi',       'tuýp', 'Bôi ngoài da'],
            ['TH010', 'Acetylcysteine',             'Acetylcysteine',                         '200 mg',     'Gói bột',       'gói',  'Uống'],
        ];

        foreach ($medicines as $medicine) {
            [$code, $name, $ingredient, $strength, $form, $unit, $route] = $medicine;

            DB::table('medicines')->updateOrInsert(
                ['medicine_code' => $code],
                [
                    'name' => $name,
                    'active_ingredient' => $ingredient,
                    'strength' => $strength,
                    'dosage_form' => $form,
                    'unit' => $unit,
                    'route_of_administration' => $route,
                    'manufacturer' => null,
                    'country_of_origin' => null,
                    'description' => null,
                    'indications' => null,
                    'contraindications' => null,
                    'warnings' => null,
                    'common_side_effects' => null,
                    'storage_instructions' => null,
                    'status' => 'ACTIVE',
                    'notes' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}