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
            $patient = $registration->register($data['full_name'], $data['phone'], $data['password']);
        } catch (UniqueConstraintViolationException) {
            return back()->withErrors(['phone' => 'Số điện thoại đã được sử dụng.'])->withInput();
        }
        return redirect()->route('patient.login')->with('success', 'Đăng ký thành công. Bạn có thể đăng nhập bằng tài khoản mới.');
    }
}
