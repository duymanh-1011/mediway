<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\EmployeeDetail;
use App\Models\User;
use App\Rules\PasswordByteLimit;
use App\Support\EmployeeAccess;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class WorkspaceController extends Controller
{
    public function home(Request $request)
    {
        $employee = app(EmployeeAccess::class)->employee($request->user());

        $workspace = $request->session()->get('employee_workspace_mode');
        return in_array($workspace, ['reception', 'cashier'], true)
            ? view('employee.dashboard', compact('employee', 'workspace'))
            : view('employee.home', compact('employee'));
    }

    public function selectWorkspace(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'workspace' => ['required', 'in:reception,cashier'],
        ], [
            'workspace.required' => 'Vui lòng chọn một chức năng.',
            'workspace.in' => 'Chức năng được chọn không hợp lệ.',
        ]);

        $request->session()->put('employee_workspace_mode', $data['workspace']);

        return redirect()->route('employee.home');
    }

    public function profile(Request $request)
    {
        $employee = app(EmployeeAccess::class)->employee($request->user());
        return view('employee.profile', compact('employee'));
    }

    public function editProfile(Request $request)
    {
        $employee = app(EmployeeAccess::class)->employee($request->user());
        return view('employee.profile-edit', compact('employee'));
    }

    public function updateProfile(Request $request)
    {
        $data = $request->validate([
            'email' => ['nullable', 'email', 'max:150'],
            'current_address' => ['nullable', 'string', 'max:255'],
            'permanent_address' => ['nullable', 'string', 'max:255'],
            'employee_type' => ['prohibited'], 'role_id' => ['prohibited'],
            'employee_code' => ['prohibited'], 'user_id' => ['prohibited'], 'status' => ['prohibited'],
        ], [
            'email.email' => 'Email không đúng định dạng.',
            'email.max' => 'Email không được vượt quá 150 ký tự.',
            'current_address.max' => 'Địa chỉ hiện tại không được vượt quá 255 ký tự.',
            'permanent_address.max' => 'Địa chỉ thường trú không được vượt quá 255 ký tự.',
            '*.string' => 'Thông tin cần nhập phải là văn bản.',
            '*.prohibited' => 'Bạn không có quyền thay đổi thông tin quản trị.',
        ]);
        $changed = DB::transaction(function () use ($request, $data) {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $employee = Employee::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
            abort_unless(app(EmployeeAccess::class)->employee($user), 403);
            $detail = $employee->detail()->lockForUpdate()->first();
            if (! $detail) {
                $detail = new EmployeeDetail(['employee_id' => $employee->id]);
            }
            $detail->fill($data);
            $fields = array_keys($detail->getDirty());
            if ($fields === []) return false;
            $detail->save();
            AuditLog::create([
                'user_id' => $user->id, 'role_name_snapshot' => 'Employee', 'action' => 'UPDATE',
                'entity_type' => 'employee_details', 'entity_id' => $detail->id, 'entity_business_code' => $employee->employee_code,
                'old_values' => [], 'new_values' => ['updated_fields' => $fields],
                'description' => 'Nhân viên cập nhật thông tin liên hệ.', 'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500), 'performed_at' => now(),
            ]);
            return true;
        });
        return redirect()->route('employee.profile')->with('success', $changed ? 'Đã cập nhật thông tin liên hệ.' : 'Thông tin liên hệ không thay đổi.');
    }

    public function changePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'max:72', new PasswordByteLimit, 'confirmed', 'different:current_password'],
        ], [
            'current_password.required' => 'Vui lòng nhập mật khẩu hiện tại.',
            'current_password.current_password' => 'Mật khẩu hiện tại không đúng.',
            'password.required' => 'Vui lòng nhập mật khẩu mới.',
            'password.min' => 'Mật khẩu mới cần ít nhất 8 ký tự.',
            'password.max' => 'Mật khẩu mới không được vượt quá 72 ký tự.',
            'password.confirmed' => 'Xác nhận mật khẩu chưa khớp.',
            'password.different' => 'Mật khẩu mới phải khác mật khẩu hiện tại.',
        ]);
        $updatedUser = DB::transaction(function () use ($request, $data) {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            abort_unless(app(EmployeeAccess::class)->employee($user), 403);
            if (! Hash::check($data['current_password'], $user->password)) {
                throw ValidationException::withMessages(['current_password' => 'Mật khẩu hiện tại không đúng.']);
            }
            $user->update(['password' => $data['password']]);
            AuditLog::create([
                'user_id' => $user->id, 'role_name_snapshot' => 'Employee', 'action' => 'UPDATE',
                'entity_type' => 'users', 'entity_id' => $user->id, 'entity_business_code' => $user->business_code,
                'old_values' => [], 'new_values' => ['credential_changed' => true],
                'description' => 'Nhân viên đổi mật khẩu tài khoản.', 'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500), 'performed_at' => now(),
            ]);
            return $user;
        });
        // Keep this session authenticated; other sessions fail AuthenticateSession on their next request.
        \Illuminate\Support\Facades\Auth::setUser($updatedUser);
        $request->session()->regenerate();
        return redirect()->route('employee.profile')->with('success', 'Đã đổi mật khẩu thành công.');
    }
}
