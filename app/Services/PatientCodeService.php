<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/** Reserve a patient code while holding the shared sequence lock in a transaction. */
class PatientCodeService
{
    public function next(): string
    {
        return DB::transaction(function (): string {
            DB::table('business_code_sequences')->insertOrIgnore([
                'scope' => 'patient_account_code', 'last_value' => 0, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $sequence = DB::table('business_code_sequences')->where('scope', 'patient_account_code')->lockForUpdate()->firstOrFail();
            $maximum = (int) $sequence->last_value;
            foreach (['users' => 'business_code', 'patients' => 'patient_code'] as $table => $column) {
                DB::table($table)->select($column)->orderBy($column)->each(function ($row) use ($column, &$maximum): void {
                    if (preg_match('/^BN(\d+)$/', (string) $row->{$column}, $match)) $maximum = max($maximum, (int) $match[1]);
                });
            }
            $number = $maximum + 1;
            DB::table('business_code_sequences')->where('id', $sequence->id)->update(['last_value' => $number, 'updated_at' => now()]);
            return 'BN'.str_pad((string) $number, 6, '0', STR_PAD_LEFT);
        }, 3);
    }
}
