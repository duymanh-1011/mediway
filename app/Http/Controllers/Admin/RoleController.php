<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RoleController extends Controller
{
    /**
     * Danh sách, tìm kiếm, lọc và phân trang.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');

        $roles = Role::query()
            ->withCount('users')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%');
                });
            })
            ->when(
                in_array($status, ['ACTIVE', 'INACTIVE'], true),
                fn ($query) => $query->where('status', $status)
            )
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.roles.index', compact(
            'roles',
            'search',
            'status'
        ));
    }

    /**
     * Form thêm.
     */
    public function create()
    {
        return view('admin.roles.create');
    }

    /**
     * Lưu vai trò mới.
     */
    public function store(StoreRoleRequest $request)
    {
        $data = $request->validated();

        $data['name'] = trim($data['name']);
        $data['description'] = isset($data['description'])
            ? trim($data['description'])
            : null;

        $role = Role::create($data);

        return redirect()
            ->route('admin.roles.show', $role)
            ->with('success', 'Thêm vai trò thành công.');
    }

    /**
     * Xem chi tiết.
     */
    public function show(Role $role)
    {
        $role->loadCount('users');

        $users = $role->users()
            ->orderBy('business_code')
            ->paginate(10);

        return view('admin.roles.show', compact(
            'role',
            'users'
        ));
    }

    /**
     * Form sửa.
     */
    public function edit(Role $role)
    {
        $role->loadCount('users');

        return view('admin.roles.edit', compact('role'));
    }

    /**
     * Cập nhật vai trò.
     */
    public function update(UpdateRoleRequest $request, Role $role)
    {
        $data = $request->validated();

        if ($role->isSystemRole()) {
            $role->update([
                'description' => isset($data['description'])
                    ? trim($data['description'])
                    : null,
            ]);

            return redirect()
                ->route('admin.roles.show', $role)
->with('success', 'Cập nhật mô tả vai trò hệ thống thành công.');
        }

        $name = trim($data['name']);
        $description = isset($data['description'])
            ? trim($data['description'])
            : null;

        $status = $data['status'];

        DB::transaction(function () use (
            $role,
            $name,
            $description,
            $status
        ) {
            $lockedRole = Role::query()
                ->lockForUpdate()
                ->findOrFail($role->id);

            if ($lockedRole->isSystemRole()) {
                throw ValidationException::withMessages([
                    'name' => 'Không được đổi tên hoặc trạng thái của vai trò hệ thống.',
                ]);
            }

            $usersCount = DB::table('users')
                ->where('role_id', $lockedRole->id)
                ->count();

            if ($usersCount > 0 && $status !== 'ACTIVE') {
                throw ValidationException::withMessages([
                    'status' => 'Không thể vô hiệu hóa vai trò đang có tài khoản liên kết.',
                ]);
            }

            $lockedRole->update([
                'name' => $name,
                'description' => $description,
                'status' => $status,
            ]);
        });

        return redirect()
            ->route('admin.roles.show', $role)
            ->with('success', 'Cập nhật vai trò thành công.');
    }

    /**
     * Xóa vai trò chưa được sử dụng.
     */
    public function destroy(Role $role)
    {
        try {
            DB::transaction(function () use ($role) {
                $lockedRole = Role::query()
                    ->lockForUpdate()
                    ->findOrFail($role->id);

                if ($lockedRole->isSystemRole()) {
                    throw ValidationException::withMessages([
                        'role' => 'Không được xóa vai trò hệ thống.',
                    ]);
                }

                $hasUsers = DB::table('users')
                    ->where('role_id', $lockedRole->id)
                    ->exists();

                if ($hasUsers) {
                    throw ValidationException::withMessages([
                        'role' => 'Vai trò đang được sử dụng. Không thể xóa.',
                    ]);
                }

                $lockedRole->delete();
            });
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Illuminate\Database\QueryException $exception) {
            return redirect()
                ->route('admin.roles.index')
                ->with(
                    'error',
                    'Không thể xóa vai trò vì dữ liệu đang được tham chiếu.'
                );
        }

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Xóa vai trò thành công.');
    }
}