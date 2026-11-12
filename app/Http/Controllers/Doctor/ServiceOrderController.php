<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Admin\ServiceOrderController as AdminServiceOrderController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreServiceOrderRequest;
use App\Models\MedicalService;
use App\Models\MedicalVisit;
use App\Models\ServiceOrderItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ServiceOrderController extends Controller
{
    public function create(Request $request, int $visit): View
    {
        $doctor = $request->user()->doctor;
        // Chỉ cho bác sĩ truy cập lượt được phân công, kể cả khi nhập trực tiếp URL.
        $record = MedicalVisit::whereKey($visit)->where('doctor_id', $doctor->id)
            ->with(['patient', 'status', 'appointment'])->firstOrFail();
        $services = MedicalService::where('status', 'ACTIVE')->with('serviceGroups')
            ->orderBy('display_order')->orderBy('name')->get();
        return view('doctor.visits.order-create', compact('doctor', 'record', 'services'));
    }

    public function store(StoreServiceOrderRequest $request, int $visit, AdminServiceOrderController $adminOrders): RedirectResponse
    {
        $doctorId = $request->user()->doctor->id;
        $serviceIds = array_map('intval', $request->validated('medical_service_ids'));

        DB::transaction(function () use ($request, $visit, $doctorId, $serviceIds, $adminOrders): void {
            // Khóa lượt khám để kiểm tra lại quyền và chống gửi lặp cùng lúc.
            $record = MedicalVisit::whereKey($visit)->where('doctor_id', $doctorId)
                ->with('status')->lockForUpdate()->firstOrFail();
            $record->assertNotBeforeAppointmentDay();
            if (! $record->canOrderServices()) {
                throw ValidationException::withMessages(['visit' => 'Lượt khám chưa ở trạng thái cho phép chỉ định dịch vụ.']);
            }
            // Không tạo lại dịch vụ còn đang chờ xử lý trong cùng lượt khám.
            $alreadyOrdered = ServiceOrderItem::query()->whereIn('medical_service_id', $serviceIds)
                ->whereHas('serviceOrder', fn ($query) => $query->where('medical_visit_id', $record->id)->where('status', '!=', 'CANCELLED'))
                ->unresolved()->exists();
            if ($alreadyOrdered) {
                throw ValidationException::withMessages(['medical_service_ids' => 'Một dịch vụ đã chọn đang chờ xử lý trong lượt khám này. Vui lòng kiểm tra chỉ định hiện có.']);
            }

            // Tái sử dụng nghiệp vụ Admin hiện hữu để lập chỉ định, khoản thu và đồng bộ trạng thái.
            // Giao dịch bên ngoài giữ khóa phân công cho đến khi toàn bộ thao tác kết thúc.
            $adminOrders->store($request, $record);
        }, 3);

        return redirect()->route('doctor.visits.show', $visit)->with('success', 'Đã tạo chỉ định và khoản cần thanh toán.');
    }
}
