<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ClinicalDocument;
use App\Models\MedicalVisit;
use App\Models\Employee;
use App\Models\EmployeeDetail;
use App\Models\Payable;
use App\Models\User;
use App\Models\ServiceOrderItem;
use App\Models\ServiceOrderProgress;
use App\Models\ServiceResult;
use App\Models\ServiceStatus;
use App\Support\EmployeeAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TechnicalServiceController extends Controller
{
    // Quyền được quyết định từ loại nhân viên hiện tại, không lấy từ dữ liệu gửi lên.
    private function context(Request $request): array
    {
        $employee = app(EmployeeAccess::class)->employee($request->user());
        abort_unless($employee, 403);
        return match ($employee->detail->employee_type) {
            'LAB_TECHNICIAN' => [$employee, 'XN', 'Xét nghiệm', 'employee.laboratory'],
            'IMAGING_TECHNICIAN' => [$employee, 'CDHA', 'Chẩn đoán hình ảnh', 'employee.imaging'],
            default => abort(403),
        };
    }

    // Lọc theo mã nhóm trong bảng danh mục và người thực hiện đã được phân công.
    private function scopedItems($employee, string $group)
    {
        return ServiceOrderItem::query()
            ->whereHas('medicalService.serviceGroups', fn ($q) => $q->where('service_group_code', $group))
            ->whereDoesntHave('medicalService.serviceGroups', fn ($q) => $q->where('service_group_code', $group === 'XN' ? 'CDHA' : 'XN'))
            ->where(fn ($q) => $q->whereNull('performed_by_employee_id')->orWhere('performed_by_employee_id', $employee->id));
    }

    public function index(Request $request)
    {
        [$employee, $group, $title, $routePrefix] = $this->context($request);
        $filters = $request->validate([
            'patient' => ['nullable', 'string', 'max:120'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['nullable', 'in:PENDING,PROCESSING,COMPLETED,CANCELLED'],
        ]);
        // Tìm theo tên hoặc mã bệnh nhân trong phạm vi nhân viên được phép xem.
        $patientSearch = trim($filters['patient'] ?? '');
        $items = $this->scopedItems($employee, $group)
            ->with(['medicalService', 'serviceStatus', 'serviceOrder.medicalVisit.patient', 'payable.paymentStatus'])
            ->when($patientSearch !== '', fn ($q) => $q->whereHas('serviceOrder.medicalVisit.patient', fn ($patient) => $patient->where(fn ($query) => $query
                ->where('full_name', 'like', '%'.$patientSearch.'%')
                ->orWhere('patient_code', 'like', '%'.$patientSearch.'%'))))
            ->when($filters['date'] ?? null, fn ($q, $date) => $q->whereHas('serviceOrder', fn ($order) => $order->whereDate('ordered_at', $date)))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->whereHas('serviceStatus', fn ($s) => $s->where('name', $status)))
            ->orderByDesc('id')->paginate(15)->withQueryString();
        return view('employee.technical.index', compact('employee', 'title', 'routePrefix', 'items', 'filters'));
    }

    public function show(Request $request, int $item)
    {
        [$employee, $group, $title, $routePrefix] = $this->context($request);
        $record = $this->scopedItems($employee, $group)->with(['medicalService', 'serviceStatus', 'serviceOrder.medicalVisit.patient', 'performer', 'payable.paymentStatus', 'result'])->findOrFail($item);
        return view('employee.technical.show', compact('employee', 'title', 'routePrefix', 'record'));
    }

    // Khóa lượt khám trước chỉ định, đồng thời kiểm tra lại quyền sau khi lấy khóa.
    private function lockedItem(Request $request, int $id, string $expectedGroup): ServiceOrderItem
    {
        // Khóa tài khoản và loại nhân viên trước khi ghi để quyền không đổi giữa chừng.
        User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
        $lockedEmployee = Employee::where('user_id', $request->user()->id)->lockForUpdate()->firstOrFail();
        EmployeeDetail::where('employee_id', $lockedEmployee->id)->lockForUpdate()->firstOrFail();
        $visitId = ServiceOrderItem::query()->whereKey($id)->join('service_orders', 'service_orders.id', '=', 'service_order_items.service_order_id')->value('service_orders.medical_visit_id');
        abort_unless($visitId, 404);
        $visit = MedicalVisit::with('status')->whereKey($visitId)->lockForUpdate()->firstOrFail();
        $visit->assertServiceDataWritable();
        [$employee, $group] = $this->context($request);
        abort_unless($group === $expectedGroup, 403);
        return $this->scopedItems($employee, $group)->with(['serviceStatus', 'serviceOrder', 'payable.paymentStatus', 'result'])->whereKey($id)->lockForUpdate()->firstOrFail();
    }

    public function start(Request $request, int $item)
    {
        [$employee, $group, , $routePrefix] = $this->context($request);
        DB::transaction(function () use ($request, $item, $employee, $group) {
            $record = $this->lockedItem($request, $item, $group);
            if ($record->serviceStatus?->name !== 'PENDING' || ! $record->serviceStatus?->allows_transition || $record->serviceOrder?->status === 'CANCELLED') {
                throw ValidationException::withMessages(['service' => 'Chỉ định không còn ở trạng thái chờ thực hiện.']);
            }
            // Đọc lại khoản phải thu khi đang giữ khóa để tránh dùng trạng thái cũ.
            $payable = $record->payable ? Payable::with('paymentStatus')->whereKey($record->payable->id)->lockForUpdate()->first() : null;
            if (! $payable?->paymentStatus?->allows_service_execution) {
                throw ValidationException::withMessages(['service' => 'Dịch vụ chưa đủ điều kiện thanh toán để thực hiện.']);
            }
            $status = ServiceStatus::where('name', 'PROCESSING')->where('is_active', true)->first();
            if (! $status) throw ValidationException::withMessages(['service' => 'Chưa cấu hình trạng thái đang thực hiện.']);
            $record->update(['service_status_id' => $status->id, 'started_at' => now(), 'performed_by_employee_id' => $employee->id]);
            $this->audit($request, $record, 'Bắt đầu thực hiện dịch vụ');
        }, 3);
        return redirect()->route($routePrefix.'.show', $item)->with('success', 'Đã bắt đầu thực hiện dịch vụ.');
    }

    public function save(Request $request, int $item)
    {
        [$employee, $group, , $routePrefix] = $this->context($request);
        $data = $request->validate([
            'result_version' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/'],
            'result_summary' => ['nullable', 'string', 'max:10000'],
            'parameters_present' => ['sometimes', 'in:1'],
            'parameters' => ['sometimes', 'array', 'max:30'],
            'parameters.*' => ['array:name,value,unit'],
            'parameters.*.name' => ['nullable', 'string', 'max:120'],
            'parameters.*.value' => ['nullable', function (string $attribute, mixed $value, \Closure $fail): void {
                // Giá trị có thể là chữ, số hoặc số 0; từ chối cấu trúc phức tạp.
                if ((! is_string($value) && ! is_int($value) && ! is_float($value)) || mb_strlen((string) $value) > 200) {
                    $fail('Giá trị chỉ số phải là chữ hoặc số và không quá 200 ký tự.');
                }
            }],
            'parameters.*.unit' => ['nullable', 'string', 'max:60'],
            'result_pdf' => ClinicalDocument::pdfRules(),
            'result_image' => ClinicalDocument::imageRules(),
        ]);
        // Chỉ kỹ thuật viên xét nghiệm được gửi bảng chỉ số; các dòng phải đủ tên và giá trị.
        if ($group !== 'XN' && ($request->has('parameters') || $request->has('parameters_present'))) {
            throw ValidationException::withMessages(['parameters' => 'Chỉ xét nghiệm mới nhập bảng chỉ số.']);
        }
        $parameters = [];
        foreach ($data['parameters'] ?? [] as $index => $row) {
            $name = trim($row['name'] ?? '');
            $value = trim((string) ($row['value'] ?? ''));
            $unit = trim($row['unit'] ?? '');
            if ($name === '' && $value === '' && $unit === '') continue;
            if ($name === '' || $value === '') {
                throw ValidationException::withMessages(['parameters.'.$index => 'Mỗi chỉ số cần có tên và giá trị.']);
            }
            $parameters[] = ['name' => $name, 'value' => $value, 'unit' => $unit];
        }
        // Ghi kết quả và tệp trong một giao dịch; không cho nhân viên tự xác nhận y khoa.
        ClinicalDocument::transaction(function (ClinicalDocument $documents) use ($request, $item, $employee, $group, $data, $parameters) {
            $record = $this->lockedItem($request, $item, $group);
            if ($record->serviceStatus?->name !== 'PROCESSING' || (int) $record->performed_by_employee_id !== (int) $employee->id || $record->serviceOrder?->status === 'CANCELLED') {
                throw ValidationException::withMessages(['service' => 'Chỉ người đang thực hiện mới được nhập kết quả khi dịch vụ đang xử lý.']);
            }
            $result = ServiceResult::where('service_order_item_id', $record->id)->lockForUpdate()->first();
            // Sau khi khóa chỉ định, từ chối nội dung gửi từ biểu mẫu đã bị thay đổi.
            if (! hash_equals(ServiceResult::editVersion($result), $data['result_version'])) {
                throw ValidationException::withMessages(['service' => 'Kết quả đã thay đổi. Vui lòng tải lại trang trước khi lưu.']);
            }
            if ($result && ($result->verified_by_user_id || $result->patient_publication_status === 'PUBLISHED')) {
                throw ValidationException::withMessages(['service' => 'Kết quả đã xác minh hoặc đã công bố, không thể sửa trực tiếp.']);
            }
            // Không ghi đè dữ liệu chỉ số cũ có cấu trúc chưa được nhận diện.
            if ($request->has('parameters_present') && $result && ServiceResult::editableParameters($result->result_parameters) === null) {
                throw ValidationException::withMessages(['parameters' => 'Chỉ số cũ có định dạng khác và được giữ nguyên.']);
            }
            $paths = $documents->store($request, 'service-results/'.$record->id, ClinicalDocument::RESULT_FILES);
            $summary = trim($data['result_summary'] ?? '');
            $values = array_merge(['result_summary' => $summary !== '' ? $summary : null, 'resulted_at' => now()], $paths);
            if ($request->has('parameters_present')) $values['result_parameters'] = $parameters;
            $content = array_merge($result?->getAttributes() ?? [], $values);
            if (! ServiceResult::hasContentValues($content, $record->id)) {
                throw ValidationException::withMessages(['result_summary' => 'Nhập nội dung kỹ thuật hoặc tải lên tệp kết quả.']);
            }
            if ($result) {
                $result->update($values);
            } else {
                ServiceResult::create(array_merge(['service_order_item_id' => $record->id, 'verified_by_user_id' => null], $values));
            }
            $this->audit($request, $record, 'Lưu kết quả kỹ thuật');
        });
        return redirect()->route($routePrefix.'.show', $item)->with('success', 'Đã lưu kết quả kỹ thuật.');
    }

    public function complete(Request $request, int $item)
    {
        [$employee, $group, , $routePrefix] = $this->context($request);
        DB::transaction(function () use ($request, $item, $employee, $group) {
            $record = $this->lockedItem($request, $item, $group);
            if ($record->serviceStatus?->name !== 'PROCESSING' || ! $record->serviceStatus?->allows_transition || (int) $record->performed_by_employee_id !== (int) $employee->id || $record->serviceOrder?->status === 'CANCELLED') {
                throw ValidationException::withMessages(['service' => 'Dịch vụ không còn ở trạng thái cho phép hoàn tất.']);
            }
            if (! $record->result?->hasContent()) throw ValidationException::withMessages(['service' => 'Cần lưu kết quả trước khi hoàn tất.']);
            $status = ServiceStatus::where('name', 'COMPLETED')->where('is_active', true)->first();
            if (! $status) throw ValidationException::withMessages(['service' => 'Chưa cấu hình trạng thái hoàn thành.']);
            $record->update(['service_status_id' => $status->id, 'completed_at' => now()]);
            $this->audit($request, $record, 'Hoàn tất dịch vụ');
            // Dùng quy trình chung để cập nhật phiếu và hàng đợi quay lại bác sĩ.
            app(ServiceOrderProgress::class)->synchronize($record->service_order_id);
        }, 3);
        return redirect()->route($routePrefix.'.show', $item)->with('success', 'Đã hoàn tất dịch vụ.');
    }

    public function document(Request $request, int $item, string $type): StreamedResponse
    {
        [$employee, $group] = $this->context($request);
        abort_unless(in_array($type, ['pdf', 'image'], true), 404);
        $record = $this->scopedItems($employee, $group)->with('result')->findOrFail($item);
        abort_unless($record->result, 404);
        $path = $type === 'pdf' ? $record->result->result_pdf_path : $record->result->result_image_path;
        return app(ClinicalDocument::class)->response($path, 'service-results/'.$record->id, $type);
    }

    private function audit(Request $request, ServiceOrderItem $item, string $description): void
    {
        AuditLog::create(['user_id' => $request->user()->id, 'role_name_snapshot' => 'Employee', 'action' => 'UPDATE', 'entity_type' => 'service_order_items', 'entity_id' => $item->id, 'description' => $description, 'ip_address' => $request->ip(), 'user_agent' => mb_substr((string) $request->userAgent(), 0, 500), 'performed_at' => now()]);
    }
}
