<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class AdminLookup
{
    public const TYPES = [
        'diseases' => [Disease::class, 'disease_code', 'name'],
        'doctors' => [Doctor::class, 'doctor_code', 'full_name'],
        'patients' => [Patient::class, 'patient_code', 'full_name'],
        'specialties' => [Specialty::class, 'specialty_code', 'name'],
        'rooms' => [ExaminationRoom::class, 'room_code', 'name'],
        'service-groups' => [ServiceGroup::class, 'service_group_code', 'name'],
        'disease-groups' => [DiseaseGroup::class, 'disease_group_code', 'name'],
        'medical-services' => [MedicalService::class, 'service_code', 'name'],
        'medicines' => [Medicine::class, 'medicine_code', 'name'],
    ];

    public const FIELDS = [
        'disease_id' => 'diseases', 'doctor_id' => 'doctors', 'patient_id' => 'patients',
        'specialty_id' => 'specialties', 'examination_room_id' => 'rooms',
        'group_id' => 'service-groups', 'service_group_id' => 'service-groups',
        'disease_group_id' => 'disease-groups', 'medical_service_id' => 'medical-services', 'medicine_id' => 'medicines',
    ];

    /** Keep the selected record visible without loading a whole catalog into HTML. */
    public static function initial(Builder $query, mixed $selected = null): Collection
    {
        $options = (clone $query)->limit(20)->get();
        if (is_scalar($selected) && ctype_digit((string) $selected) && ! $options->contains('id', (int) $selected)) {
            $record = (clone $query)->whereKey((int) $selected)->first();
            if ($record) {
                $options->push($record);
            }
        }

        return $options;
    }
}
