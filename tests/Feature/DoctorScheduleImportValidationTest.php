<?php

namespace Tests\Feature;

use App\Models\DoctorSchedule;
use App\Services\DoctorScheduleImportService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DoctorScheduleImportValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(DatabaseSeeder::class)->setContainer($this->app)->__invoke(['includePracticeData' => false]);
    }

    public function test_invalid_shift_and_missing_doctor_never_create_schedules(): void
    {
        $count = DoctorSchedule::count();
        $date = today()->addDays(21)->format('d/m/Y');
        $rows = [
            2 => ['BS000001', $date, 'Tối', 'CKTMH', 'PKTMH01', ''],
            3 => ['KHONGTONTAI', $date, 'Sáng', 'CKTMH', 'PKTMH01', ''],
        ];
        try {
            app(DoctorScheduleImportService::class)->import($rows);
            $this->fail('File có dữ liệu sai phải bị từ chối.');
        } catch (ValidationException $exception) {
            $messages = implode(' ', $exception->errors()['file']);
            $this->assertStringContainsString('Dòng 2', $messages);
            $this->assertStringContainsString('Dòng 3', $messages);
        }
        $this->assertSame($count, DoctorSchedule::count());
    }

    public function test_duplicate_row_rolls_back_all_rows(): void
    {
        $count = DoctorSchedule::count();
        $date = today()->addDays(22)->format('d/m/Y');
        $rows = [
            2 => ['BS000001', $date, 'Sáng', 'CKTMH', 'PKTMH01', ''],
            3 => ['BS000001', $date, 'Sáng', 'CKTMH', 'PKTMH01', ''],
        ];
        try {
            app(DoctorScheduleImportService::class)->import($rows);
            $this->fail('Lịch bị trùng phải được từ chối.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('Dòng 3', implode(' ', $exception->errors()['file']));
        }
        $this->assertSame($count, DoctorSchedule::count());
    }
}
