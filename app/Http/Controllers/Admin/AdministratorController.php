<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdministratorRequest;
use App\Http\Requests\Admin\UpdateAdministratorRequest;
use App\Models\Administrator;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdministratorController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');

        $administrators = Administrator::query()
            ->with('user.role')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('full_name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery
                                ->where('business_code', 'like', '%'.$search.'%')
                                ->orWhere('phone', 'like', '%'.$search.'%');
                        });
                });
            })
            ->when(
                in_array($status, ['ACTIVE', 'LOCKED'], true),
                function ($query) use ($status) {
                    $query->whereHas('user', function ($userQuery) use ($status) {
                        $userQuery->where('status', $status);
                    });
                }
            )
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.administrators.index', compact(
            'administrators',
            'search',
            'status'
        ));
    }

    public function create()
    {
        return view('admin.administrators.create');
    }

    public function store(StoreAdministratorRequest $request)
    {
        $data = $request->validated();

        $administrator = DB::transaction(function () use ($data) {
            /*
             * Khóa bản ghi vai trò Administrator để các yêu cầu
             * tạo quản trị viên thông qua module này được xử lý lần lượt.
             */
            $role = Role::query()
                ->where('name', 'Administrator')
                ->where('status', 'ACTIVE')
                ->lockForUpdate()
                ->first();

            if (! $role) {
                throw ValidationException::withMessages([
                    'role' => 'Chưa có vai trò quản trị viên đang hoạt động. Vui lòng báo người phụ trách hệ thống.',
                ]);
            }

            /*
             * Sinh mã QT tiếp theo từ dữ liệu thực tế.
             * Không giả định ID tài khoản và mã nghiệp vụ giống nhau.
*/
            $lastCode = User::query()
                ->where('business_code', 'like', 'QT%')
                ->orderByDesc('business_code')
                ->value('business_code');

            $lastNumber = 0;

            if (
                is_string($lastCode)
                && preg_match('/^QT([0-9]+)$/', $lastCode, $matches)
            ) {
                $lastNumber = (int) $matches[1];
            }

            $businessCode = 'QT'.str_pad(
                (string) ($lastNumber + 1),
                6,
                '0',
                STR_PAD_LEFT
            );

            $user = User::create([
                'business_code' => $businessCode,
                'phone' => $data['phone'],
                'password' => $data['password'],
                'role_id' => $role->id,
                'status' => $data['status'],
            ]);

            /*
             * users.status dùng ACTIVE / LOCKED.
             * administrators.status dùng ACTIVE / INACTIVE.
             */
            return Administrator::create([
                'user_id' => $user->id,
                'full_name' => trim($data['full_name']),
                'email' => $this->nullableText($data['email'] ?? null),
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'gender' => $data['gender'] ?? null,
                'avatar_path' => null,
                'address' => $this->nullableText($data['address'] ?? null),
                'status' => $data['status'] === 'ACTIVE'
                    ? 'ACTIVE'
                    : 'INACTIVE',
            ]);
        }, 3);

        return redirect()
            ->route('admin.administrators.show', $administrator)
            ->with('success', 'Thêm quản trị viên thành công.');
    }

    public function show(Administrator $administrator)
    {
        $administrator->load('user.role');

        return view('admin.administrators.show', compact('administrator'));
    }

    public function edit(Administrator $administrator)
    {
        $administrator->load('user.role');

        return view('admin.administrators.edit', compact('administrator'));
    }

    public function update(
        UpdateAdministratorRequest $request,
        Administrator $administrator
    ) {
        $data = $request->validated();

        DB::transaction(function () use ($administrator, $data) {
            $profile = Administrator::query()
                ->lockForUpdate()
                ->findOrFail($administrator->id);

            $user = User::query()
                ->lockForUpdate()
                ->findOrFail($profile->user_id);

            $user->update([
                'phone' => $data['phone'],
            ]);

            $profile->update([
                'full_name' => trim($data['full_name']),
                'email' => $this->nullableText($data['email'] ?? null),
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'gender' => $data['gender'] ?? null,
                'address' => $this->nullableText($data['address'] ?? null),
            ]);
        });

        return redirect()
            ->route('admin.administrators.show', $administrator)
            ->with('success', 'Cập nhật thông tin quản trị viên thành công.');
    }

    public function updateStatus(Request $request, Administrator $administrator)
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

        DB::transaction(function () use ($administrator, $data) {
            /*
             * Khóa vai trò để tuần tự hóa các thao tác khóa/mở
             * thực hiện qua module quản trị viên này.
             */
            $role = Role::query()
                ->where('name', 'Administrator')
                ->lockForUpdate()
                ->firstOrFail();

            $profile = Administrator::query()
                ->lockForUpdate()
                ->findOrFail($administrator->id);

            $user = User::query()
                ->lockForUpdate()
                ->findOrFail($profile->user_id);

            if ($user->role_id !== $role->id) {
                throw ValidationException::withMessages([
                    'status' => 'Tài khoản này không thuộc vai trò quản trị viên.',
                ]);
            }

            if (
                $data['status'] === 'LOCKED'
                && $user->id === Auth::id()
            ) {
                throw ValidationException::withMessages([
                    'status' => 'Bạn không thể tự khóa tài khoản đang đăng nhập.',
                ]);
            }

            if ($data['status'] === 'LOCKED') {
                $activeCount = User::query()
                    ->where('role_id', $role->id)
                    ->where('status', 'ACTIVE')
                    ->whereHas('administrator', function ($query) {
                        $query->where('status', 'ACTIVE');
                    })
                    ->count();

                if (
                    $user->status === 'ACTIVE'
                    && $profile->status === 'ACTIVE'
                    && $activeCount <= 1
                ) {
                    throw ValidationException::withMessages([
                        'status' => 'Không thể khóa quản trị viên hoạt động cuối cùng.',
                    ]);
                }
            }

            $user->update([
                'status' => $data['status'],
            ]);

            $profile->update([
                'status' => $data['status'] === 'ACTIVE'
                    ? 'ACTIVE'
                    : 'INACTIVE',
            ]);
        }, 3);

        return redirect()
            ->route('admin.administrators.show', $administrator)
            ->with(
                'success',
                $data['status'] === 'ACTIVE'
                    ? 'Mở khóa quản trị viên thành công.'
                    : 'Khóa quản trị viên thành công.'
            );
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
