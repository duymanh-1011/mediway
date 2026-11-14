<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Support\Portal\BookingProfileGate;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CleanDatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_seed_creates_reference_data_and_complete_profiles_without_operational_records(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach ([
            'personal_health_records', 'doctor_schedules', 'appointment_time_slots', 'appointments',
            'medical_visits', 'examination_room_queues', 'visit_clinical_notes', 'visit_diagnoses',
            'visit_conclusions', 'service_orders', 'service_order_items', 'service_results',
            'prescriptions', 'prescription_items', 'payables', 'payment_transactions', 'audit_logs',
        ] as $table) {
            $this->assertDatabaseCount($table, 0);
        }

        $this->assertDatabaseCount('patients', 10);
        $this->assertDatabaseCount('doctors', 6);
        $this->assertDatabaseCount('employees', 8);
        foreach (['patient' => 'patients', 'doctor' => 'doctors', 'employee' => 'employees'] as $person => $table) {
            $profiles = DB::table($table)->join("{$person}_details as details", "{$table}.id", '=', "details.{$person}_id")
                ->get(['date_of_birth', 'gender', 'email', 'current_address', 'permanent_address']);

            $this->assertCount(DB::table($table)->count(), $profiles);
            foreach ($profiles as $profile) {
                $this->assertNotEmpty($profile->date_of_birth);
                $this->assertContains($profile->gender, ['Nam', 'Nữ']);
                $this->assertMatchesRegularExpression('/^[a-z]+(?:\.[a-z]+)+@mediway\.test$/', $profile->email);
                $this->assertMatchesRegularExpression('/^Số \d+ .+, Hà Nội$/u', $profile->current_address);
                $this->assertNotEmpty($profile->permanent_address);
            }
        }

        foreach (Patient::with(['user', 'detail'])->get() as $patient) {
            $this->assertSame([], app(BookingProfileGate::class)->missingFields($patient));
        }

        $this->assertDatabaseHas('employee_details', ['email' => 'nguyen.thi.lan@mediway.test']);
        $this->assertDatabaseHas('doctor_details', ['email' => 'nguyen.van.binh@mediway.test']);
        $this->assertDatabaseHas('patient_details', ['email' => 'nguyen.minh.an@mediway.test']);
        $this->assertDatabaseHas('specialties', ['specialty_code' => 'CKTMH']);
        $this->assertDatabaseHas('examination_rooms', ['room_code' => 'PKTMH01']);
        $this->assertDatabaseHas('service_groups', ['service_group_code' => 'SA']);
        $this->assertDatabaseHas('medical_services', ['service_code' => 'DVCD003']);
        $this->assertNotNull(DB::table('medical_services')->where('service_code', 'DVCD003')->value('service_result_template_id'));
        $this->assertDatabaseHas('medicines', ['medicine_code' => 'TH001', 'price_per_unit' => 360]);
        $this->assertDatabaseHas('doctor_shift_settings', ['id' => 1]);
    }
}
