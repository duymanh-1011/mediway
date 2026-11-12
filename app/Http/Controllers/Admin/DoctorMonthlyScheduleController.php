<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\ExaminationRoom;
use App\Models\Specialty;
use App\Services\DoctorMonthlyScheduleService;
use App\Services\DoctorScheduleWorkbook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use ZipArchive;

class DoctorMonthlyScheduleController extends Controller
{
    public function __construct(private DoctorMonthlyScheduleService $schedules, private DoctorScheduleWorkbook $workbook) {}

    public function index(Request $request)
    {
        $keyword = $request->string('keyword')->trim()->toString();
        $doctors = Doctor::with('specialty')->when($keyword !== '', fn ($query) => $query->where(
            fn ($names) => $names->whereLike('full_name', '%'.$keyword.'%')->orWhereLike('doctor_code', '%'.$keyword.'%'),
        ))->orderBy('full_name')->orderBy('id')->paginate(15)->appends(['keyword' => $keyword]);

        return view('admin.doctor-schedules.month-doctors', compact('doctors', 'keyword'));
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

    public function template()
    {
        abort_unless(class_exists(ZipArchive::class), 503, 'PHP cần bật extension zip để tạo mẫu Excel.');

        return response()->streamDownload(function (): void {
            $book = $this->workbook->template();
            try {
                (new Xlsx($book))->save('php://output');
            } finally {
                $book->disconnectWorksheets();
            }
        }, 'Mediway_Mau_Lich_Bac_Si.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function preview(Request $request, Doctor $doctor)
    {
        $data = $request->validate(['month' => 'required|date_format:Y-m', 'workbook' => 'required|file|extensions:xlsx|max:1024']);
        $month = $data['month'];
        $days = $this->schedules->forMonth($request->user(), $doctor->id, $month)['days'];
        $rows = [];
        foreach ($this->workbook->read($data['workbook']->getRealPath(), $month) as $date => $shifts) {
            foreach ($shifts as $code => $state) {
                $cell = $days[$date]['shifts'][$code];
                $rows[] = [
                    'date' => $date, 'code' => $code, 'state' => $state, 'before' => $cell['state'],
                    'revision' => $cell['revision'], 'specialty_id' => $cell['schedule']?->specialty_id,
                    'room_id' => $cell['schedule']?->examination_room_id,
                ];
            }
        }
        $token = Str::random(48);
        $this->saveDraft($request, $doctor, $token, ['month' => $month, 'rows' => $rows]);

        return redirect()->route('admin.doctor-schedules.month.import.show', [$doctor, $token]);
    }

    public function review(Request $request, Doctor $doctor, string $token)
    {
        $draft = $this->draft($request, $doctor, $token);
        $issues = $this->issues($request, $doctor, $draft['rows']);
        $revision = hash('sha256', serialize($draft));

        return response()->view('admin.doctor-schedules.month-preview',
            compact('doctor', 'token', 'draft', 'issues', 'revision') + $this->catalogue($doctor))->header('Cache-Control', 'private, no-store');
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
            DB::transaction(function () use ($request, $doctor, $draft): void {
                foreach ($draft['rows'] as $row) {
                    try {
                        $this->apply($request, $doctor, $row);
                    } catch (ValidationException $error) {
                        $message = collect($error->errors())->flatten()->first();
                        throw ValidationException::withMessages(['workbook' => $row['date'].' ('.$row['code'].') '.$message]);
                    }
                }
            }, 5);
        } catch (ValidationException $error) {
            return redirect()->route('admin.doctor-schedules.month.import.show', [$doctor, $token])->withErrors($error->errors());
        }
        Cache::store('file')->forget($this->draftKey($request, $doctor, $token));

        return redirect()->route('admin.doctor-schedules.month.show', [$doctor, 'month' => $draft['month']])
            ->with('success', 'Đã áp dụng lịch từ Excel.');
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
                ->orderBy('name')->get(['id', 'name', 'specialty_id']),
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
