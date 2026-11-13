<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\ExaminationRoom;
use App\Models\Specialty;
use App\Services\DoctorMonthlyScheduleService;
use App\Services\DoctorScheduleWorkbook;
use App\Services\DoctorShiftHours;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use ZipArchive;

class DoctorMonthlyScheduleController extends Controller
{
    public function __construct(
        private DoctorMonthlyScheduleService $schedules,
        private DoctorScheduleWorkbook $workbook,
        private DoctorShiftHours $shiftHours,
    ) {}

    public function index(Request $request)
    {
        $month = $request->validate(['month' => 'nullable|date_format:Y-m'])['month'] ?? null;
        $keyword = $request->string('keyword')->trim()->toString();
        $doctors = Doctor::with('specialty')->when($keyword !== '', fn ($query) => $query->where(
            fn ($names) => $names->whereLike('full_name', '%'.$keyword.'%')->orWhereLike('doctor_code', '%'.$keyword.'%'),
        ))->orderBy('full_name')->orderBy('id')->paginate(15)->appends(['keyword' => $keyword, 'month' => $month]);

        return view('admin.doctor-schedules.month-doctors', compact('doctors', 'keyword', 'month'));
    }

    public function editShiftHours()
    {
        return view('admin.doctor-schedules.shift-hours', ['shiftHours' => $this->shiftHours->current()]);
    }

    public function updateShiftHours(Request $request)
    {
        $this->shiftHours->update($request->only('morning_start', 'morning_end', 'afternoon_start', 'afternoon_end'));

        return redirect()->route('admin.doctor-schedules.month.shift-hours.edit')->with('success', 'Đã lưu giờ làm việc mặc định.');
    }

    public function show(Request $request, Doctor $doctor)
    {
        $month = $request->validate(['month' => 'sometimes|required|date_format:Y-m'])['month'] ?? today()->format('Y-m');

        return view('admin.doctor-schedules.month', $this->schedules->forMonth($request->user(), $doctor->id, $month) + $this->catalogue($doctor));
    }

    public function update(Request $request, Doctor $doctor)
    {
        $data = $request->validate([
            'date' => 'required|date_format:Y-m-d', 'shift' => 'required|string', 'state' => 'required|string',
            'revision' => 'nullable|string|size:64', 'specialty_id' => 'nullable|integer|min:1', 'room_id' => 'nullable|integer|min:1',
        ]);
        $this->schedules->updateShift($request->user(), $doctor->id, $data['date'], $data['shift'], $data['state'],
            $data['revision'] ?? null, $data['specialty_id'] ?? null, $data['room_id'] ?? null);

        return redirect()->route('admin.doctor-schedules.month.show', [$doctor, 'month' => substr($data['date'], 0, 7)])
            ->with('success', 'Đã lưu ca làm việc.');
    }

    public function template(Request $request)
    {
        abort_unless(class_exists(ZipArchive::class), 503, 'PHP cần bật extension zip để tạo mẫu Excel.');
        $month = $request->validate(['month' => 'sometimes|required|date_format:Y-m'])['month'] ?? today()->format('Y-m');

        return response()->streamDownload(function () use ($month): void {
            $book = $this->workbook->template($month);
            try {
                (new Xlsx($book))->save('php://output');
            } finally {
                $book->disconnectWorksheets();
            }
        }, 'Mediway_Mau_Lich_Bac_Si_'.$month.'.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function preview(Request $request, Doctor $doctor)
    {
        $data = $request->validate(['month' => 'required|date_format:Y-m', 'workbook' => 'required|file|extensions:xlsx|max:1024']);
        $draft = $this->workbookDraft($request, $doctor, $data['month'], $data['workbook']->getRealPath());
        $token = Str::random(48);
        $this->saveDraft($request, $doctor, $token, $draft);

        return redirect()->route('admin.doctor-schedules.month.import.show', [$doctor, $token]);
    }

    public function import(Request $request, Doctor $doctor)
    {
        $data = $request->validate([
            'month' => 'required|date_format:Y-m',
            'workbook' => 'required|file|extensions:xlsx|max:1024',
            'room_id' => 'nullable|integer|min:1',
        ]);
        $month = $data['month'];
        $monthPage = redirect()->route('admin.doctor-schedules.month.show', [$doctor, 'month' => $month]);

        try {
            $draft = $this->workbookDraft($request, $doctor, $month, $data['workbook']->getRealPath());
            if ($draft['rows'] === []) {
                return $monthPage->with('success', 'Tất cả các ca trong tệp được giữ nguyên.');
            }
            $draft['rows'] = $this->assignImportRooms($doctor, $draft['rows'], $data['room_id'] ?? null);
            $this->applyDraft($request, $doctor, $draft);
        } catch (ValidationException $error) {
            return $monthPage->withErrors($error->errors())->withInput($request->only('room_id'));
        }

        return $monthPage->with('success', 'Đã áp dụng lịch từ Excel.');
    }

    private function workbookDraft(Request $request, Doctor $doctor, string $month, string $path): array
    {
        ['days' => $days, 'shiftHours' => $shiftHours] = $this->schedules->forMonth($request->user(), $doctor->id, $month);
        $rooms = $this->catalogue($doctor)['rooms']->keyBy(fn (ExaminationRoom $room) => mb_strtoupper($room->room_code));
        $rows = [];
        foreach ($this->workbook->read($path, $month) as $date => $shifts) {
            foreach ($shifts as $code => ['state' => $state, 'room_code' => $roomCode]) {
                if ($state === 'KEEP') {
                    continue;
                }
                $cell = $days[$date]['shifts'][$code];
                $room = $roomCode === null ? null : $rooms->get(mb_strtoupper($roomCode));
                if ($roomCode !== null && $room === null) {
                    $shift = $code === 'MORNING' ? 'ca sáng' : 'ca chiều';
                    $dateLabel = \Illuminate\Support\Carbon::parse($date)->format('d/m/Y');
                    throw ValidationException::withMessages(['workbook' => "$dateLabel · $shift: phòng $roomCode không hoạt động hoặc không phù hợp với chuyên khoa của bác sĩ."]);
                }
                $rows[] = [
                    'date' => $date, 'code' => $code, 'state' => $state, 'before' => $cell['state'],
                    'revision' => $cell['revision'], 'specialty_id' => $room?->specialty_id ?? $cell['schedule']?->specialty_id,
                    'room_id' => $room?->id ?? $cell['schedule']?->examination_room_id,
                    'start' => $cell['start'], 'end' => $cell['end'],
                ];
            }
        }
        return ['month' => $month, 'rows' => $rows, 'shiftHours' => $shiftHours];
    }

    private function assignImportRooms(Doctor $doctor, array $rows, ?int $roomId): array
    {
        $rooms = $this->catalogue($doctor)['rooms'];
        $selectedRoom = $roomId === null ? null : $rooms->find($roomId);
        if ($roomId !== null && $selectedRoom === null) {
            throw ValidationException::withMessages(['room_id' => 'Chọn phòng khám đang hoạt động và phù hợp với chuyên khoa của bác sĩ.']);
        }

        foreach ($rows as &$row) {
            if ($row['state'] !== 'WORKING' || ($row['specialty_id'] && $row['room_id'])) {
                continue;
            }

            $specialtyId = $row['specialty_id'] ?? $doctor->specialty_id;
            $sameSpecialty = $rooms->where('specialty_id', $specialtyId);
            $eligibleRooms = $sameSpecialty->isEmpty() && $row['specialty_id'] === null ? $rooms : $sameSpecialty;
            $room = $selectedRoom && ($row['specialty_id'] === null || (int) $selectedRoom->specialty_id === (int) $specialtyId)
                ? $selectedRoom
                : ($eligibleRooms->count() === 1 ? $eligibleRooms->first() : null);

            if ($room === null) {
                throw ValidationException::withMessages(['room_id' => $eligibleRooms->isEmpty()
                    ? 'Chưa có phòng khám phù hợp cho ca làm việc. Hãy cấu hình phòng khám trước khi nhập lịch.'
                    : 'Chọn một phòng phù hợp trong mục Nhập lịch từ Excel rồi xác nhận lại.']);
            }

            $row['specialty_id'] = (int) $room->specialty_id;
            $row['room_id'] = (int) $room->id;
        }
        unset($row);

        return $rows;
    }

    public function review(Request $request, Doctor $doctor, string $token)
    {
        $draft = $this->draft($request, $doctor, $token);
        $hoursChanged = ($draft['shiftHours'] ?? null) !== $this->shiftHours->current();
        $issues = $hoursChanged ? [] : $this->issues($request, $doctor, $draft['rows']);
        $revision = hash('sha256', serialize($draft));

        return response()->view('admin.doctor-schedules.month-preview',
            compact('doctor', 'token', 'draft', 'issues', 'revision', 'hoursChanged') + $this->catalogue($doctor))->header('Cache-Control', 'private, no-store');
    }

    public function assignments(Request $request, Doctor $doctor, string $token)
    {
        $data = $request->validate([
            'assignments' => 'required|array|max:62', 'assignments.*' => 'array:specialty_id,room_id',
            'assignments.*.specialty_id' => 'nullable|integer|min:1', 'assignments.*.room_id' => 'nullable|integer|min:1',
        ]);
        $draft = $this->draft($request, $doctor, $token);
        foreach ($draft['rows'] as $index => &$row) {
            if ($row['state'] === 'WORKING' && isset($data['assignments'][$index])) {
                $row = [...$row, ...$data['assignments'][$index]];
            }
        }
        unset($row);
        $this->saveDraft($request, $doctor, $token, $draft);

        return redirect()->route('admin.doctor-schedules.month.import.show', [$doctor, $token]);
    }

    public function confirm(Request $request, Doctor $doctor, string $token)
    {
        $draft = $this->draft($request, $doctor, $token);
        try {
            $revision = $request->input('revision');
            if (! is_string($revision) || ! hash_equals(hash('sha256', serialize($draft)), $revision)) {
                throw ValidationException::withMessages(['workbook' => 'Bản xem trước đã thay đổi. Hãy xem lại trước khi xác nhận.']);
            }
            $this->applyDraft($request, $doctor, $draft);
        } catch (ValidationException $error) {
            return redirect()->route('admin.doctor-schedules.month.import.show', [$doctor, $token])->withErrors($error->errors());
        }
        Cache::store('file')->forget($this->draftKey($request, $doctor, $token));

        return redirect()->route('admin.doctor-schedules.month.show', [$doctor, 'month' => $draft['month']])
            ->with('success', 'Đã áp dụng lịch từ Excel.');
    }

    private function applyDraft(Request $request, Doctor $doctor, array $draft): void
    {
        DB::transaction(function () use ($request, $doctor, $draft): void {
            if (($draft['shiftHours'] ?? null) !== $this->shiftHours->current(lock: true)) {
                throw ValidationException::withMessages(['workbook' => 'Giờ ca mặc định đã thay đổi. Vui lòng tải lại tệp Excel để kiểm tra theo giờ mới.']);
            }

            foreach ($draft['rows'] as $row) {
                try {
                    $this->apply($request, $doctor, $row);
                } catch (ValidationException $error) {
                    $message = collect($error->errors())->flatten()->first();
                    $date = \Illuminate\Support\Carbon::parse($row['date'])->format('d/m/Y');
                    $shift = $row['code'] === 'MORNING' ? 'ca sáng' : 'ca chiều';
                    throw ValidationException::withMessages(['workbook' => $date.' · '.$shift.': '.$message]);
                }
            }
        }, 5);
    }

    private function issues(Request $request, Doctor $doctor, array $rows): array
    {
        $issues = [];
        foreach ($rows as $index => $row) {
            try {
                $this->apply($request, $doctor, $row, preview: true);
            } catch (ValidationException $error) {
                $issues[$index] = collect($error->errors())->flatten()->all();
            }
        }

        return $issues;
    }

    private function apply(Request $request, Doctor $doctor, array $row, bool $preview = false): void
    {
        $this->schedules->updateShift($request->user(), $doctor->id, $row['date'], $row['code'], $row['state'], $row['revision'],
            $row['specialty_id'], $row['room_id'], $preview);
    }

    private function catalogue(Doctor $doctor): array
    {
        $doctor->loadMissing('specialty');
        $specialties = Specialty::where('status', 'ACTIVE')
            ->when(! $doctor->specialty?->is_general, fn ($query) => $query->whereKey($doctor->specialty_id))
            ->orderBy('name')->get(['id', 'name']);

        return [
            'specialties' => $specialties,
            'rooms' => ExaminationRoom::where('status', 'ACTIVE')->whereIn('specialty_id', $specialties->modelKeys())
                ->orderBy('name')->get(['id', 'room_code', 'name', 'specialty_id']),
        ];
    }

    private function draft(Request $request, Doctor $doctor, string $token): array
    {
        $draft = Cache::store('file')->get($this->draftKey($request, $doctor, $token));
        abort_unless(is_array($draft), 404, 'Bản xem trước đã hết hạn. Vui lòng tải lại file.');

        return $draft;
    }

    private function saveDraft(Request $request, Doctor $doctor, string $token, array $draft): void
    {
        Cache::store('file')->put($this->draftKey($request, $doctor, $token), $draft, now()->addMinutes(30));
    }

    private function draftKey(Request $request, Doctor $doctor, string $token): string
    {
        return 'doctor-schedule-import:'.$request->user()->id.':'.$doctor->id.':'.$token;
    }
}
