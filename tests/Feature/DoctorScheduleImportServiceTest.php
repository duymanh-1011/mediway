<?php

namespace Tests\Feature;

use App\Models\DoctorSchedule;
use App\Services\DoctorScheduleImportService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\UploadedFile;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class DoctorScheduleImportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        if (! class_exists(\ZipArchive::class)) $this->markTestSkipped('Cần bật PHP ZIP để kiểm thử file Excel.');
        $this->app->make(DatabaseSeeder::class)->setContainer($this->app)->__invoke(['includePracticeData' => false]);
    }

    public function test_imports_two_shifts_and_rolls_back_if_a_line_conflicts(): void
    {
        $service = app(DoctorScheduleImportService::class);
        $day = today()->addDays(9)->format('d/m/Y');
        $data = [2 => ['BS000001', $day, 'Sáng', 'CKTMH', 'PKTMH01', ''], 3 => ['BS000001', $day, 'Chiều', 'CKTMH', 'PKTMH01', '']];
        $this->assertSame(['schedules' => 2, 'slots' => 15], $service->import($data));
        $before = DoctorSchedule::count();
        try {
            $service->import([2 => ['BS000001', today()->addDays(10)->format('d/m/Y'), 'Sáng', 'CKTMH', 'PKTMH01', ''], 3 => $data[2]]);
            $this->fail('Lịch trùng phải được từ chối.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('Dòng 3', implode(' ', $exception->errors()['file']));
        }
        $this->assertSame($before, DoctorSchedule::count());
    }

    public function test_reads_xlsx_and_rejects_formulas(): void
    {
        $book = new Spreadsheet;
        $book->getActiveSheet()->fromArray([['Mã bác sĩ', 'Ngày làm việc', 'Ca', 'Mã chuyên khoa', 'Mã phòng', 'Ghi chú'], ['BS000001', '01/12/2026', 'Sáng', 'CKTMH', 'PKTMH01', '']]);
        $path = tempnam(sys_get_temp_dir(), 'mediway-xlsx-');
        try {
            (new Xlsx($book))->save($path);
            $this->assertSame('BS000001', app(DoctorScheduleImportService::class)->read($path)[2][0]);
            $book->getActiveSheet()->setCellValue('A2', '=1+1');
            (new Xlsx($book))->save($path);
            $this->expectException(ValidationException::class);
            app(DoctorScheduleImportService::class)->read($path);
        } finally {
            $book->disconnectWorksheets();
            @unlink($path);
        }
    }

    public function test_admin_imports_valid_xlsx_and_other_roles_are_blocked(): void
    {
        $book = app(DoctorScheduleImportService::class)->template();
        $book->getActiveSheet()->fromArray([['BS000001', today()->addDays(12)->format('d/m/Y'), 'Sáng', 'CKTMH', 'PKTMH01', '']], null, 'A2');
        $path = tempnam(sys_get_temp_dir(), 'mediway-test-');
        try {
            (new Xlsx($book))->save($path);
            $file = new UploadedFile($path, 'lich.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
            $response = $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail())
                ->post(route('admin.doctor-schedules.import'), ['file' => $file]);
            $this->assertNull(session('errors'), json_encode(session('errors')));
            $response->assertRedirect(route('admin.doctor-schedules.index'));
            $this->assertSame(9, DoctorSchedule::whereDate('work_date', today()->addDays(12))->firstOrFail()->appointmentTimeSlots()->count());
            $this->flushSession();
            $this->actingAs(User::where('business_code', 'BN000001')->firstOrFail())
                ->post(route('admin.doctor-schedules.import'), ['file' => $file])->assertRedirect(route('admin.login'));
            $this->assertSame(1, DoctorSchedule::whereDate('work_date', today()->addDays(12))->count());
        } finally {
            $book->disconnectWorksheets();
            @unlink($path);
        }
    }

    public function test_rejects_invalid_calendar_date_and_duplicate_rows_without_partial_writes(): void
    {
        $service = app(DoctorScheduleImportService::class);
        $before = DoctorSchedule::count();
        $valid = ['BS000001', today()->addDays(12)->format('d/m/Y'), 'Sáng', 'CKTMH', 'PKTMH01', ''];
        foreach ([
            [2 => $valid, 3 => ['BS000001', '31/02/2027', 'Sáng', 'CKTMH', 'PKTMH01', '']],
            [2 => $valid, 3 => $valid],
        ] as $rows) {
            try {
                $service->import($rows);
                $this->fail('Dòng sai hoặc trùng phải bị từ chối.');
            } catch (ValidationException $exception) {
                $this->assertStringContainsString('Dòng 3', implode(' ', $exception->errors()['file']));
            }
            $this->assertSame($before, DoctorSchedule::count());
        }
    }

    public function test_spreadsheet_reports_blank_rows_and_formulas_together(): void
    {
        $book = app(DoctorScheduleImportService::class)->template();
        $book->getActiveSheet()->fromArray([['BS000001', '01/12/2026', 'Sáng', 'CKTMH', 'PKTMH01', '']], null, 'A2');
        $book->getActiveSheet()->setCellValue('A4', '=1+1');
        $path = tempnam(sys_get_temp_dir(), 'mediway-xlsx-');
        try {
            (new Xlsx($book))->save($path);
            try {
                app(DoctorScheduleImportService::class)->read($path);
                $this->fail('Tệp có dòng trống và công thức phải bị từ chối.');
            } catch (ValidationException $exception) {
                $messages = implode(' ', $exception->errors()['file']);
                $this->assertStringContainsString('Dòng 3', $messages);
                $this->assertStringContainsString('Dòng 4', $messages);
            }
        } finally {
            $book->disconnectWorksheets();
            @unlink($path);
        }
    }
}
