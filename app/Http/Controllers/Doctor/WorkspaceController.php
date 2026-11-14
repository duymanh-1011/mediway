<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Support\DoctorAccess;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class WorkspaceController extends Controller
{
    public function home(Request $request)
    {
        $employee = app(DoctorAccess::class)->doctor($request->user());

        if ($request->session()->get('doctor_workspace_mode') === 'services') {
            return redirect()->route('doctor.services.index');
        }

        if ($request->session()->get('doctor_workspace_mode') === 'examination') {
            return view('doctor.examination-placeholder', compact('employee'));
        }

        return view('doctor.home', compact('employee'));
    }

    public function selectWorkspace(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'workspace' => ['required', 'in:examination,services'],
        ], [
            'workspace.required' => 'Vui lòng chọn một chức năng.',
            'workspace.in' => 'Chức năng được chọn không hợp lệ.',
        ]);

        $current = $request->session()->get('doctor_workspace_mode');
        if ($current !== null && $current !== $data['workspace']) {
            abort(403, 'Bạn đã chọn chức năng cho phiên đăng nhập này. Vui lòng đăng xuất nếu cần đổi chức năng.');
        }

        $request->session()->put('doctor_workspace_mode', $data['workspace']);
        $request->session()->regenerate();

        return redirect()->route('doctor.home');
    }
}
