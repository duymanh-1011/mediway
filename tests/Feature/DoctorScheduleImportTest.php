<?php

namespace Tests\Feature;

use App\Models\DoctorSchedule;
use App\Models\User;
use App\Services\DoctorMonthlyScheduleService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;
use ZipArchive;

class DoctorScheduleImportTest extends TestCase
{
    use RefreshDatabase;

    private DoctorSchedule $sample;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail());
        $this->sample = DoctorSchedule::whereNotNull('specialty_id')->firstOrFail();
    }

    public function test_admin_can_browse_month_and_save_one_shift(): void
    {
        $doctor = $this->sample->doctor;
        $this->get(route('admin.doctor-schedules.month.index', ['keyword' => $doctor->doctor_code]))->assertOk()->assertSee($doctor->full_name);
        $this->get(route('admin.doctor-schedules.month.show', [$doctor, 'month' => '2090-02']))->assertOk()->assertSee('28/02/2090')->assertSee('Chưa cấu hình');
        $this->put(route('admin.doctor-schedules.month.update', $doctor), [
            'date' => '2090-02-01', 'shift' => 'MORNING', 'state' => 'OFF',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('doctor_schedules', ['doctor_id' => $doctor->id, 'shift_code' => 'MORNING', 'status' => 'INACTIVE']);
        $standard = $doctor->schedules()->whereDate('work_date', '2090-02-01')->where('shift_code', 'MORNING')->sole();
        $this->get(route('admin.doctor-schedules.index', ['work_date' => '2090-02-01']))
            ->assertOk()->assertViewHas('doctorSchedules', fn ($rows) => $rows->isEmpty());
        $this->get(route('admin.doctor-schedules.show', $standard))
            ->assertOk()->assertSee('Quản lý lịch tháng')->assertDontSee('Thêm khung giờ');
    }

    public function test_preview_reuses_assignments_and_does_not_write_until_confirmed(): void
    {
        $this->requiresZip();
        $doctor = $this->sample->doctor;
        $service = app(DoctorMonthlyScheduleService::class);
        $saved = $service->updateShift(auth()->user(), $doctor->id, '2090-02-01', 'MORNING', 'WORKING', null, $this->sample->specialty_id, $this->sample->examination_room_id);
        $before = DoctorSchedule::count();
        $preview = $this->upload([['2090-02-01', 'LÀM', 'NGHỈ'], ['04/02/2090', 'LÀM', 'NGHỈ']]);
        $page = $this->get($preview)->assertOk()->assertDontSee('Xác nhận nhập lịch');
        $this->assertArrayNotHasKey(0, $page->viewData('issues'));
        $this->assertSame($before, DoctorSchedule::count());
        $token = basename($preview);
        $this->put(route('admin.doctor-schedules.month.import.assignments', [$doctor, $token]), [
            'assignments' => [2 => ['specialty_id' => $this->sample->specialty_id, 'room_id' => $this->sample->examination_room_id]],
        ])->assertRedirect($preview);
        $revision = $this->get($preview)->assertOk()->assertSee('Xác nhận nhập lịch')->viewData('revision');
        $this->assertSame($before, DoctorSchedule::count());
        $this->post(route('admin.doctor-schedules.month.import.confirm', [$doctor, $token]), ['revision' => $revision])->assertSessionHasNoErrors();
        $this->assertSame($before + 3, DoctorSchedule::count());
        $this->assertSame($saved->id, DoctorSchedule::where('doctor_id', $doctor->id)->whereDate('work_date', '2090-02-01')->where('shift_code', 'MORNING')->sole()->id);
        $this->assertFalse(DoctorSchedule::whereDate('work_date', '2090-02-02')->exists());
        $this->get($preview)->assertNotFound();

        $secondPreview = $this->upload([['2090-02-01', 'LÀM', 'NGHỈ'], ['04/02/2090', 'LÀM', 'NGHỈ']]);
        $secondRevision = $this->get($secondPreview)->assertOk()->viewData('revision');
        $this->post(route('admin.doctor-schedules.month.import.confirm', [$doctor, basename($secondPreview)]), [
            'revision' => $secondRevision,
        ])->assertSessionHasNoErrors();
        $this->assertSame($before + 3, DoctorSchedule::count());
        $this->assertSame(8, $saved->appointmentTimeSlots()->count());
    }

    public function test_confirm_rolls_back_earlier_rows_when_a_later_cell_has_changed(): void
    {
        $this->requiresZip();
        $doctor = $this->sample->doctor;
        $preview = $this->upload([['2090-02-01', 'NGHỈ', 'NGHỈ'], ['2090-02-02', 'NGHỈ', 'NGHỈ']]);
        $revision = $this->get($preview)->assertOk()->viewData('revision');
        app(DoctorMonthlyScheduleService::class)->updateShift(auth()->user(), $doctor->id, '2090-02-02', 'AFTERNOON', 'OFF', null);
        $this->post(route('admin.doctor-schedules.month.import.confirm', [$doctor, basename($preview)]), ['revision' => $revision])
            ->assertRedirect($preview)->assertSessionHasErrors('workbook');
        $this->assertSame(1, DoctorSchedule::whereNotNull('shift_code')->count());
        $this->assertFalse(DoctorSchedule::whereDate('work_date', '2090-02-01')->exists());
    }

    public function test_two_admins_with_the_same_preview_cannot_create_duplicate_shifts(): void
    {
        $this->requiresZip();
        $firstAdmin = auth()->user();
        $secondAdmin = $firstAdmin->replicate();
        $secondAdmin->fill(['business_code' => 'QT000099', 'phone' => '0901000099'])->save();
        $administrator = $firstAdmin->administrator->replicate();
        $secondAdmin->administrator()->save($administrator);

        $doctor = $this->sample->doctor;
        $rows = [['2090-02-01', 'NGHỈ', 'NGHỈ']];
        $firstPreview = $this->upload($rows);
        $firstRevision = $this->get($firstPreview)->assertOk()->viewData('revision');
        $this->actingAs($secondAdmin)->get($firstPreview)->assertNotFound();
        $secondPreview = $this->upload($rows);
        $secondRevision = $this->get($secondPreview)->assertOk()->viewData('revision');

        $this->actingAs($firstAdmin)->post(route('admin.doctor-schedules.month.import.confirm', [$doctor, basename($firstPreview)]), [
            'revision' => $firstRevision,
        ])->assertSessionHasNoErrors();
        $this->actingAs($secondAdmin)->post(route('admin.doctor-schedules.month.import.confirm', [$doctor, basename($secondPreview)]), [
            'revision' => $secondRevision,
        ])->assertSessionHasErrors('workbook');
        $this->assertSame(2, $doctor->schedules()->whereDate('work_date', '2090-02-01')->count());
    }

    public function test_invalid_dates_status_duplicates_and_formula_are_rejected(): void
    {
        $this->requiresZip();
        $this->post(route('admin.doctor-schedules.month.import.preview', $this->sample->doctor), [
            'month' => '2090-02', 'workbook' => UploadedFile::fake()->createWithContent('lich.xlsx', 'Ngày,Ca sáng,Ca chiều'),
        ])->assertSessionHasErrors('workbook');
        foreach ([
            [['2090-03-01', 'NGHỈ', 'LÀM']],
            [['2090-02-30', 'NGHỈ', 'LÀM']],
            [['2090-02-01', 'SAI', 'LÀM']],
            [['2090-02-01', 'NGHỈ', 'LÀM'], ['2090-02-01', 'NGHỈ', 'LÀM']],
            [['2090-02-01', '=1+1', 'LÀM']],
        ] as $rows) {
            $this->post(route('admin.doctor-schedules.month.import.preview', $this->sample->doctor), [
                'month' => '2090-02', 'workbook' => $this->workbook($rows),
            ])->assertSessionHasErrors('workbook');
        }
        $this->assertFalse(DoctorSchedule::whereNotNull('shift_code')->exists());
    }

    private function upload(array $rows): string
    {
        return $this->post(route('admin.doctor-schedules.month.import.preview', $this->sample->doctor), [
            'month' => '2090-02', 'workbook' => $this->workbook($rows),
        ])->assertSessionHasNoErrors()->assertRedirect()->headers->get('Location');
    }

    private function workbook(array $rows): UploadedFile
    {
        $book = new Spreadsheet;
        $book->getActiveSheet()->fromArray([['Ngày', 'Ca sáng', 'Ca chiều'], ...$rows]);
        $file = UploadedFile::fake()->create('lich.xlsx');
        (new Xlsx($book))->save($file->getPathname());
        $book->disconnectWorksheets();

        return $file;
    }

    private function requiresZip(): void
    {
        if (! class_exists(ZipArchive::class)) {
            $this->markTestSkipped('Cần bật extension zip để kiểm thử XLSX.');
        }
    }
}
