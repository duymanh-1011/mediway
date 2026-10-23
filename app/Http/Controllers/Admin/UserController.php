<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Administrator;
use App\Models\Doctor;
use App\Models\Employee;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    private const ACCOUNT_ROLES = [
        'Patient' => 'BN',
        'Doctor' => 'BS',
        'Employee' => 'NV',
    ];

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $roleId = $request->query('role_id');
        $status = (string) $request->query('status', '');

        $users = User::query()
            ->with('role')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('business_code', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%');
                });
            })
            ->when(
                filter_var($roleId, FILTER_VALIDATE_INT) !== false
                    && (int) $roleId > 0,
                fn ($query) => $query->where('role_id', (int) $roleId)
            )
            ->when(
                in_array($status, ['ACTIVE', 'LOCKED'], true),
                fn ($query) => $query->where('status', $status)
            )
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $roles = Role::query()
            ->orderBy('name')
            ->get();

        return view('admin.users.index', compact(
            'users',
            'roles',
            'search',
            'roleId',
            'status'
        ));
    }

    public function create()
    {
        $roles = Role::query()
            ->whereIn('name', array_keys(self::ACCOUNT_ROLES))
            ->where('status', 'ACTIVE')
            ->orderBy('name')
            ->get();

        return view('admin.users.create', compact('roles'));
    }

    public function store(StoreUserRequest $request)
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data) {
            $role = Role::query()
                ->whereKey($data['role_id'])
                ->where('status', 'ACTIVE')
                ->lockForUpdate()
                ->first();

            if (! $role || ! array_key_exists($role->name, self::ACCOUNT_ROLES)) {
                throw ValidationException::withMessages([
                    'role_id' => 'Vui lòng chọn vai trò bệnh nhân, bác sĩ hoặc nhân viên. Để thêm quản trị viên, hãy vào mục Quản trị viên.',
                ]);
            }

            $prefix = self::ACCOUNT_ROLES[$role->name];

            $lastCode = User::query()
                ->where('business_code', 'like', $prefix.'%')
                ->orderByDesc('business_code')
                ->value('business_code');

            $lastNumber = 0;

            if (
                is_string($lastCode)
                && preg_match(
                    '/^'.preg_quote($prefix, '/').'([0-9]+)$/',
                    $lastCode,
                    $matches
                )
            ) {
                $lastNumber = (int) $matches[1];
            }

            $businessCode = $prefix.str_pad(
                (string) ($lastNumber + 1),
                6,
                '0',
                STR_PAD_LEFT
            );

            return User::create([
                'business_code' => $businessCode,
                'phone' => $data['phone'],
                'password' => $data['password'],
                'role_id' => $role->id,
                'status' => $data['status'],
            ]);
        }, 3);

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', 'Tạo tài khoản thành công.');
    }

    public function show(User $user)
    {
        /*
         * Chỉ tải những Model hiện đã tồn tại.
         * Các Model Patient / Doctor / Employee sẽ được bổ sung
         * vào những ngày triển khai module hồ sơ tương ứng.
         */
        $relations = [
            'role',
            'administrator',
        ];

        if (class_exists(Patient::class)) {
            $relations[] = 'patient';
        }

        if (class_exists(Doctor::class)) {
            $relations[] = 'doctor';
        }

        if (class_exists(Employee::class)) {
            $relations[] = 'employee';
        }

        $user->load($relations);

        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        $user->load('role');

        return view('admin.users.edit', compact('user'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $request->validated();

        $user->update([
            'phone' => $data['phone'],
        ]);

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', 'Cập nhật số điện thoại thành công.');
    }

    public function updateStatus(Request $request, User $user)
    {
        $data = $request->validate([
            'status' => [
                'required',
                Rule::in(['ACTIVE', 'LOCKED']),
            ],
        ], [
            'status.required' => 'Vui lòng chọn trạng thái.',
            'status.in' => 'Vui lòng chọn lại trạng thái từ các lựa chọn có sẵn.',
        ]);

        if (
            $data['status'] === 'LOCKED'
            && $user->id === Auth::id()
        ) {
            throw ValidationException::withMessages([
                'status' => 'Bạn không thể tự khóa tài khoản đang đăng nhập.',
            ]);
        }

        DB::transaction(function () use ($user, $data) {
            $administratorRole = Role::query()
                ->where('name', 'Administrator')
                ->lockForUpdate()
                ->firstOrFail();

            $lockedUser = User::query()
                ->lockForUpdate()
                ->findOrFail($user->id);

            if (
                $data['status'] === 'LOCKED'
                && $lockedUser->id === Auth::id()
            ) {
                throw ValidationException::withMessages([
                    'status' => 'Bạn không thể tự khóa tài khoản đang đăng nhập.',
                ]);
            }

            if ($lockedUser->role_id !== $administratorRole->id) {
                $lockedUser->update([
                    'status' => $data['status'],
                ]);

                return;
            }

            $profile = Administrator::query()
                ->where('user_id', $lockedUser->id)
                ->lockForUpdate()
                ->first();

            if (! $profile) {
                throw ValidationException::withMessages([
                    'status' => 'Tài khoản quản trị viên chưa có hồ sơ liên kết.',
                ]);
            }

            if (
                $data['status'] === 'LOCKED'
                && $lockedUser->status === 'ACTIVE'
                && $profile->status === 'ACTIVE'
            ) {
                $activeAdministrators = User::query()
                    ->where('role_id', $administratorRole->id)
                    ->where('status', 'ACTIVE')
                    ->whereHas('administrator', function ($query) {
                        $query->where('status', 'ACTIVE');
                    })
                    ->count();

                if ($activeAdministrators <= 1) {
                    throw ValidationException::withMessages([
                        'status' => 'Không thể khóa quản trị viên hoạt động cuối cùng.',
                    ]);
                }
            }

            $lockedUser->update([
                'status' => $data['status'],
            ]);

            $profile->update([
                'status' => $data['status'] === 'ACTIVE'
                    ? 'ACTIVE'
                    : 'INACTIVE',
            ]);
        }, 3);

        return redirect()
            ->route('admin.users.show', $user)
            ->with(
                'success',
                $data['status'] === 'ACTIVE'
                    ? 'Mở khóa tài khoản thành công.'
                    : 'Khóa tài khoản thành công.'
            );
    }

    public function resetPassword(Request $request, User $user)
    {
        $data = $request->validate([
            'password' => [
                'required',
                'string',
                'min:8',
                'max:72',
                'confirmed',
            ],
        ], [
            'password.required' => 'Vui lòng nhập mật khẩu mới.',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'password.max' => 'Mật khẩu không được quá 72 ký tự.',
            'password.confirmed' => 'Mật khẩu nhập lại chưa khớp. Vui lòng kiểm tra cả hai ô mật khẩu.',
        ]);

        $user->update([
            'password' => $data['password'],
        ]);

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', 'Đặt lại mật khẩu thành công.');
    }
}
