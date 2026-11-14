<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\User;
use App\Services\DoctorMonthlyScheduleService;
use App\Support\Portal\PatientBookingWindow;
use Tests\Fixtures\Seeders\WorkflowFixtureSeeder;
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
        $this->seed(WorkflowFixtureSeeder::class);
        $this->withoutVite();
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail());
        $this->sample = DoctorSchedule::whereNotNull('specialty_id')->firstOrFail();
    }

    public function test_admin_can_browse_month_and_save_one_shift(): void
    {
        $doctor = $this->sample->doctor;
        $this->get(route('admin.doctor-schedules.month.index', ['keyword' => $doctor->doctor_code]))->assertOk()
            ->assertSee($doctor->full_name)->assertSeeText('Xem lịch làm việc bác sĩ')->assertSeeText('Lọc')
            ->assertDontSeeText('Tra cứu lịch theo ngày');
        $this->get(route('admin.doctor-schedules.month.show', [$doctor, 'month' => '2090-02']))->assertOk()
            ->assertSee('28/02/2090')->assertSee('Chưa cấu hình')->assertSeeText('Chọn tệp Excel')->assertDontSeeText('Lịch cũ');
        $this->put(route('admin.doctor-schedules.month.update', $doctor), [
            'date' => '2090-02-01', 'shift' => 'MORNING', 'state' => 'OFF',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('doctor_schedules', ['doctor_id' => $doctor->id, 'shift_code' => 'MORNING', 'status' => 'INACTIVE']);
        $standard = $doctor->schedules()->whereDate('work_date', '2090-02-01')->where('shift_code', 'MORNING')->sole();
        $this->get(route('admin.doctor-schedules.index', ['work_date' => '2090-02-01', 'doctor_id' => $doctor->id]))
            ->assertRedirect(route('admin.doctor-schedules.month.show', [$doctor, 'month' => '2090-02']));
        $this->get(route('admin.doctor-schedules.create'))->assertRedirect(route('admin.doctor-schedules.month.index'));
        $month = $this->sample->work_date->format('Y-m');
        $monthUrl = route('admin.doctor-schedules.month.show', [$doctor, 'month' => $month]);
        $this->get($monthUrl)->assertOk()->assertSee(route('admin.doctor-schedules.show', $this->sample))
            ->assertDontSeeText('Tra cứu lịch theo ngày');
        $this->get(route('admin.doctor-schedules.show', $this->sample))->assertOk()->assertSee($monthUrl);
        $this->get(route('admin.doctor-schedules.show', $standard))
            ->assertOk()->assertSee('Quản lý lịch tháng')->assertDontSee('Thêm khung giờ');
    }

    public function test_configured_hours_apply_to_new_shifts_and_preserve_existing_bookable_hours(): void
    {
        $this->travelTo(now()->setDate(2090, 2, 1)->setTime(17, 0));
        $doctor = $this->sample->doctor;
        $actor = auth()->user();
        $service = app(DoctorMonthlyScheduleService::class);
        $old = $service->updateShift($actor, $doctor->id, '2090-02-02', 'AFTERNOON', 'WORKING', null,
            $this->sample->specialty_id, $this->sample->examination_room_id);

        $settings = route('admin.doctor-schedules.month.shift-hours.edit');
        $update = route('admin.doctor-schedules.month.shift-hours.update');
        $this->get($settings)->assertOk()->assertSee('value="13:30"', false);
        $hours = ['morning_start' => '08:00', 'morning_end' => '11:00', 'afternoon_start' => '17:30', 'afternoon_end' => '19:00'];
        $this->from($settings)->put($update, [...$hours, 'afternoon_end' => '18:50'])->assertSessionHasErrors('afternoon_end');
        $this->from($settings)->put($update, [...$hours, 'morning_end' => '18:00'])->assertSessionHasErrors('afternoon_start');
        $this->put($update, $hours)->assertRedirect($settings)->assertSessionHasNoErrors();
        $this->get($settings)->assertOk()->assertSee('value="17:30"', false);

        $cells = $service->forMonth($actor, $doctor->id, '2090-02')['days'];
        $this->assertSame('13:30:00', $cells['2090-02-02']['shifts']['AFTERNOON']['start']);
        $this->assertSame('17:30:00', $cells['2090-02-01']['shifts']['AFTERNOON']['start']);

        $new = $service->updateShift($actor, $doctor->id, '2090-02-01', 'AFTERNOON', 'WORKING', null,
            $this->sample->specialty_id, $this->sample->examination_room_id);
        $this->assertSame(['17:30:00', '18:00:00', '18:30:00'], $new->appointmentTimeSlots()->orderBy('start_time')->pluck('start_time')->all());
        $this->assertTrue(PatientBookingWindow::allows($old, $old->appointmentTimeSlots()->firstOrFail()));
        $this->assertTrue(PatientBookingWindow::allows($new, $new->appointmentTimeSlots()->firstOrFail()));
        $this->travel(30)->minutes();
        $this->assertFalse(PatientBookingWindow::allows($new, $new->appointmentTimeSlots()->orderByDesc('start_time')->firstOrFail()));
    }

    public function test_changed_hours_require_a_new_excel_preview_before_confirming(): void
    {
        $this->requiresZip();
        $doctor = $this->sample->doctor;
        $preview = $this->upload([['2090-02-01', 'NGHỈ', 'NGHỈ']]);
        $revision = $this->get($preview)->assertOk()->assertSee('07:30–11:30')->viewData('revision');
        $this->put(route('admin.doctor-schedules.month.shift-hours.update'), [
            'morning_start' => '08:00', 'morning_end' => '11:00', 'afternoon_start' => '14:00', 'afternoon_end' => '18:00',
        ])->assertSessionHasNoErrors();

        $this->get($preview)->assertOk()->assertSeeText('Giờ ca mặc định đã thay đổi')->assertDontSeeText('Xác nhận nhập lịch');
        $this->post(route('admin.doctor-schedules.month.import.confirm', [$doctor, basename($preview)]), ['revision' => $revision])
            ->assertRedirect($preview)->assertSessionHasErrors('workbook');
        $this->assertFalse($doctor->schedules()->whereDate('work_date', '2090-02-01')->exists());
    }

    public function test_confirming_a_file_on_the_month_page_fills_working_and_off_shifts(): void
    {
        $this->requiresZip();
        $doctor = $this->sample->doctor;
        $monthPage = route('admin.doctor-schedules.month.show', [$doctor, 'month' => '2090-02']);
        $apply = route('admin.doctor-schedules.month.import.apply', $doctor);
        $this->get($monthPage)->assertOk()->assertSeeText('Xác nhận nhập lịch')->assertSee($apply);

        $this->post($apply, [
            'month' => '2090-02',
            'workbook' => $this->workbook([['2090-02-01', 'NGHỈ', 'LÀM'], ['2090-02-03', 'LÀM', 'NGHỈ']]),
        ])->assertRedirect($monthPage)->assertSessionHasNoErrors();

        $days = $this->get($monthPage)->assertOk()->assertSeeText('Đã áp dụng lịch từ Excel.')->viewData('days');
        $this->assertSame('OFF', $days['2090-02-01']['shifts']['MORNING']['state']);
        $afternoon = $days['2090-02-01']['shifts']['AFTERNOON'];
        $this->assertSame('WORKING', $afternoon['state']);
        $this->assertSame($doctor->specialty_id, $afternoon['schedule']->specialty_id);
        $this->assertSame($this->sample->examination_room_id, $afternoon['schedule']->examination_room_id);
        $this->assertSame(7, $afternoon['schedule']->appointmentTimeSlots()->count());
        $this->assertSame('UNCONFIGURED', $days['2090-02-02']['shifts']['MORNING']['state']);
        $this->assertSame('WORKING', $days['2090-02-03']['shifts']['MORNING']['state']);
    }

    public function test_a_single_room_choice_applies_to_new_shifts_and_keeps_existing_assignments(): void
    {
        $this->requiresZip();
        $doctor = $this->sample->doctor;
        $service = app(DoctorMonthlyScheduleService::class);
        $existing = $service->updateShift(auth()->user(), $doctor->id, '2090-02-01', 'MORNING', 'WORKING', null,
            $doctor->specialty_id, $this->sample->examination_room_id);
        $room = $this->sample->examinationRoom->replicate();
        $room->fill(['room_code' => 'PK-IMPORT-02', 'name' => 'Phòng khám nhập lịch 02'])->save();
        $apply = route('admin.doctor-schedules.month.import.apply', $doctor);
        $rows = [['2090-02-01', 'LÀM', 'NGHỈ'], ['2090-02-02', 'LÀM', 'LÀM']];

        $this->post($apply, ['month' => '2090-02', 'workbook' => $this->workbook($rows)])
            ->assertSessionHasErrors('room_id');
        $this->assertFalse($doctor->schedules()->whereDate('work_date', '2090-02-02')->exists());

        $this->post($apply, ['month' => '2090-02', 'workbook' => $this->workbook($rows), 'room_id' => $room->id])
            ->assertSessionHasNoErrors();
        $this->assertSame($this->sample->examination_room_id, $existing->fresh()->examination_room_id);
        $this->assertSame([$room->id, $room->id], $doctor->schedules()->whereDate('work_date', '2090-02-02')->pluck('examination_room_id')->all());
    }

    public function test_confirming_a_file_with_a_room_conflict_rolls_back_all_its_shifts(): void
    {
        $this->requiresZip();
        $doctor = $this->sample->doctor;
        $otherDoctor = Doctor::whereKeyNot($doctor->id)->firstOrFail();
        $occupied = $this->sample->replicate();
        $occupied->fill(['doctor_id' => $otherDoctor->id, 'work_date' => '2090-02-02', 'start_time' => '07:30:00', 'end_time' => '11:30:00'])->save();

        $this->post(route('admin.doctor-schedules.month.import.apply', $doctor), [
            'month' => '2090-02',
            'workbook' => $this->workbook([['2090-02-01', 'LÀM', 'NGHỈ'], ['2090-02-02', 'LÀM', 'NGHỈ']]),
        ])->assertRedirect(route('admin.doctor-schedules.month.show', [$doctor, 'month' => '2090-02']))
            ->assertSessionHasErrors(['workbook' => '02/02/2090 · ca sáng: Phòng khám đã có lịch làm việc trùng giờ.']);
        $this->assertFalse($doctor->schedules()->whereBetween('work_date', ['2090-02-01', '2090-02-02'])->exists());
    }

    public function test_excel_assigns_a_distinct_room_to_each_shift_and_keeps_protected_shifts(): void
    {
        $this->requiresZip();
        $doctor = $this->sample->doctor;
        $firstRoom = $this->sample->examinationRoom;
        $secondRoom = $firstRoom->replicate();
        $secondRoom->fill(['room_code' => 'PK-IMPORT-02', 'name' => 'Phòng khám nhập lịch 02'])->save();
        $existing = app(DoctorMonthlyScheduleService::class)->updateShift(auth()->user(), $doctor->id, '2090-02-02', 'MORNING', 'WORKING', null,
            $doctor->specialty_id, $firstRoom->id);
        $rows = [
            ['2090-02-01', 'LÀM', $firstRoom->room_code.' | '.$firstRoom->name, 'LÀM', $secondRoom->room_code],
            ['2090-02-02', 'GIỮ NGUYÊN', $firstRoom->room_code, 'NGHỈ', null],
        ];

        $this->post(route('admin.doctor-schedules.month.import.apply', $doctor), [
            'month' => '2090-02', 'workbook' => $this->workbook($rows, withRooms: true),
        ])->assertSessionHasNoErrors();

        $days = $this->get(route('admin.doctor-schedules.month.show', [$doctor, 'month' => '2090-02']))
            ->assertOk()->assertSeeText($secondRoom->room_code.' · '.$secondRoom->name)->viewData('days');
        $this->assertSame($firstRoom->id, $days['2090-02-01']['shifts']['MORNING']['schedule']->examination_room_id);
        $this->assertSame($secondRoom->id, $days['2090-02-01']['shifts']['AFTERNOON']['schedule']->examination_room_id);
        $this->assertSame($existing->id, $days['2090-02-02']['shifts']['MORNING']['schedule']->id);
        $this->assertSame('OFF', $days['2090-02-02']['shifts']['AFTERNOON']['state']);
    }

    public function test_new_excel_format_rejects_missing_or_incompatible_working_rooms(): void
    {
        $this->requiresZip();
        $doctor = $this->sample->doctor;
        $firstRoom = $this->sample->examinationRoom;
        $wrongRoom = \App\Models\ExaminationRoom::where('specialty_id', '!=', $doctor->specialty_id)->firstOrFail();
        foreach ([null, $wrongRoom->room_code] as $roomCode) {
            $rows = [['2090-02-01', 'LÀM', $firstRoom->room_code, 'LÀM', $roomCode]];
            $this->post(route('admin.doctor-schedules.month.import.apply', $doctor), [
                'month' => '2090-02', 'workbook' => $this->workbook($rows, withRooms: true),
            ])->assertSessionHasErrors('workbook');
        }

        $this->assertFalse($doctor->schedules()->whereDate('work_date', '2090-02-01')->exists());
    }

    public function test_downloaded_template_uses_all_days_of_the_selected_month(): void
    {
        $this->requiresZip();
        $response = $this->get(route('admin.doctor-schedules.month.template', ['month' => '2028-02']))
            ->assertDownload('Mediway_Mau_Lich_Bac_Si_2028-02.xlsx');
        $file = UploadedFile::fake()->createWithContent('template.xlsx', $response->streamedContent());
        $book = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
        $sheet = $book->getActiveSheet();
        $this->assertSame([['Ngày', 'Ca sáng', 'Phòng khám ca sáng', 'Ca chiều', 'Phòng khám ca chiều']], $sheet->rangeToArray('A1:E1'));
        $this->assertSame('01/02/2028', $sheet->getCell('A2')->getFormattedValue());
        $this->assertSame('29/02/2028', $sheet->getCell('A30')->getFormattedValue());
        $this->assertSame(30, $sheet->getHighestDataRow());
        $book->disconnectWorksheets();
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

    private function workbook(array $rows, bool $withRooms = false): UploadedFile
    {
        $book = new Spreadsheet;
        $headers = $withRooms ? ['Ngày', 'Ca sáng', 'Phòng khám ca sáng', 'Ca chiều', 'Phòng khám ca chiều'] : ['Ngày', 'Ca sáng', 'Ca chiều'];
        $book->getActiveSheet()->fromArray([$headers, ...$rows]);
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
