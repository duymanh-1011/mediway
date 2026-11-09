<?php

namespace App\Services;

use App\Models\ExaminationRoomQueue;
use App\Models\MedicalVisit;
use Illuminate\Support\Facades\DB;

final class ExaminationQueueNumber
{
    /** The caller holds the visit lock or has just created the visit in this transaction. */
    public function reserve(MedicalVisit $visit): int
    {
        if ($number = $visit->issuedQueueNumber()) {
            return $number;
        }

        $day = $visit->checked_in_at?->toDateString() ?? today()->toDateString();
        $roomId = $visit->examination_room_id;
        $scope = 'exam_queue:'.$roomId.':'.$day;
        $sequences = DB::table('business_code_sequences');
        $counter = (clone $sequences)->where('scope', $scope);

        if (! $counter->exists()) {
            // Seed the first allocation from numbers already issued before the counter existed.
            $legacyMaximum = ExaminationRoomQueue::whereDate('queued_at', $day)
                ->whereHas('medicalVisit', fn ($query) => $query->where('examination_room_id', $roomId)->whereNull('reception_queue_number'))
                ->max('queue_number');
            $reservedMaximum = MedicalVisit::where('examination_room_id', $roomId)->whereDate('checked_in_at', $day)->max('reception_queue_number');
            $sequences->insertOrIgnore([
                'scope' => $scope, 'last_value' => max((int) $legacyMaximum, (int) $reservedMaximum), 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $number = (int) $counter->lockForUpdate()->firstOrFail()->last_value + 1;
        $counter->update(['last_value' => $number, 'updated_at' => now()]);
        $visit->update(['reception_queue_number' => $number]);

        return $number;
    }
}
