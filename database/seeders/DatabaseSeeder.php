<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            AdministratorSeeder::class,

            PatientSeeder::class,
            PatientDetailSeeder::class,
            PersonalHealthRecordSeeder::class,

            DoctorSeeder::class,
            DoctorDetailSeeder::class,

            EmployeeSeeder::class,
            EmployeeDetailSeeder::class,
            SpecialtySeeder::class,

            ExaminationRoomSeeder::class,
            ServiceGroupSeeder::class,
            MedicalServiceSeeder::class,
            DiseaseGroupSeeder::class,
            DiseaseSeeder::class,
            MedicineSeeder::class,
            AppointmentStatusSeeder::class,
            DoctorScheduleSeeder::class,
            AppointmentTimeSlotSeeder::class,
            AppointmentSeeder::class,

            VisitStatusSeeder::class,
            QueueStatusSeeder::class,
            MedicalVisitSeeder::class,
            ExaminationRoomQueueSeeder::class,
            VisitClinicalNoteSeeder::class,
            VisitDiagnosisSeeder::class,
            VisitConclusionSeeder::class,
        ]);
    }
}
