<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class BusinessCodeGenerator
{
    public const CATALOGS = [
        'specialty_code' => [Specialty::class, 'CK'],
        'room_code' => [ExaminationRoom::class, 'PK'],
        'service_group_code' => [ServiceGroup::class, 'NDV'],
        'service_code' => [MedicalService::class, 'DV'],
        'disease_group_code' => [DiseaseGroup::class, 'NB'],
        'disease_code' => [Disease::class, 'B'],
        'medicine_code' => [Medicine::class, 'TH'],
    ];

    public function preview(string $field): string
    {
        [$model, $prefix] = $this->catalog($field);
        $table = (new $model)->getTable();
        $reserved = (int) DB::table('business_code_sequences')->where('scope', $field)->value('last_value');

        return $this->format($prefix, max($reserved, $this->maximum($table, $field)) + 1);
    }

    public function next(string $field): string
    {
        [$model, $prefix] = $this->catalog($field);
        $table = (new $model)->getTable();

        return DB::transaction(function () use ($field, $table, $prefix): string {
            DB::table('business_code_sequences')->insertOrIgnore([
                'scope' => $field, 'last_value' => 0, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $sequence = DB::table('business_code_sequences')->where('scope', $field)->lockForUpdate()->first();
            $number = max((int) $sequence->last_value, $this->maximum($table, $field)) + 1;
            $code = $this->format($prefix, $number);
            DB::table('business_code_sequences')->where('id', $sequence->id)->update(['last_value' => $number, 'updated_at' => now()]);

            return $code;
        }, 3);
    }

    /** @return array{class-string<Model>, string} */
    private function catalog(string $field): array
    {
        return self::CATALOGS[$field] ?? throw new \InvalidArgumentException('Unknown business-code scope.');
    }

    private function maximum(string $table, string $field): int
    {
        if (DB::getDriverName() === 'mysql') {
            $value = DB::table($table)->selectRaw('COALESCE(MAX(CAST(REGEXP_SUBSTR(`'.$field.'`, ?) AS UNSIGNED)), 0) AS maximum', ['[0-9]+$'])->first()->maximum;
            if (strlen(ltrim((string) $value, '0')) > 18) {
                throw new \RuntimeException('Không thể cấp mã mới vì mã hiện có vượt giới hạn hệ thống. Vui lòng báo người phụ trách hệ thống.');
            }

            return (int) $value;
        }

        $maximum = 0;
        DB::table($table)->select('id', $field)->orderBy('id')->chunkById(1000, function ($rows) use ($field, &$maximum): void {
            foreach ($rows as $row) {
                if (preg_match('/([0-9]+)$/', (string) $row->{$field}, $matches)) {
                    if (strlen(ltrim($matches[1], '0')) > 18) {
                        throw new \RuntimeException('Không thể cấp mã mới vì mã hiện có vượt giới hạn hệ thống. Vui lòng báo người phụ trách hệ thống.');
                    }
                    $maximum = max($maximum, (int) $matches[1]);
                }
            }
        });

        return $maximum;
    }

    private function format(string $prefix, int $number): string
    {
        return $prefix.str_pad((string) $number, 6, '0', STR_PAD_LEFT);
    }
}
