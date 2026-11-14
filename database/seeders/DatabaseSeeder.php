<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(?bool $includeExtraAccounts = null): void
    {
        $includeExtraAccounts ??= app()->environment(['local', 'testing']);
        $accounts = ['includeExtraAccounts' => $includeExtraAccounts];

        $this->call(RoleSeeder::class);
        $this->callWith(UserSeeder::class, $accounts);
        $this->call(AdministratorSeeder::class);
        $this->callWith(PatientSeeder::class, $accounts);
        $this->callWith(PatientDetailSeeder::class, $accounts);
        $this->call(SpecialtySeeder::class);
        $this->callWith(DoctorSeeder::class, $accounts);
        $this->callWith(DoctorDetailSeeder::class, $accounts);
        $this->call([EmployeeSeeder::class, EmployeeDetailSeeder::class]);

        if ($includeExtraAccounts) {
            $this->call(AdditionalEmployeeSeeder::class);
        }

        $this->call([
            ExaminationRoomSeeder::class,
            ServiceGroupSeeder::class,
            MedicalServiceSeeder::class,
            ServiceResultTemplateSeeder::class,
            DiseaseGroupSeeder::class,
            DiseaseSeeder::class,
            MedicineSeeder::class,
            ReferenceMedicinePriceSeeder::class,
            AppointmentStatusSeeder::class,
            VisitStatusSeeder::class,
            QueueStatusSeeder::class,
            ServiceStatusSeeder::class,
            PaymentStatusSeeder::class,
        ]);
    }
}
