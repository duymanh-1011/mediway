<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\RegisterPatientRequest;
use App\Services\PatientAccountRegistrationService;
use Illuminate\Database\UniqueConstraintViolationException;

class RegistrationController extends Controller
{
    public function create() { return view('portal.auth.register'); }

    public function store(RegisterPatientRequest $request, PatientAccountRegistrationService $registration)
    {
        try {
            $data = $request->safe()->only(['full_name', 'phone', 'password']);
            $registration->register($data['full_name'], $data['phone'], $data['password']);
        } catch (UniqueConstraintViolationException) {
            return back()->withErrors(['phone' => 'Không thể hoàn tất đăng ký. Vui lòng kiểm tra lại thông tin và thử lại.'])->onlyInput('full_name', 'phone');
        }
        return redirect()->route('patient.login')->with('success', 'Đăng ký thành công. Bạn có thể đăng nhập bằng tài khoản mới.');
    }
}
