<?php

namespace Tests\Feature;

use App\Models\MedicalService;
use App\Models\ServiceGroup;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DataConstraintsTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_tables_have_primary_keys_and_do_not_cascade_delete_history(): void
    {
        $tables = ['roles', 'users', 'administrators', 'patients', 'patient_details', 'personal_health_records', 'doctors', 'doctor_details', 'employees', 'employee_details', 'specialties', 'examination_rooms', 'service_groups', 'medical_services', 'disease_groups', 'diseases', 'medicines', 'appointment_statuses', 'doctor_schedules', 'appointment_time_slots', 'appointments', 'visit_statuses', 'queue_statuses', 'medical_visits', 'examination_room_queues', 'visit_clinical_notes', 'visit_diagnoses', 'visit_conclusions', 'service_statuses', 'payment_statuses', 'service_orders', 'service_order_items', 'service_results', 'prescriptions', 'prescription_items', 'payables', 'payment_transactions', 'audit_logs', 'service_group_medical_service'];
        $this->assertCount(39, $tables);
        foreach ($tables as $table) {
            $primary = array_values(array_filter(Schema::getIndexes($table), fn (array $index): bool => $index['primary']));
            $this->assertSame(['id'], $primary[0]['columns'] ?? null, $table);
            foreach (Schema::getForeignKeys($table) as $foreign) {
                $this->assertNotSame('cascade', strtolower($foreign['on_delete']), $table.': '.implode(',', $foreign['columns']));
            }
        }
    }

    public function test_one_to_one_and_business_unique_indexes_are_present(): void
    {
        foreach ([
            ['medical_visits', ['appointment_id']], ['visit_clinical_notes', ['medical_visit_id']],
            ['visit_conclusions', ['medical_visit_id']], ['prescriptions', ['medical_visit_id']],
            ['service_results', ['service_order_item_id']], ['payables', ['service_order_item_id']],
            ['appointment_statuses', ['name']], ['appointment_time_slots', ['doctor_schedule_id', 'start_time', 'end_time']],
            ['medical_services', ['name']],
            ['service_order_items', ['service_order_id', 'medical_service_id']],
            ['visit_diagnoses', ['medical_visit_id', 'disease_id', 'diagnosis_type']],
            ['service_group_medical_service', ['service_group_id', 'medical_service_id']],
        ] as [$table, $columns]) {
            $this->assertTrue(Schema::hasIndex($table, $columns, 'unique'), $table.': '.implode(',', $columns));
        }
        $this->assertFalse(Schema::hasColumn('medical_services', 'service_group_id'));
    }

    public function test_catalog_status_defaults_are_active_strings(): void
    {
        foreach (['specialties', 'examination_rooms', 'service_groups', 'medical_services', 'disease_groups', 'diseases', 'medicines'] as $table) {
            $columns = array_column(Schema::getColumns($table), null, 'name');
            $this->assertSame('ACTIVE', trim((string) $columns['status']['default'], "'\""), $table);
            $this->assertFalse($columns['status']['nullable']);
        }
    }

    public function test_duplicate_pivot_links_are_rejected_by_database(): void
    {
        $group = ServiceGroup::create(['service_group_code' => 'TEST', 'name' => 'Nhóm thử']);
        $service = MedicalService::create(['service_code' => 'TEST', 'name' => 'Dịch vụ thử', 'price' => 1000]);
        $service->serviceGroups()->attach($group);
        $this->expectException(QueryException::class);
        $service->serviceGroups()->attach($group);
    }

    public function test_database_rejects_duplicate_service_names_even_with_different_codes(): void
    {
        MedicalService::create(['service_code' => 'NAME001', 'name' => 'Dịch vụ không trùng tên', 'price' => 1000]);
        $this->expectException(QueryException::class);
        MedicalService::create(['service_code' => 'NAME002', 'name' => 'Dịch vụ không trùng tên', 'price' => 2000]);
    }

    public function test_mysql_enforces_service_price_check_for_direct_sql_writes(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('ALTER ADD CHECK được xác minh trong suite MySQL riêng.');
        }
        $service = MedicalService::create(['service_code' => 'PRICECHECK', 'name' => 'Kiểm tra giá', 'price' => 1000]);
        $this->expectException(QueryException::class);
        DB::table('medical_services')->where('id', $service->id)->update(['price' => 1500]);
    }
}
