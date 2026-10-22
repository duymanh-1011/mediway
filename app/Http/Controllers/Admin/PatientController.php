<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePatientRequest;
use App\Http\Requests\Admin\UpdatePatientRequest;
use App\Models\Patient;
use App\Models\PatientDetail;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class PatientController extends Controller
{
    private const DETAIL_FIELDS = [
        'citizen_id_number',
        'citizen_id_issue_date',
        'citizen_id_issue_place',
        'email',
        'hometown',
        'permanent_address',
        'current_address',
        'nationality',
        'ethnicity',
        'occupation',
        'blood_type',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
        'notes',
    ];

    private const FILE_FIELDS = [
        'avatar' => 'avatar_path',
        'citizen_id_front' => 'citizen_id_front_path',
        'citizen_id_back' => 'citizen_id_back_path',
        'profile_pdf' => 'profile_pdf_path',
    ];

    private const DOCUMENT_TYPES = [
        'avatar' => 'avatar_path',
        'citizen-id-front' => 'citizen_id_front_path',
        'citizen-id-back' => 'citizen_id_back_path',
        'profile-pdf' => 'profile_pdf_path',
    ];

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');

        $patients = Patient::query()
            ->with(['user', 'detail'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where(
                        'patient_code',
                        'like',
                        '%'.$search.'%'
                    )
                        ->orWhere(
                            'full_name',
                            'like',
                            '%'.$search.'%'
                        )
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where(
                                'phone',
                                'like',
                                '%'.$search.'%'
                            );
                        })
                        ->orWhereHas('detail', function ($detailQuery) use ($search) {
                            $detailQuery->where(
                                'citizen_id_number',
                                'like',
                                '%'.$search.'%'
                            );
                        });
                });
            })
            ->when(
                $status === 'ACTIVE',
                fn ($query) => $query->where('status', 'ACTIVE')
            )
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.patients.index', compact(
            'patients',
            'search',
            'status'
        ));
    }

    public function create()
    {
        $availableUsers = $this->availablePatientUsers();

        return view('admin.patients.create', compact('availableUsers'));
    }

    public function store(StorePatientRequest $request)
    {
        $data = $request->validated();
        $uploadedPaths = [];

        try {
            $patient = DB::transaction(function () use (
                $request,
                $data,
                &$uploadedPaths
            ) {
                $patientRole = Role::query()
                    ->where('name', 'Patient')
                    ->lockForUpdate()
                    ->first();

                if (! $patientRole) {
                    throw ValidationException::withMessages([
                        'user_id' => 'Hệ thống chưa có vai trò bệnh nhân.',
                    ]);
                }

                $userId = $data['user_id'] ?? null;

                if ($userId !== null) {
                    $user = $this->validateAndLockPatientUser(
                        (int) $userId,
                        $patientRole->id
                    );

                    $patientCode = $user->business_code;

                    if (
                        Patient::query()
                            ->where('patient_code', $patientCode)
                            ->exists()
                    ) {
                        throw ValidationException::withMessages([
                            'user_id' => 'Mã tài khoản đã được sử dụng cho hồ sơ bệnh nhân.',
                        ]);
                    }
                } else {
                    $patientCode = $this->nextPatientCode();
                }

                $patient = Patient::create([
                    'user_id' => $userId,
                    'patient_code' => $patientCode,
                    'full_name' => trim($data['full_name']),
                    'date_of_birth' => $data['date_of_birth'] ?? null,
                    'gender' => $data['gender'] ?? null,
                    'status' => 'ACTIVE',
                ]);

                $details = $this->detailData($data);

                $newFiles = $this->storeUploadedFiles(
                    $request,
                    $patient,
                    $uploadedPaths
                );

                PatientDetail::create(
                    array_merge(
                        ['patient_id' => $patient->id],
                        $details,
                        $newFiles
                    )
                );

                return $patient;
            }, 3);
        } catch (Throwable $exception) {
            $this->deleteFiles($uploadedPaths);
            throw $exception;
        }

        return redirect()
            ->route('admin.patients.show', $patient)
            ->with('success', 'Thêm hồ sơ bệnh nhân thành công.');
    }

    public function show(Patient $patient)
    {
        $patient->load(['user.role', 'detail']);

        return view('admin.patients.show', compact('patient'));
    }

    public function edit(Patient $patient)
    {
        $patient->load(['user', 'detail']);

        $availableUsers = $this->availablePatientUsers($patient->id);

        return view('admin.patients.edit', compact(
            'patient',
            'availableUsers'
        ));
    }

    public function update(
        UpdatePatientRequest $request,
        Patient $patient
    ) {
        $data = $request->validated();

        $uploadedPaths = [];
        $oldPaths = [];

        try {
            DB::transaction(function () use (
                $request,
                $patient,
                $data,
                &$uploadedPaths,
                &$oldPaths
            ) {
                $patientRole = Role::query()
                    ->where('name', 'Patient')
                    ->lockForUpdate()
                    ->first();

                if (! $patientRole) {
                    throw ValidationException::withMessages([
                        'user_id' => 'Hệ thống chưa có vai trò bệnh nhân.',
                    ]);
                }

                $lockedPatient = Patient::query()
                    ->lockForUpdate()
                    ->findOrFail($patient->id);

                $newUserId = $data['user_id'] ?? null;

                if ($newUserId !== null) {
                    $user = $this->validateAndLockPatientUser(
                        (int) $newUserId,
                        $patientRole->id,
                        $lockedPatient->id
                    );

                    if (
                        $user->business_code !== $lockedPatient->patient_code
                    ) {
                        throw ValidationException::withMessages([
                            'user_id' => 'Mã tài khoản không trùng mã bệnh nhân. Không thể liên kết trực tiếp.',
                        ]);
                    }
                }

                $lockedPatient->update([
                    'user_id' => $newUserId,
                    'full_name' => trim($data['full_name']),
                    'date_of_birth' => $data['date_of_birth'] ?? null,
                    'gender' => $data['gender'] ?? null,
                ]);

                $detail = PatientDetail::query()
                    ->where('patient_id', $lockedPatient->id)
                    ->lockForUpdate()
                    ->first();

                $details = $this->detailData($data);

                $newFiles = $this->storeUploadedFiles(
                    $request,
                    $lockedPatient,
                    $uploadedPaths
                );

                if ($detail) {
                    foreach ($newFiles as $column => $newPath) {
                        $oldPath = $detail->{$column};

                        if ($oldPath && $oldPath !== $newPath) {
                            $oldPaths[] = $oldPath;
                        }
                    }

                    $detail->update(array_merge($details, $newFiles));
                } else {
                    PatientDetail::create(
                        array_merge(
                            ['patient_id' => $lockedPatient->id],
                            $details,
                            $newFiles
                        )
                    );
                }
            }, 3);
        } catch (Throwable $exception) {
            $this->deleteFiles($uploadedPaths);
            throw $exception;
        }

        /*
         * Chỉ xóa tài liệu cũ khi transaction database
         * đã lưu thành công.
         */
        $this->deleteFiles($oldPaths);

        return redirect()
            ->route('admin.patients.show', $patient)
            ->with('success', 'Cập nhật hồ sơ bệnh nhân thành công.');
    }

    /**
     * Phục vụ tài liệu qua route được bảo vệ bằng AdminMiddleware.
     * Không nhận đường dẫn tùy ý từ người dùng.
     */
    public function document(Patient $patient, string $type)
    {
        abort_unless(
            array_key_exists($type, self::DOCUMENT_TYPES),
            404
        );

        $patient->load('detail');

        $column = self::DOCUMENT_TYPES[$type];
        $path = $patient->detail?->{$column};

        abort_unless(is_string($path) && $path !== '', 404);

        /*
         * Ngăn việc sử dụng đường dẫn ngoài thư mục
         * tài liệu dành riêng cho bệnh nhân này.
         */
        $expectedPrefix = 'patient-documents/'.$patient->id.'/';

        abort_unless(
            str_starts_with($path, $expectedPrefix)
                && ! str_contains($path, '..')
                && ! str_contains($path, '\\'),
            404
        );

        $disk = Storage::disk('local');

        abort_unless($disk->exists($path), 404);

        return $disk->response(
            $path,
            $type === 'profile-pdf'
                ? 'ho-so-benh-nhan.pdf'
                : basename($path),
            [
                'Cache-Control' => 'private, no-store, no-cache',
                'X-Content-Type-Options' => 'nosniff',
            ],
            'inline'
        );
    }

    /**
     * Lưu file trên disk local riêng tư.
     * Mỗi bệnh nhân có một thư mục theo ID.
     */
    private function storeUploadedFiles(
        Request $request,
        Patient $patient,
        array &$uploadedPaths
    ): array {
        $paths = [];

        foreach (self::FILE_FIELDS as $input => $column) {
            if (! $request->hasFile($input)) {
                continue;
            }

            $file = $request->file($input);

            if (! $file || ! $file->isValid()) {
                throw ValidationException::withMessages([
                    $input => 'Tệp tải lên không hợp lệ.',
                ]);
            }

            $path = $file->store(
                'patient-documents/'.$patient->id,
                'local'
            );

            if (! $path) {
                throw ValidationException::withMessages([
                    $input => 'Không thể lưu tệp. Vui lòng thử lại.',
                ]);
            }

            $uploadedPaths[] = $path;
            $paths[$column] = $path;
        }

        return $paths;
    }

    /**
     * Xóa những file ứng dụng sở hữu.
     * Không nhận đường dẫn tùy ý từ request.
     */
    private function deleteFiles(array $paths): void
    {
        if (empty($paths)) {
            return;
        }

        try {
            Storage::disk('local')->delete(
                array_values(array_unique($paths))
            );
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function availablePatientUsers(?int $patientId = null)
    {
        return User::query()
            ->whereHas('role', function ($query) {
                $query->where('name', 'Patient');
            })
            ->where(function ($query) use ($patientId) {
                $query->whereDoesntHave('patient');

                if ($patientId !== null) {
                    $query->orWhereHas(
                        'patient',
                        function ($patientQuery) use ($patientId) {
                            $patientQuery->whereKey($patientId);
                        }
                    );
                }
            })
            ->orderBy('business_code')
            ->get(['id', 'business_code', 'phone']);
    }

    private function validateAndLockPatientUser(
        int $userId,
        int $roleId,
        ?int $currentPatientId = null
    ): User {
        $user = User::query()
            ->lockForUpdate()
            ->find($userId);

        if (! $user || $user->role_id !== $roleId) {
            throw ValidationException::withMessages([
                'user_id' => 'Tài khoản không tồn tại hoặc không thuộc vai trò bệnh nhân.',
            ]);
        }

        $existingPatient = Patient::query()
            ->where('user_id', $user->id)
            ->first();

        if (
            $existingPatient
            && $existingPatient->id !== $currentPatientId
        ) {
            throw ValidationException::withMessages([
                'user_id' => 'Tài khoản đã liên kết với bệnh nhân khác.',
            ]);
        }

        return $user;
    }

    private function nextPatientCode(): string
    {
        $lastPatientCode = Patient::query()
            ->where('patient_code', 'like', 'BN%')
            ->orderByDesc('patient_code')
            ->value('patient_code');

        $lastUserCode = User::query()
            ->where('business_code', 'like', 'BN%')
            ->orderByDesc('business_code')
            ->value('business_code');

        $lastNumber = max(
            $this->codeNumber($lastPatientCode),
            $this->codeNumber($lastUserCode)
        );

        return 'BN'.str_pad(
            (string) ($lastNumber + 1),
            6,
            '0',
            STR_PAD_LEFT
        );
    }

    private function codeNumber(?string $code): int
    {
        if (
            $code !== null
            && preg_match('/^BN([0-9]+)$/', $code, $matches)
        ) {
            return (int) $matches[1];
        }

        return 0;
    }

    private function detailData(array $data): array
    {
        $details = Arr::only($data, self::DETAIL_FIELDS);

        foreach (self::DETAIL_FIELDS as $field) {
            $value = $details[$field] ?? null;

            if (is_string($value)) {
                $value = trim($value);
            }

            $details[$field] = $value === '' ? null : $value;
        }

        return $details;
    }
}
