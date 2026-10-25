<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEmployeeRequest;
use App\Http\Requests\Admin\UpdateEmployeeRequest;
use App\Models\Employee;
use App\Models\EmployeeDetail;
use App\Models\EmployeeType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class EmployeeController extends Controller
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
        'department',
        'position',
        'employee_type',
        'hire_date',
        'termination_date',
        'years_of_service',
        'primary_shift',
        'work_location',
        'job_description',
        'professional_skills',
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

    /**
     * Danh sách nhân viên, tìm kiếm, lọc và phân trang.
     */
    public function index(Request $request)
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', 'in:ACTIVE,INACTIVE'],
            'department' => ['nullable', 'string', 'max:120'],
            'employee_type' => ['nullable', 'string', Rule::exists('employee_types', 'code')],
        ]);

        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');
        $department = (string) $request->query('department', '');
        $employeeType = (string) $request->query('employee_type', '');

        $employees = Employee::query()
            ->with(['user', 'detail'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner
                        ->where('employee_code', 'like', '%'.$search.'%')
                        ->orWhere('full_name', 'like', '%'.$search.'%')
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where(
                                'phone',
                                'like',
                                '%'.$search.'%'
                            );
                        })
                        ->orWhereHas('detail', function ($detailQuery) use ($search) {
                            $detailQuery
                                ->where('citizen_id_number', 'like', '%'.$search.'%')
                                ->orWhere('email', 'like', '%'.$search.'%')
                                ->orWhere('position', 'like', '%'.$search.'%');
                        });
                });
            })
            ->when(
                $status !== '',
                fn ($query) => $query->where('status', $status)
            )
            ->when(
                $department !== '',
                fn ($query) => $query->whereHas(
                    'detail',
                    fn ($detailQuery) => $detailQuery->where(
                        'department',
                        $department
                    )
                )
            )
            ->when(
                $employeeType !== '',
                fn ($query) => $query->whereHas(
                    'detail',
                    fn ($detailQuery) => $detailQuery->where(
                        'employee_type',
                        $employeeType
                    )
                )
            )
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $departments = EmployeeDetail::query()
            ->whereNotNull('department')
            ->where('department', '<>', '')
            ->distinct()
            ->orderBy('department')
            ->pluck('department');

        $employeeTypes = EmployeeType::orderBy('display_order')->get();

        return view('admin.employees.index', compact(
            'employees',
            'search',
            'status',
            'department',
            'employeeType',
            'departments',
            'employeeTypes'
        ));
    }

    /**
     * Form thêm nhân viên.
     */
    public function create()
    {
        $availableUsers = $this->availableEmployeeUsers();
        $employeeTypes = EmployeeType::orderBy('display_order')->get();

        return view('admin.employees.create', compact('availableUsers', 'employeeTypes'));
    }

    /**
     * Tạo tài khoản nếu cần, sau đó tạo nhân viên và hồ sơ chi tiết.
     */
    public function store(StoreEmployeeRequest $request)
    {
        $data = $request->validated();
        $uploadedPaths = [];

        try {
            $employee = DB::transaction(function () use (
                $request,
                $data,
                &$uploadedPaths
            ) {
                /*
                 * Khóa role Employee để các thao tác tạo qua
                 * Controller này tuần tự cấp mã NV.
                 */
                $role = Role::query()
                    ->where('name', 'Employee')
                    ->lockForUpdate()
                    ->first();

                if (! $role) {
                    throw ValidationException::withMessages([
                        'user_id' => 'Chưa có vai trò nhân viên trong hệ thống. Vui lòng báo người phụ trách hệ thống.',
                    ]);
                }

                $employeeStatus = $data['status'];

                if (! empty($data['user_id'])) {
                    $user = $this->validateAndLockEmployeeUser(
                        (int) $data['user_id'],
                        (int) $role->id
                    );

                    $employeeCode = $user->business_code;

                    if (Employee::query()
                        ->where('employee_code', $employeeCode)
                        ->exists()) {
                        throw ValidationException::withMessages([
                            'user_id' => 'Mã nhân viên đã tồn tại.',
                        ]);
                    }

                    // Giữ tài khoản đang bị khóa nếu hồ sơ mới ACTIVE.
                    // Không tự mở khóa một tài khoản đã bị khóa từ trước.
                    $user->update([
                        'status' => $employeeStatus === 'INACTIVE'
                            ? 'LOCKED'
                            : $user->status,
                    ]);
                } else {
                    $employeeCode = $this->nextEmployeeCode();

                    $user = User::create([
                        'business_code' => $employeeCode,
                        'phone' => $data['phone'],
                        // User model có cast password => hashed.
                        'password' => $data['password'],
                        'role_id' => $role->id,
                        'status' => $this->accountStatus($employeeStatus),
                    ]);
                }

                $employee = Employee::create([
                    'user_id' => $user->id,
                    'employee_code' => $employeeCode,
                    'full_name' => trim($data['full_name']),
                    'date_of_birth' => $data['date_of_birth'] ?? null,
                    'gender' => $data['gender'] ?? null,
                    'status' => $employeeStatus,
                ]);

                $newFiles = $this->storeUploadedFiles(
                    $request,
                    $employee,
                    $uploadedPaths
                );

                EmployeeDetail::create(array_merge(
                    ['employee_id' => $employee->id],
                    $this->detailData($data),
                    $newFiles
                ));

                return $employee;
            });
        } catch (Throwable $exception) {
            $this->deleteFiles($uploadedPaths);
            throw $exception;
        }

        return redirect()
            ->route('admin.employees.show', $employee)
            ->with('success', 'Thêm nhân viên và hồ sơ công việc thành công.');
    }

    /**
     * Trang chi tiết nhân viên.
     */
    public function show(Employee $employee)
    {
        $employee->load(['user.role', 'detail']);

        return view('admin.employees.show', compact('employee'));
    }

    /**
     * Form sửa thông tin nhân viên.
     */
    public function edit(Employee $employee)
    {
        $employee->load(['user', 'detail']);
        $employeeTypes = EmployeeType::orderBy('display_order')->get();

        return view('admin.employees.edit', compact('employee', 'employeeTypes'));
    }

    /**
     * Cập nhật hồ sơ nhân viên.
     * Không đổi user_id hoặc employee_code.
     */
    public function update(
        UpdateEmployeeRequest $request,
        Employee $employee
    ) {
        $data = $request->validated();
        $uploadedPaths = [];
        $oldPaths = [];

        try {
            DB::transaction(function () use (
                $request,
                $employee,
                $data,
                &$uploadedPaths,
                &$oldPaths
            ) {
                /*
                 * Khóa tài khoản liên kết và hồ sơ nhân viên.
                 * Transaction bảo đảm các thay đổi DB cùng thành công
                 * hoặc cùng rollback.
                 */
                $current = Employee::query()->findOrFail($employee->id);

                $user = User::query()
                    ->lockForUpdate()
                    ->findOrFail($current->user_id);

                $lockedEmployee = Employee::query()
                    ->lockForUpdate()
                    ->findOrFail($employee->id);

                if ((int) $lockedEmployee->user_id !== (int) $user->id) {
                    throw ValidationException::withMessages([
                        'status' => 'Tài khoản liên kết đã thay đổi. Vui lòng tải lại trang.',
                    ]);
                }

                $oldEmployeeStatus = $lockedEmployee->status;
                $newEmployeeStatus = $data['status'];

                $lockedEmployee->update([
                    'full_name' => trim($data['full_name']),
                    'date_of_birth' => $data['date_of_birth'] ?? null,
                    'gender' => $data['gender'] ?? null,
                    'status' => $newEmployeeStatus,
                ]);

                /*
                 * Chuyển sang INACTIVE: khóa tài khoản.
                 * Chuyển từ INACTIVE sang ACTIVE: mở tài khoản.
                 * Sửa thông tin khi hồ sơ vẫn ACTIVE:
                 * giữ nguyên trạng thái tài khoản để không vô tình
                 * mở khóa tài khoản bị khóa từ module Users.
                 */
                if ($newEmployeeStatus === 'INACTIVE') {
                    $user->update(['status' => 'LOCKED']);
                } elseif ($oldEmployeeStatus === 'INACTIVE') {
                    $user->update(['status' => 'ACTIVE']);
                }

                $detail = EmployeeDetail::query()
                    ->where('employee_id', $lockedEmployee->id)
                    ->lockForUpdate()
                    ->first();

                if (! $detail) {
                    $detail = new EmployeeDetail([
                        'employee_id' => $lockedEmployee->id,
                    ]);
                }

                $newFiles = $this->storeUploadedFiles(
                    $request,
                    $lockedEmployee,
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

        // Xóa tệp cũ chỉ sau khi DB đã cập nhật thành công.
        $this->deleteFiles($oldPaths);

        return redirect()
            ->route('admin.employees.show', $employee)
            ->with('success', 'Cập nhật hồ sơ nhân viên thành công.');
    }

    /**
     * Truy cập tài liệu nhân viên qua route có AdminMiddleware.
     */
    public function document(Employee $employee, string $type)
    {
        $column = self::DOCUMENT_TYPES[$type] ?? null;

        if (! $column) {
            abort(404);
        }

        $employee->loadMissing('detail');

        $path = $employee->detail?->{$column};

        if (! is_string($path) || $path === '') {
            abort(404);
        }

        $prefix = 'employee-documents/'.$employee->id.'/';

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
            'profile-pdf' => 'ho-so-nhan-vien',
            default => 'tai-lieu',
        };

        $filename .= '-'.$employee->employee_code;

        $extension = pathinfo($path, PATHINFO_EXTENSION);

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

    /**
     * Các tài khoản Employee chưa gắn hồ sơ nhân viên.
     */
    private function availableEmployeeUsers()
    {
        return User::query()
            ->whereHas('role', function ($query) {
                $query->where('name', 'Employee');
            })
            ->whereDoesntHave('employee')
            ->where('business_code', 'regexp', '^NV[0-9]{6,}$')
            ->orderBy('business_code')
            ->get([
                'id',
                'business_code',
                'phone',
                'status',
            ]);
    }

    /**
     * Kiểm tra vai trò, mã tài khoản và quan hệ nhân viên.
     */
    private function validateAndLockEmployeeUser(
        int $userId,
        int $roleId
    ): User {
        $user = User::query()
            ->lockForUpdate()
            ->find($userId);

        if (! $user) {
            throw ValidationException::withMessages([
                'user_id' => 'Tài khoản không tồn tại.',
            ]);
        }

        if ((int) $user->role_id !== $roleId) {
            throw ValidationException::withMessages([
                'user_id' => 'Tài khoản phải có vai trò nhân viên.',
            ]);
        }

        if (! preg_match('/^NV[0-9]{6,}$/', (string) $user->business_code)) {
            throw ValidationException::withMessages([
                'user_id' => 'Mã tài khoản nhân viên phải có dạng NV000001.',
            ]);
        }

        if (Employee::query()
            ->where('user_id', $user->id)
            ->exists()) {
            throw ValidationException::withMessages([
                'user_id' => 'Tài khoản này đã liên kết với nhân viên khác.',
            ]);
        }

        return $user;
    }

    /**
     * Sinh mã nhân viên từ cả bảng employees và users.
     */
    private function nextEmployeeCode(): string
    {
        $employeeCodes = Employee::query()
            ->where('employee_code', 'like', 'NV%')
            ->pluck('employee_code');

        $userCodes = User::query()
            ->where('business_code', 'like', 'NV%')
            ->pluck('business_code');

        $maxNumber = 0;

        foreach ($employeeCodes->concat($userCodes) as $code) {
            if (preg_match('/^NV([0-9]+)$/', (string) $code, $matches)) {
                $maxNumber = max(
                    $maxNumber,
                    (int) $matches[1]
                );
            }
        }

        return 'NV'.str_pad(
            (string) ($maxNumber + 1),
            6,
            '0',
            STR_PAD_LEFT
        );
    }

    private function accountStatus(string $employeeStatus): string
    {
        return $employeeStatus === 'ACTIVE'
            ? 'ACTIVE'
            : 'LOCKED';
    }

    /**
     * Chỉ nhận các cột thuộc bảng employee_details.
     */
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

    /**
     * Lưu ảnh và PDF vào private local storage.
     */
    private function storeUploadedFiles(
        Request $request,
        Employee $employee,
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
                'employee-documents/'.$employee->id,
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

    /**
     * Dọn dẹp file sau khi thay thế hoặc khi transaction lỗi.
     */
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
