<?php

namespace Tests\Fixtures\Seeders;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WorkflowFixtureSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(bool $includePracticeData = false): void
    {
        $this->callWith(DatabaseSeeder::class, ['includeExtraAccounts' => $includePracticeData]);

        $this->call(PersonalHealthRecordSeeder::class);
        $this->callWith(DoctorScheduleSeeder::class, ['includePracticeData' => $includePracticeData]);
        $this->callWith(AppointmentTimeSlotSeeder::class, ['includePracticeData' => $includePracticeData]);
        $this->call([
            AppointmentSeeder::class,
            MedicalVisitSeeder::class,
            ExaminationRoomQueueSeeder::class,
            VisitClinicalNoteSeeder::class,
            VisitDiagnosisSeeder::class,
            VisitConclusionSeeder::class,
            ServiceOrderSeeder::class,
            ServiceOrderItemSeeder::class,
            ServiceResultSeeder::class,
            PrescriptionSeeder::class,
            PrescriptionItemSeeder::class,
            PayableSeeder::class,
            PaymentTransactionSeeder::class,
            AuditLogSeeder::class,
        ]);

        if ($includePracticeData) {
            $this->call(TechnicalServicePracticeSeeder::class);
        }
    }
}
