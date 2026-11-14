<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(?bool $includePracticeData = null): void
    {
        $includePracticeData ??= app()->environment('local');
        $practice = ['includePracticeData' => $includePracticeData];

        $this->call(RoleSeeder::class);
        $this->callWith(UserSeeder::class, $practice);
        $this->call(AdministratorSeeder::class);
        $this->callWith(PatientSeeder::class, $practice);
        $this->callWith(PatientDetailSeeder::class, $practice);
        $this->call([PersonalHealthRecordSeeder::class, SpecialtySeeder::class]);
        $this->callWith(DoctorSeeder::class, $practice);
        $this->callWith(DoctorDetailSeeder::class, $practice);

        $this->call([
            EmployeeSeeder::class,
            EmployeeDetailSeeder::class,

            ExaminationRoomSeeder::class,
            ServiceGroupSeeder::class,
            MedicalServiceSeeder::class,
            ServiceResultTemplateSeeder::class,
            DiseaseGroupSeeder::class,
            DiseaseSeeder::class,
            MedicineSeeder::class,
            ReferenceMedicinePriceSeeder::class,
            AppointmentStatusSeeder::class,
        ]);
        $this->callWith(DoctorScheduleSeeder::class, $practice);
        $this->callWith(AppointmentTimeSlotSeeder::class, $practice);
        $this->call([
            AppointmentSeeder::class,

            VisitStatusSeeder::class,
            QueueStatusSeeder::class,
            MedicalVisitSeeder::class,
            ExaminationRoomQueueSeeder::class,
            VisitClinicalNoteSeeder::class,
            VisitDiagnosisSeeder::class,
            VisitConclusionSeeder::class,

            ServiceStatusSeeder::class,
            PaymentStatusSeeder::class,
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
            $this->call([EmployeeDemoExpansionSeeder::class, TechnicalServicePracticeSeeder::class]);
        }
    }
}
