<?php

namespace App\Support\Portal;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ClinicalPublication
{
    public static function restrict($query, string $table)
    {
        if (! Schema::hasColumn($table, 'patient_publication_status')) {
            Log::error('Patient clinical publication column is missing; access is denied.', ['table' => $table]);
            return $query->whereRaw('1 = 0');
        }

        // The patient-facing states were explicitly approved in the project conversation.
        return $query->whereIn($table.'.patient_publication_status', ['PUBLIC', 'PUBLISHED']);
    }
}
