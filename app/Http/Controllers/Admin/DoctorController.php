<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDoctorRequest;
use App\Http\Requests\Admin\UpdateDoctorRequest;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorDetail;
use App\Models\MedicalVisit;
use App\Models\Role;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class DoctorController extends Controller
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
        'education_level',
        'professional_qualification',
        'academic_title_degree',
        'professional_title',
        'current_position',
        'current_workplace',
        'work_start_date',
        'years_of_experience',
        'specialized_fields',
        'professional_skills',
        'languages',
        'practice_license_number',
        'practice_license_issue_date',
        'practice_license_issue_place',
        'practice_license_expiry_date',
        'practice_scope',
        'professional_bio',
        'notes',
    ];

    private const FILE_FIELDS = [
        'avatar' => 'avatar_path',
        'citizen_id_front' => 'citizen_id_front_path',
        'citizen_id_back' => 'citizen_id_back_path',
        'practice_license_file' => 'practice_license_file_path',
        'profile_pdf' => 'profile_pdf_path',
    ];

    private const DOCUMENT_TYPES = [
        'avatar' => 'avatar_path',
        'citizen-id-front' => 'citizen_id_front_path',
        'citizen-id-back' => 'citizen_id_back_path',
        'practice-license' => 'practice_license_file_path',
        'profile-pdf' => 'profile_pdf_path',
    ];

    public function index(Request $request)
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', 'in:ACTIVE,INACTIVE'],
            'specialty_id' => ['nullable', 'integer', 'exists:specialties,id'],
        ]);

        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');

        $doctors = Doctor::query()
            ->with(['user', 'detail', 'specialty'])
            ->when($request->filled('specialty_id'), fn ($query) => $query->forSpecialty($request->integer('specialty_id')))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner
                        ->where('doctor_code', 'like', '%'.$search.'%')
                        ->orWhere('full_name', 'like', '%'.$search.'%')
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('phone', 'like', '%'.$search.'%');
                        })
                        ->orWhereHas('detail', function ($detailQuery) use ($search) {
                            $detailQuery
                                ->where(
                                    'practice_license_number',
                                    'like',
                                    '%'.$search.'%'
                                )
                                ->orWhere(
                                    'citizen_id_number',
                                    'like',
                                    '%'.$search.'%'
                                )
                                ->orWhere(
                                    'email',
                                    'like',
                                    '%'.$search.'%'
                                );
                        });
                });
            })
            ->when(
                $status !== '',
                fn ($query) => $query->where('status', $status)
            )
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $specialties = Specialty::orderBy('name')->get();

        return view('admin.doctors.index', compact(
            'doctors',
            'specialties',
            'search',
            'status'
        ));
    }

    public function create()
    {
        $availableUsers = $this->availableDoctorUsers();
        $specialties = Specialty::where('status', 'ACTIVE')->orderBy('name')->get();

        return view('admin.doctors.create', compact('availableUsers', 'specialties'));
    }

    public function store(StoreDoctorRequest $request)
    {
        $data = $request->validated();
        $uploadedPaths = [];

        try {
            $doctor = DB::transaction(function () use (
                $request,
                $data,
                &$uploadedPaths
            ) {
                /*
                 * Dùng cùng dòng khóa Role Doctor với UserController
                 * để giảm nguy cơ cấp mã BS trùng khi tạo đồng thời.
                 */
                $role = Role::query()
                    ->where('name', 'Doctor')
                    ->lockForUpdate()
                    ->first();

                if (! $role) {
                    throw ValidationException::withMessages([
                        'user_id' => 'Chưa có vai trò bác sĩ trong hệ thống. Vui lòng báo người phụ trách hệ thống.',
                    ]);
                }

                $status = $data['status'];
                $accountStatus = $this->accountStatus($status);

                if (! empty($data['user_id'])) {
                    $user = $this->validateAndLockDoctorUser(
                        (int) $data['user_id'],
                        (int) $role->id
                    );

                    $doctorCode = $user->business_code;

                    if (! preg_match('/^BS[0-9]{6,}$/', $doctorCode)) {
                        throw ValidationException::withMessages([
                            'user_id' => 'Mã tài khoản bác sĩ phải có dạng BS000001.',
                        ]);
                    }

                    if (Doctor::query()
                        ->where('doctor_code', $doctorCode)
                        ->exists()) {
                        throw ValidationException::withMessages([
                            'user_id' => 'Mã bác sĩ này đã tồn tại.',
                        ]);
                    }

                    // Đồng bộ ngay tài khoản có sẵn với hồ sơ mới.
                    $user->update([
                        'status' => $accountStatus,
                    ]);
                } else {
                    $doctorCode = $this->nextDoctorCode();

                    $user = User::create([
                        'business_code' => $doctorCode,
                        'phone' => $data['phone'],
                        'password' => $data['password'],
                        'role_id' => $role->id,
                        'status' => $accountStatus,
                    ]);
                }

                $doctor = Doctor::create([
                    'user_id' => $user->id,
                    'specialty_id' => $this->lockSelectedSpecialty((int) $data['specialty_id'])->id,
                    'doctor_code' => $doctorCode,
                    'full_name' => trim($data['full_name']),
                    'date_of_birth' => $data['date_of_birth'] ?? null,
                    'gender' => $data['gender'] ?? null,
                    'status' => $status,
                ]);

                $newFiles = $this->storeUploadedFiles(
                    $request,
                    $doctor,
                    $uploadedPaths
                );

                DoctorDetail::create(array_merge(
                    [
                        'doctor_id' => $doctor->id,
                    ],
                    $this->detailData($data),
                    $newFiles
                ));

                return $doctor;
            });
        } catch (Throwable $exception) {
            $this->deleteFiles($uploadedPaths);

            throw $exception;
        }

        return redirect()
            ->route('admin.doctors.show', $doctor)
            ->with('success', 'Thêm bác sĩ thành công.');
    }

    public function show(Doctor $doctor)
    {
        $doctor->load(['user.role', 'detail', 'specialty']);

        return view('admin.doctors.show', compact('doctor'));
    }

    public function edit(Doctor $doctor)
    {
        $doctor->load(['user', 'detail', 'specialty']);
        $specialties = Specialty::where('status', 'ACTIVE')->orWhere('id', $doctor->specialty_id)->orderBy('name')->get();

        return view('admin.doctors.edit', compact('doctor', 'specialties'));
    }

    public function update(
        UpdateDoctorRequest $request,
        Doctor $doctor
    ) {
        $data = $request->validated();

        $uploadedPaths = [];
        $oldPaths = [];

        try {
            DB::transaction(function () use (
                $request,
                $doctor,
                $data,
                &$uploadedPaths,
                &$oldPaths
            ) {
                /*
                 * Khóa tài khoản trước, sau đó khóa hồ sơ bác sĩ.
                 * Thứ tự này giúp nhất quán với các thao tác khóa User.
                 */
                $user = User::query()
                    ->lockForUpdate()
                    ->findOrFail($doctor->user_id);

                $lockedDoctor = Doctor::query()
                    ->lockForUpdate()
                    ->findOrFail($doctor->id);

                if ((int) $lockedDoctor->user_id !== (int) $user->id) {
                    throw ValidationException::withMessages([
                        'status' => 'Tài khoản liên kết đã thay đổi. Vui lòng tải lại trang.',
                    ]);
                }

                $specialty = $this->lockSelectedSpecialty((int) $data['specialty_id']);
                if ((int) $lockedDoctor->specialty_id !== (int) $specialty->id && ! $specialty->is_general) {
                    $hasSchedules = $lockedDoctor->schedules()->where('status', 'ACTIVE')->whereDate('work_date', '>=', today())
                        ->where('specialty_id', '!=', $specialty->id)->exists();
                    $hasAppointments = Appointment::where('doctor_id', $lockedDoctor->id)->whereDate('appointment_date', '>=', today())
                        ->where('specialty_id', '!=', $specialty->id)
                        ->whereHas('appointmentStatus', fn ($query) => $query->whereNotIn('name', ['CANCELLED', 'COMPLETED']))->exists();
                    $hasOpenVisits = MedicalVisit::where('doctor_id', $lockedDoctor->id)->where('specialty_id', '!=', $specialty->id)
                        ->whereHas('status', fn ($query) => $query->where('name', '!=', 'COMPLETED'))->exists();
                    if ($hasSchedules || $hasAppointments || $hasOpenVisits) {
                        throw ValidationException::withMessages(['specialty_id' => 'Bác sĩ còn lịch làm việc, lịch hẹn hoặc lượt khám chưa hoàn tất ở chuyên khoa khác. Vui lòng xử lý các hồ sơ đó trước khi đổi chuyên khoa.']);
                    }
                }

                $lockedDoctor->update([
                    'specialty_id' => $specialty->id,
                    'full_name' => trim($data['full_name']),
                    'date_of_birth' => $data['date_of_birth'] ?? null,
                    'gender' => $data['gender'] ?? null,
                    'status' => $data['status'],
                ]);

                $user->update([
                    'status' => $this->accountStatus($data['status']),
                ]);

                $detail = DoctorDetail::query()
                    ->where('doctor_id', $lockedDoctor->id)
                    ->lockForUpdate()
                    ->first();

                if (! $detail) {
                    $detail = new DoctorDetail([
                        'doctor_id' => $lockedDoctor->id,
                    ]);
                }

                $newFiles = $this->storeUploadedFiles(
                    $request,
                    $lockedDoctor,
                    $uploadedPaths
                );

                foreach ($newFiles as $column => $newPath) {
                    $oldPath = $detail->{$column};

                    if (
                        is_string($oldPath) &&
                        $oldPath !== '' &&
                        $oldPath !== $newPath
                    ) {
                        $oldPaths[] = $oldPath;
                    }
                }

                $detail->fill(array_merge(
                    $this->detailData($data),
                    $newFiles
                ));

                $detail->save();
            });
        } catch (Throwable $exception) {
            $this->deleteFiles($uploadedPaths);

            throw $exception;
        }

        // Chỉ dọn tệp cũ sau khi database đã lưu thành công.
        $this->deleteFiles($oldPaths);

        return redirect()
            ->route('admin.doctors.show', $doctor)
            ->with('success', 'Cập nhật hồ sơ bác sĩ thành công.');
    }

    /**
     * Xem tài liệu riêng tư.
     */
    public function document(Doctor $doctor, string $type)
    {
        $column = self::DOCUMENT_TYPES[$type] ?? null;

        if (! $column) {
            abort(404);
        }

        $doctor->loadMissing('detail');

        $path = $doctor->detail?->{$column};

        if (! is_string($path) || $path === '') {
            abort(404);
        }

        $prefix = 'doctor-documents/'.$doctor->id.'/';

        if (
            ! str_starts_with($path, $prefix) ||
            str_contains($path, '..') ||
            str_contains($path, '\\')
        ) {
            abort(404);
        }

        $disk = Storage::disk('local');

        if (! $disk->exists($path)) {
            abort(404);
        }

        $filename = match ($type) {
            'avatar' => 'anh-dai-dien',
            'citizen-id-front' => 'cccd-mat-truoc',
            'citizen-id-back' => 'cccd-mat-sau',
            'practice-license' => 'giay-phep-hanh-nghe',
            'profile-pdf' => 'ho-so-bac-si',
            default => 'tai-lieu',
        };

        $extension = pathinfo($path, PATHINFO_EXTENSION);

        $filename .= '-'.$doctor->doctor_code;

        if ($extension !== '') {
            $filename .= '.'.$extension;
        }

        return $disk->response(
            $path,
            $filename,
            [
                'Cache-Control' => 'private, no-store, no-cache',
                'X-Content-Type-Options' => 'nosniff',
            ],
            'inline'
        );
    }

    private function availableDoctorUsers()
    {
        return User::query()
            ->whereHas('role', function ($query) {
                $query->where('name', 'Doctor');
            })
            ->whereDoesntHave('doctor')
            ->where(
                'business_code',
                'regexp',
                '^BS[0-9]{6,}$'
            )
            ->orderBy('business_code')
            ->get([
                'id',
                'business_code',
                'phone',
                'status',
            ]);
    }

    private function lockSelectedSpecialty(int $id): Specialty
    {
        $specialty = Specialty::whereKey($id)->lockForUpdate()->first();
        if (! $specialty || $specialty->status !== 'ACTIVE') {
            throw ValidationException::withMessages(['specialty_id' => 'Chuyên khoa đã ngừng hoạt động. Vui lòng chọn lại chuyên khoa.']);
        }

        return $specialty;
    }

    private function validateAndLockDoctorUser(
        int $userId,
        int $roleId
    ): User {
        $user = User::query()
            ->lockForUpdate()
            ->find($userId);

        if (! $user) {
            throw ValidationException::withMessages([
                'user_id' => 'Tài khoản được chọn không tồn tại.',
            ]);
        }

        if ((int) $user->role_id !== $roleId) {
            throw ValidationException::withMessages([
                'user_id' => 'Tài khoản phải có vai trò bác sĩ.',
            ]);
        }

        if (Doctor::query()
            ->where('user_id', $user->id)
            ->exists()) {
            throw ValidationException::withMessages([
                'user_id' => 'Tài khoản đã liên kết với một bác sĩ.',
            ]);
        }

        return $user;
    }

    /**
     * Sinh mã bác sĩ từ cả users và doctors.
     */
    private function nextDoctorCode(): string
    {
        $doctorCodes = Doctor::query()
            ->where('doctor_code', 'like', 'BS%')
            ->pluck('doctor_code');

        $userCodes = User::query()
            ->where('business_code', 'like', 'BS%')
            ->pluck('business_code');

        $max = 0;

        foreach ($doctorCodes->concat($userCodes) as $code) {
            if (preg_match('/^BS([0-9]+)$/', (string) $code, $matches)) {
                $max = max($max, (int) $matches[1]);
            }
        }

        return 'BS'.str_pad(
            (string) ($max + 1),
            6,
            '0',
            STR_PAD_LEFT
        );
    }

    private function accountStatus(string $doctorStatus): string
    {
        return $doctorStatus === 'ACTIVE' ? 'ACTIVE' : 'LOCKED';
    }

    private function detailData(array $data): array
    {
        $result = Arr::only($data, self::DETAIL_FIELDS);

        foreach (self::DETAIL_FIELDS as $field) {
            $value = $result[$field] ?? null;

            if (is_string($value)) {
                $value = trim($value);
            }

            $result[$field] = $value === '' ? null : $value;
        }

        return $result;
    }

    private function storeUploadedFiles(
        Request $request,
        Doctor $doctor,
        array &$uploadedPaths
    ): array {
        $result = [];

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
                'doctor-documents/'.$doctor->id,
                'local'
            );

            if (! $path) {
                throw ValidationException::withMessages([
                    $input => 'Không thể lưu tệp. Vui lòng thử lại hoặc báo người phụ trách hệ thống.',
                ]);
            }

            $result[$column] = $path;
            $uploadedPaths[] = $path;
        }

        return $result;
    }

    private function deleteFiles(array $paths): void
    {
        $paths = array_values(
            array_unique(
                array_filter($paths)
            )
        );

        if (empty($paths)) {
            return;
        }

        try {
            Storage::disk('local')->delete($paths);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
