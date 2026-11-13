<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Doctor;
use App\Models\User;
use App\Rules\PasswordByteLimit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class WorkspaceController extends Controller
{
    public function home(Request $request)
    {
        $doctor = $request->attributes->get('current_doctor');

        abort_unless($doctor, 403);

        return view('doctor.home', compact('doctor'));
    }

    public function profile(Request $request)
    {
        $doctor = $request->attributes->get('current_doctor');

        abort_unless($doctor, 403);

        return view('doctor.profile', compact('doctor'));
    }

    public function editProfile(Request $request)
    {
        $doctor = $request->attributes->get('current_doctor');

        abort_unless($doctor, 403);

        return view('doctor.profile-edit', compact('doctor'));
    }

    public function updateProfile(Request $request)
    {
        abort_unless($request->attributes->get('current_doctor'), 403);

        $data = $request->validate([
            'email' => ['nullable', 'email', 'max:150'],
            'current_address' => ['nullable', 'string', 'max:255'],
            'permanent_address' => ['nullable', 'string', 'max:255'],

            'doctor_code' => ['prohibited'],
            'user_id' => ['prohibited'],
            'specialty_id' => ['prohibited'],
            'status' => ['prohibited'],
            'role_id' => ['prohibited'],
            'practice_license_number' => ['prohibited'],
            'professional_qualification' => ['prohibited'],
            'current_workplace' => ['prohibited'],
        ], [
            'email.email' => 'Email không đúng định dạng.',
            'email.max' => 'Email không được vượt quá 150 ký tự.',
            'current_address.max' => 'Địa chỉ hiện tại không được vượt quá 255 ký tự.',
            'permanent_address.max' => 'Địa chỉ thường trú không được vượt quá 255 ký tự.',
            '*.string' => 'Thông tin cần nhập phải là văn bản.',
            '*.prohibited' => 'Bạn không có quyền thay đổi thông tin quản trị hoặc chuyên môn.',
        ]);

        $changed = DB::transaction(function () use ($request, $data) {
            $user = User::whereKey($request->user()->id)
                ->lockForUpdate()
                ->firstOrFail();

            $doctor = Doctor::where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $detail = $doctor->detail()
                ->lockForUpdate()
                ->firstOrFail();

            $detail->fill([
                'email' => $data['email'] ?? null,
                'current_address' => $data['current_address'] ?? null,
                'permanent_address' => $data['permanent_address'] ?? null,
            ]);

            $fields = array_keys($detail->getDirty());

            if ($fields === []) {
                return false;
            }

            $detail->save();

            AuditLog::create([
                'user_id' => $user->id,
                'role_name_snapshot' => 'Doctor',
                'action' => 'UPDATE',
                'entity_type' => 'doctor_details',
                'entity_id' => $detail->id,
                'entity_business_code' => $doctor->doctor_code,
                'old_values' => [],
                'new_values' => ['updated_fields' => $fields],
                'description' => 'Bác sĩ cập nhật thông tin liên hệ.',
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
                'performed_at' => now(),
            ]);

            return true;
        });

        return redirect()
            ->route('doctor.profile')
            ->with(
                'success',
                $changed
                    ? 'Đã cập nhật thông tin liên hệ.'
                    : 'Thông tin liên hệ không thay đổi.'
            );
    }

    public function changePassword(Request $request)
    {
        abort_unless($request->attributes->get('current_doctor'), 403);

        $data = $request->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:72',
                new PasswordByteLimit,
                'confirmed',
                'different:current_password',
            ],
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
            $user = User::whereKey($request->user()->id)
                ->lockForUpdate()
                ->firstOrFail();

            Doctor::where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! Hash::check($data['current_password'], $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => 'Mật khẩu hiện tại không đúng.',
                ]);
            }

            $user->update([
                'password' => $data['password'],
            ]);

            AuditLog::create([
                'user_id' => $user->id,
                'role_name_snapshot' => 'Doctor',
                'action' => 'UPDATE',
                'entity_type' => 'users',
                'entity_id' => $user->id,
                'entity_business_code' => $user->business_code,
                'old_values' => [],
                'new_values' => ['credential_changed' => true],
                'description' => 'Bác sĩ đổi mật khẩu tài khoản.',
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
                'performed_at' => now(),
            ]);

            return $user;
        });

        Auth::setUser($updatedUser);
        $request->session()->regenerate();

        return redirect()
            ->route('doctor.profile')
            ->with('success', 'Đã đổi mật khẩu thành công.');
    }
}
