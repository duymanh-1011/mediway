<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DoctorScheduleUnlimitedMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_preserves_existing_slots_and_appointment_links(): void
    {
        $this->app->make(DatabaseSeeder::class)->setContainer($this->app)->__invoke(['includePracticeData' => true]);
        $before = DB::table('appointment_time_slots')->orderBy('id')->get(['id', 'doctor_schedule_id', 'start_time', 'end_time'])->toArray();
        $appointments = DB::table('appointments')->orderBy('id')->get(['id', 'appointment_time_slot_id'])->toArray();
        $this->assertNotEmpty($before);
        $this->assertNotEmpty($appointments);
        $slotId = $before[0]->id;
        DB::table('appointment_time_slots')->where('id', $slotId)->update(['status' => 'FULL']);
        (require database_path('migration-proposals/2026_11_10_000000_make_appointment_slots_unlimited.php'))->up();
        $this->assertEquals($before, DB::table('appointment_time_slots')->orderBy('id')->get(['id', 'doctor_schedule_id', 'start_time', 'end_time'])->toArray());
        $this->assertEquals($appointments, DB::table('appointments')->orderBy('id')->get(['id', 'appointment_time_slot_id'])->toArray());
        $this->assertSame(0, DB::table('appointment_time_slots')->whereNotNull('max_patients')->count());
        $this->assertSame('ACTIVE', DB::table('appointment_time_slots')->where('id', $slotId)->value('status'));
        $this->assertSame([], DB::select('PRAGMA foreign_key_check'));
    }
}
