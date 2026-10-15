<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePersonalHealthRecordRequest;
use App\Http\Requests\Admin\UpdatePersonalHealthRecordRequest;
use App\Models\Patient;
use App\Models\PersonalHealthRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PersonalHealthRecordController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $patientId = $request->query('patient_id');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $request->validate([
            'patient_id' => ['nullable', 'integer', 'exists:patients,id'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);

        $records = PersonalHealthRecord::query()
            ->with('patient')
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('patient', function ($patientQuery) use ($search) {
                    $patientQuery
                        ->where('patient_code', 'like', '%' . $search . '%')
                        ->orWhere('full_name', 'like', '%' . $search . '%')
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where(
                                'phone',
                                'like',
                                '%' . $search . '%'
                            );
                        });
                });
            })
            ->when(
                filled($patientId),
                fn ($query) => $query->where('patient_id', $patientId)
            )
            ->when(
                filled($dateFrom),
                fn ($query) => $query->whereDate('recorded_at', '>=', $dateFrom)
            )
            ->when(
                filled($dateTo),
                fn ($query) => $query->whereDate('recorded_at', '<=', $dateTo)
            )
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.personal-health-records.index', compact(
            'records',
            'search',
            'patientId',
            'dateFrom',
            'dateTo'
        ));
    }

    public function create(Request $request)
    {
        $patientId = $request->query('patient_id');

        $selectedPatient = null;

        if (filled($patientId)) {
            $selectedPatient = Patient::query()->findOrFail($patientId);
        }

        $patients = Patient::query()
            ->orderBy('patient_code')
            ->get(['id', 'patient_code', 'full_name', 'status']);

        return view('admin.personal-health-records.create', compact(
            'patients',
            'selectedPatient'
        ));
    }

    public function store(StorePersonalHealthRecordRequest $request)
    {
        $data = $request->validated();

        $record = DB::transaction(function () use ($data) {
            $patient = Patient::query()
                ->findOrFail($data['patient_id']);

            if ($patient->status !== 'ACTIVE') {
                throw ValidationException::withMessages([
                    'patient_id' => 'Không thể thêm chỉ số cho hồ sơ bệnh nhân không hoạt động.',
                ]);
            }

            return PersonalHealthRecord::create(
                $this->recordData($data, $patient)
            );
        });

        return redirect()
            ->route('admin.personal-health-records.show', $record)
            ->with('success', 'Thêm bản ghi sức khỏe thành công.');
    }

    public function show(PersonalHealthRecord $personalHealthRecord)
    {
        $personalHealthRecord->load('patient.user');

        return view('admin.personal-health-records.show', [
            'record' => $personalHealthRecord,
        ]);
    }

    public function edit(PersonalHealthRecord $personalHealthRecord)
    {
        $personalHealthRecord->load('patient');

        return view('admin.personal-health-records.edit', [
            'record' => $personalHealthRecord,
        ]);
    }

    public function update(
        UpdatePersonalHealthRecordRequest $request,
        PersonalHealthRecord $personalHealthRecord
    ) {
        $data = $request->validated();

        DB::transaction(function () use ($personalHealthRecord, $data) {
            $record = PersonalHealthRecord::query()
                ->lockForUpdate()
                ->findOrFail($personalHealthRecord->id);

            $patient = Patient::query()
                ->findOrFail($record->patient_id);

            /*
             * Giữ nguyên bệnh nhân sở hữu bản ghi.
             * Chỉ cập nhật các chỉ số và thời gian ghi nhận.
             */
            $record->update(
                $this->recordData($data, $patient)
            );
        });

        return redirect()
            ->route('admin.personal-health-records.show', $personalHealthRecord)
            ->with('success', 'Cập nhật bản ghi sức khỏe thành công.');
    }

    /**
     * Chuẩn hóa dữ liệu và tự tính BMI.
     * patient_code luôn được lấy từ bảng patients,
     * không nhận từ form.
     */
    private function recordData(array $data, Patient $patient): array
    {
        $height = $this->nullableNumber($data['height_cm'] ?? null);
        $weight = $this->nullableNumber($data['weight_kg'] ?? null);

        $bmi = null;

        if ($height !== null && $weight !== null && $height > 0) {
            $heightInMeters = $height / 100;

            $bmi = round(
                $weight / ($heightInMeters * $heightInMeters),
                2
            );
        }

        return [
            'patient_id' => $patient->id,
            'patient_code' => $patient->patient_code,

            'height_cm' => $height,
            'weight_kg' => $weight,
            'bmi' => $bmi,

            'systolic_blood_pressure' =>
                $data['systolic_blood_pressure'] ?? null,

            'diastolic_blood_pressure' =>
                $data['diastolic_blood_pressure'] ?? null,

            'heart_rate' =>
                $data['heart_rate'] ?? null,

            'body_temperature' =>
                $this->nullableNumber($data['body_temperature'] ?? null),

            'spo2' =>
                $this->nullableNumber($data['spo2'] ?? null),

            'respiratory_rate' =>
                $data['respiratory_rate'] ?? null,

            'blood_glucose' =>
                $this->nullableNumber($data['blood_glucose'] ?? null),

            'recorded_at' => Carbon::createFromFormat(
                '!Y-m-d H:i',
                str_replace('T', ' ', $data['recorded_at']),
                config('app.timezone')
            ),

            'notes' => $this->nullableText($data['notes'] ?? null),
        ];
    }

    private function nullableNumber($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }

    private function nullableText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
