<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Seed reproducible demo reference prices; these are not the clinic's official tariff. */
class ReferenceMedicinePriceSeeder extends Seeder
{
    private const CORE_PRICES = [
        // code => [display name, strength, base unit, unit price, units/blister, units/box, evidence]
        'TH001' => ['Paracetamol', '500 mg', 'viên', 360, 10, 100, 'Long Chau retail product listing'],
        'TH002' => ['Amoxicillin/Clavulanate', '875/125 mg', 'viên', 13000, 7, 14, '2026 public hospital tender benchmark'],
        'TH003' => ['Cetirizine', '10 mg', 'viên', 480, 10, 100, 'Long Chau retail listing; 480 VND/tablet'],
        'TH004' => ['Natri clorid 0,9% xịt mũi', '0,9%', 'chai', 17000, null, null, 'Long Chau retail product listing'],
        'TH005' => ['Losartan', '50 mg', 'viên', 380, 10, 100, 'Long Chau retail listing; 38,000 VND / 100 tablets'],
        'TH006' => ['Omeprazole', '20 mg', 'viên', 350, 10, 100, 'Long Chau retail listing; 35,000 VND / 100 tablets'],
        'TH007' => ['Antacid', '10 ml', 'gói', 2478, null, 20, '2026 tender median for comparable 10 ml antacid sachet'],
        'TH008' => ['Metformin', '500 mg', 'viên', 600, 10, 100, '2026 public hospital tender price'],
        'TH009' => ['Hydrocortisone 1%', '1%', 'tuýp', 17979, null, null, '2026 public hospital tender price for 1% / 10 g tube'],
        'TH010' => ['Acetylcysteine', '200 mg', 'gói', 435, null, 20, '2026 public hospital tender price per 200 mg sachet'],
    ];

    /**
     * Public procurement and retail benchmarks for the seeded demonstration catalogue.
     * Keys are the same ingredient and strength pairs used by DefenseDatasetSeeder.
     * Values are VND per dispensing unit; the catalog note documents approximations.
     */
    private const DEFENSE_UNIT_PRICES = [
        'paracetamol|120 mg/5 ml' => 20000,
        'amoxicillin|500 mg' => 1449,
        'azithromycin|500 mg' => 2600,
        'doxycycline|100 mg' => 500,
        'cephalexin|500 mg' => 2750,
        'ciprofloxacin|500 mg' => 950,
        'metronidazole|500 mg' => 500,
        'acyclovir|400 mg' => 1200,
        'fluconazole|150 mg' => 4500,
        'albendazole|400 mg' => 1000,
        'losartan potassium|100 mg' => 310,
        'amlodipine|5 mg' => 565,
        'amlodipine|10 mg' => 686,
        'enalapril maleate|5 mg' => 300,
        'hydrochlorothiazide|25 mg' => 300,
        'atorvastatin|20 mg' => 600,
        'simvastatin|20 mg' => 400,
        'metformin hydrochloride|850 mg' => 700,
        'gliclazide|30 mg' => 700,
        'levothyroxine sodium|50 microgam' => 300,
        'pantoprazole|40 mg' => 900,
        'famotidine|20 mg' => 400,
        'domperidone|10 mg' => 300,
        'glucose + natri clorid + kali clorid + natri citrat|gói pha dung dịch' => 1500,
        'ondansetron|4 mg' => 2000,
        'loratadine|10 mg' => 400,
        'salbutamol|100 microgam/liều' => 75000,
        'budesonide|200 microgam/liều' => 150000,
        'prednisolone|5 mg' => 300,
        'clotrimazole|1%' => 12000,
        'miconazole|2%' => 16000,
        'ibuprofen|400 mg' => 500,
        'diclofenac diethylamine|1%' => 15000,
        'acid acetylsalicylic|81 mg' => 450,
        'clopidogrel|75 mg' => 900,
        'warfarin sodium|2 mg' => 500,
        'insulin người|100 đơn vị/ml' => 120000,
        'sắt sulfat|200 mg' => 400,
        'acid folic|5 mg' => 200,
        'vitamin d3|1000 đơn vị quốc tế' => 600,
        'calci carbonat|500 mg' => 600,
        'natri clorid|0,9%' => 8000,
        'chlorhexidine gluconate|0,05%' => 12000,
        'lidocaine hydrochloride|2%' => 2000,
        'epinephrine|1 mg/ml' => 2000,
        'furosemide|40 mg' => 300,
        'spironolactone|25 mg' => 400,
        'bisoprolol fumarate|2,5 mg' => 500,
        'glyceryl trinitrate|0,5 mg' => 3000,
        'natri clorid + kali clorid + calci clorid + natri lactat|dung dịch truyền' => 7392,
        'gentamicin|0,3%' => 8000,
        'carboxymethylcellulose sodium|0,5%' => 35000,
        'betamethasone valerate|0,1%' => 25000,
        'loperamide hydrochloride|2 mg' => 300,
        'lactulose|10 g/15 ml' => 25000,
        'docusate sodium|100 mg' => 600,
        'thiamine hydrochloride|100 mg' => 200,
        'vitamin b1 + b6 + b12|hàm lượng phối hợp' => 500,
        'magnesium sulfate|50%' => 1500,
        'dexamethasone|4 mg/ml' => 1200,
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            foreach (self::CORE_PRICES as $code => [$name, $strength, $unit, $price, $unitsPerBlister, $unitsPerBox, $evidence]) {
                $medicine = DB::table('medicines')
                    ->where('medicine_code', $code)
                    ->where('name', $name)
                    ->where('strength', $strength)
                    ->where('unit', $unit)
                    ->lockForUpdate()
                    ->first();

                if ($medicine === null) {
                    continue;
                }

                $this->writePrices($medicine, $price, $unitsPerBlister, $unitsPerBox,
                    "Giá tham khảo demo 11/2026; benchmark: {$evidence}. Xem docs/THAM_KHAO_NHOM_BENH_VA_GIA_THUOC.md.");
            }

            $unpriced = [];
            $medicines = DB::table('medicines')->where('medicine_code', 'like', 'TH9%')->lockForUpdate()->get();
            foreach ($medicines as $medicine) {
                $key = mb_strtolower(trim((string) $medicine->active_ingredient), 'UTF-8')
                    .'|'.mb_strtolower(trim((string) $medicine->strength), 'UTF-8');
                $price = self::DEFENSE_UNIT_PRICES[$key] ?? null;
                if ($price === null) {
                    $unpriced[] = $medicine->medicine_code.' ('.$key.')';
                    continue;
                }

                $unitsPerBlister = $medicine->unit === 'viên' ? 10 : null;
                $unitsPerBox = match ($medicine->unit) {
                    'viên' => 100,
                    'gói' => 20,
                    default => null,
                };
                $this->writePrices($medicine, $price, $unitsPerBlister, $unitsPerBox,
                    'Giá tham khảo demo 11/2026; tham chiếu hoạt chất/hàm lượng và đơn vị cấp phát tương ứng, không phải giá kê khai của một biệt dược cụ thể. Xem docs/THAM_KHAO_NHOM_BENH_VA_GIA_THUOC.md.');
            }

            if ($unpriced !== []) {
                throw new RuntimeException('Thiếu giá tham khảo cho thuốc demo: '.implode(', ', $unpriced));
            }
        });
    }

    private function writePrices(object $medicine, int $price, ?int $unitsPerBlister, ?int $unitsPerBox, string $note): void
    {
        $changes = [];
        $unitPrice = (int) ($medicine->price_per_unit ?? $price);

        if ($medicine->price_per_unit === null) {
            $changes['price_per_unit'] = $price;
        }
        if ($medicine->price_per_blister === null && $unitsPerBlister !== null) {
            $changes['price_per_blister'] = $unitPrice * $unitsPerBlister;
        }
        if ($medicine->price_per_box === null && $unitsPerBox !== null) {
            $changes['price_per_box'] = $unitPrice * $unitsPerBox;
        }
        if ($medicine->price_per_unit === null
            && ($medicine->notes === null || trim((string) $medicine->notes) === '')
            && $note !== '') {
            $changes['notes'] = $note;
        }
        if ($changes !== []) {
            $changes['updated_at'] = now();
            DB::table('medicines')->where('id', $medicine->id)->update($changes);
        }
    }
}
