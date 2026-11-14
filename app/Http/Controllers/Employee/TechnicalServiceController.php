<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ClinicalDocument;
use App\Models\MedicalVisit;
use App\Models\Employee;
use App\Models\EmployeeDetail;
use App\Models\Doctor;
use App\Models\MedicalService;
use App\Models\Payable;
use App\Models\User;
use App\Models\ServiceGroup;
use App\Models\ServiceOrderItem;
use App\Models\ServiceOrderProgress;
use App\Models\ServiceResult;
use App\Models\ServiceStatus;
use App\Support\EmployeeAccess;
use App\Support\DoctorAccess;
use App\Support\SessionSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TechnicalServiceController extends Controller
{
    private const UNGROUPED = 'chua-phan-nhom';

    private function executor(Request $request): Employee|Doctor
    {
        if ($this->isDoctorWorkspace($request)) {
            $doctor = app(DoctorAccess::class)->doctor($request->user());
            abort_unless($doctor, 403);

            return $doctor;
        }

        $employee = app(EmployeeAccess::class)->employee($request->user());
        abort_unless($employee, 403);

        return $employee;
    }

    private function isDoctorWorkspace(Request $request): bool
    {
        return $request->routeIs('doctor.*');
    }

    private function routePrefix(Request $request): string
    {
        $prefix = $this->isDoctorWorkspace($request) ? 'doctor' : 'employee';

        return match (true) {
            $request->routeIs($prefix.'.laboratory.*') => $prefix.'.laboratory',
            $request->routeIs($prefix.'.imaging.*') => $prefix.'.imaging',
            default => $prefix.'.services',
        };
    }

    private function shortcutGroups(Request $request): array
    {
        return match ($this->routePrefix($request)) {
            'employee.laboratory', 'doctor.laboratory' => ['XN'],
            'employee.imaging', 'doctor.imaging' => ['CDHA', 'SA'],
            default => [],
        };
    }

    private function scopedItems(Employee|Doctor $executor, array $groupCodes): Builder
    {
        $query = ServiceOrderItem::query()
            ->when($groupCodes, fn ($query) => $query->whereHas('medicalService.serviceGroups', fn ($groups) => $groups->whereIn('service_group_code', $groupCodes)));

        return $this->constrainToExecutor($query, $executor);
    }

    private function constrainToExecutor(Builder $query, Employee|Doctor $executor): Builder
    {
        if ($executor instanceof Employee) {
            return $query->visibleToTechnician($executor);
        }

        if ($executor instanceof Doctor) {
            return $query->where(fn (Builder $items) => $items->where('performed_by_doctor_id', $executor->id)
                ->orWhere(fn (Builder $legacy) => $legacy->whereNull('performed_by_doctor_id')
                    ->where(fn (Builder $assigned) => $assigned->whereNull('performed_by_employee_id')
                        ->orWhereHas('serviceStatus', fn (Builder $status) => $status->whereIn('name', ['PENDING', 'PROCESSING'])))));
        }

        return $query->whereRaw('1 = 0');
    }

    private function listQuery(Request $request): array
    {
        return [
            ...array_filter(
                Arr::only($request->query(), ['group', 'status', 'date', 'page']),
                fn ($value) => is_string($value) && $value !== ''
            ),
            ...(new SessionSearch($request, $this->isDoctorWorkspace($request) ? 'doctor.services' : 'employee.services'))->navigation(),
        ];
    }

    private function filterStage(Builder $items, string $stage): void
    {
        $status = in_array($stage, ['READY', 'WAITING_PAYMENT'], true) ? 'PENDING' : $stage;
        $hasStatus = fn (Builder $query) => $query->whereHas('serviceStatus', fn ($state) => $state->where('name', $status));
        $cancelledOrder = fn ($query) => $query->where('status', 'CANCELLED');
        if ($stage === 'CANCELLED') {
            $items->where(fn (Builder $query) => $hasStatus($query)->orWhereHas('serviceOrder', $cancelledOrder));

            return;
        }
        $hasStatus($items)->whereDoesntHave('serviceOrder', $cancelledOrder);
        $paid = fn ($query) => $query->where('allows_service_execution', true);
        if ($stage === 'READY') {
            $items->whereHas('payable.paymentStatus', $paid);
        } elseif ($stage === 'WAITING_PAYMENT') {
            $items->whereDoesntHave('payable.paymentStatus', $paid);
        }
    }

    public function index(Request $request)
    {
        $employee = $this->executor($request);
        $shortcutGroups = $this->shortcutGroups($request);
        $withCurrentOrders = fn ($services) => $services->withCount([
            'serviceOrderItems as current_order_count' => fn (Builder $items) => $this->constrainToExecutor($items, $employee)->unresolved()
                ->whereDoesntHave('serviceOrder', fn ($order) => $order->where('status', 'CANCELLED')),
        ])->orderBy('medical_services.display_order')->orderBy('medical_services.name');

        $groups = ServiceGroup::query()
            ->when($shortcutGroups, fn ($groups) => $groups->whereIn('service_group_code', $shortcutGroups))
            ->with(['medicalServices' => $withCurrentOrders])
            ->orderBy('display_order')->orderBy('name')->get()
            ->map(fn (ServiceGroup $group) => [
                'code' => $group->service_group_code, 'name' => $group->name, 'services' => $group->medicalServices,
            ]);

        if (! $shortcutGroups) {
            $ungrouped = $withCurrentOrders(MedicalService::whereDoesntHave('serviceGroups'))->get();
            if ($ungrouped->isNotEmpty()) {
                $groups->push(['code' => self::UNGROUPED, 'name' => 'Chưa phân nhóm', 'services' => $ungrouped]);
            }
        }

        $openGroup = $request->query('group');
        if ($openGroup === null && count($shortcutGroups) === 1) {
            $openGroup = $shortcutGroups[0];
        }

        $routePrefix = $this->routePrefix($request);

        return view('employee.technical.index', compact('employee', 'groups', 'openGroup', 'shortcutGroups', 'routePrefix'));
    }

    private function orderFilterRules(): array
    {
        return [
            'date' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['nullable', 'in:PENDING,READY,WAITING_PAYMENT,PROCESSING,COMPLETED,CANCELLED'],
        ];
    }

    public function filterOrders(Request $request, string $group, string $service)
    {
        $this->executor($request);
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100'], ...$this->orderFilterRules()]);
        $routePrefix = $this->routePrefix($request);
        $search = new SessionSearch($request, $routePrefix);

        return redirect()->route($routePrefix.'.orders', [
            'group' => $group,
            'service' => $service,
            ...Arr::except($filters, 'q'),
            ...$search->remember($filters['q'] ?? null),
        ]);
    }

    public function orders(Request $request, string $group, string $service)
    {
        $employee = $this->executor($request);
        $routePrefix = $this->routePrefix($request);
        $groupRecord = $group === self::UNGROUPED ? null : ServiceGroup::where('service_group_code', $group)->firstOrFail();
        $group = $groupRecord?->service_group_code ?? self::UNGROUPED;
        $groupName = $groupRecord?->name ?? 'Chưa phân nhóm';
        $service = ($groupRecord?->medicalServices() ?? MedicalService::whereDoesntHave('serviceGroups'))
            ->where('medical_services.service_code', $service)->firstOrFail();
        $search = new SessionSearch($request, $routePrefix);
        $urlFilters = [...$request->validate($this->orderFilterRules()), ...$search->navigation()];
        $routeParameters = ['group' => $group, 'service' => $service->service_code];
        if ($request->query->has('q') || ($request->query->has(SessionSearch::PARAMETER) && $search->keyword() === null)) {
            return redirect()->route($routePrefix.'.orders', [...$routeParameters, ...$urlFilters])
                ->header('Referrer-Policy', 'no-referrer')
                ->header('Cache-Control', 'private, no-store');
        }
        $filters = $urlFilters + ['q' => $search->keyword()];
        // Tìm chỉ định theo mã phiếu hoặc thông tin bệnh nhân trong phạm vi nhân viên được xem.
        $items = $this->constrainToExecutor($service->serviceOrderItems()->getQuery(), $employee)
            ->with(['serviceStatus', 'serviceOrder.medicalVisit.patient', 'payable.paymentStatus'])
            ->when($filters['q'] ?? null, function (Builder $items, string $search) {
                $term = trim($search);
                $keyword = '%'.$term.'%';
                $items->where(fn (Builder $matches) => $matches
                    ->whereHas('serviceOrder', fn ($query) => $query->whereLike('service_order_code', $keyword))
                    ->orWhereHas('serviceOrder.medicalVisit.patient', fn ($patient) => $patient
                        ->whereLike('full_name', $keyword)
                        ->orWhereLike('patient_code', $keyword)
                        ->orWhereHas('user', fn ($user) => $user->where('phone', $term))));
            })
            ->when($filters['date'] ?? null, fn ($q, $date) => $q->whereHas('serviceOrder', fn ($order) => $order->whereDate('ordered_at', $date)))
            ->when($filters['status'] ?? null, fn (Builder $query, string $stage) => $this->filterStage($query, $stage))
            ->orderByDesc('id')->paginate(15)->appends($urlFilters);
        $listQuery = ['group' => $group, ...Arr::except($this->listQuery($request), ['group'])];
        $ordersUrl = route($routePrefix.'.orders', $routeParameters);
        $filterUrl = route($routePrefix.'.orders.filter', $routeParameters);
        $catalogueUrl = route($routePrefix.'.index', ['group' => $group]).'#nhom-'.rawurlencode($group);

        return response()->view('employee.technical.orders', compact('employee', 'groupName', 'service', 'items', 'filters', 'listQuery', 'ordersUrl', 'filterUrl', 'catalogueUrl', 'routePrefix'))
            ->header('Cache-Control', 'private, no-store');
    }

    public function show(Request $request, int $item)
    {
        $employee = $this->executor($request);
        $title = $this->isDoctorWorkspace($request) ? 'Bác sĩ thực hiện dịch vụ' : 'Thực hiện dịch vụ';
        $routePrefix = $this->routePrefix($request);
        $record = $this->scopedItems($employee, $this->shortcutGroups($request))
            ->with(['medicalService.serviceGroups', 'serviceStatus', 'serviceOrder.medicalVisit.patient', 'serviceOrder.medicalVisit.status', 'performer', 'performerDoctor', 'payable.paymentStatus', 'result'])
            ->findOrFail($item);
        $serviceGroups = $record->medicalService?->serviceGroups;
        // Không tự đoán biểu mẫu khi dịch vụ có nhiều nhóm hoặc chưa có nhóm.
        $groupCode = $serviceGroups?->count() === 1 ? $serviceGroups->first()->service_group_code : null;
        $hideUploads = $serviceGroups?->contains(fn ($group) => in_array($group->service_group_code, ['XN', 'CDHA', 'TDCN', 'TT'], true)) ?? false;
        $supportsDetails = in_array($groupCode, ['XN', 'CDHA', 'TDCN', 'TT'], true);
        $supportsParameters = in_array($groupCode, ['XN', 'TDCN'], true);
        $listQuery = $this->listQuery($request);
        $shortcutGroups = $this->shortcutGroups($request);
        $backGroup = $serviceGroups?->firstWhere('service_group_code', $listQuery['group'] ?? null)
            ?? $serviceGroups?->first(fn (ServiceGroup $group) => in_array($group->service_group_code, $shortcutGroups, true))
            ?? $serviceGroups?->sortBy('display_order')->first();
        $backUrl = $record->medicalService
            ? route($routePrefix.'.orders', [
                'group' => $backGroup?->service_group_code ?? self::UNGROUPED,
                'service' => $record->medicalService->service_code,
                ...Arr::except($listQuery, ['group']),
            ])
            : route($routePrefix.'.index');

        $isDoctorWorkspace = $this->isDoctorWorkspace($request);

        return view('employee.technical.show', compact('employee', 'title', 'routePrefix', 'record', 'supportsParameters', 'supportsDetails', 'hideUploads', 'groupCode', 'listQuery', 'backUrl', 'isDoctorWorkspace'));
    }

    // Chỉ tạo bản xem từ dữ liệu đã lưu trong phạm vi nhân viên được phép xem.
    public function printResult(Request $request, int $item)
    {
        $employee = $this->executor($request);
        $routePrefix = $this->routePrefix($request);
        $record = $this->scopedItems($employee, $this->shortcutGroups($request))
            ->with(['medicalService.serviceGroups', 'serviceOrder.medicalVisit.patient', 'serviceOrder.medicalVisit.examinationRoom', 'performer', 'performerDoctor', 'result'])
            ->findOrFail($item);
        $groups = $record->medicalService?->serviceGroups;
        abort_unless($groups?->count() === 1 && $record->result?->hasContent(), 404);
        $title = match ($groups->first()->service_group_code) {
            'XN' => 'PHIẾU KẾT QUẢ XÉT NGHIỆM',
            'CDHA' => 'PHIẾU KẾT QUẢ CHẨN ĐOÁN HÌNH ẢNH',
            'SA' => 'PHIẾU KẾT QUẢ SIÊU ÂM',
            'TDCN' => 'PHIẾU KẾT QUẢ THĂM DÒ CHỨC NĂNG',
            'TT' => 'PHIẾU KẾT QUẢ THỦ THUẬT',
            default => 'PHIẾU KẾT QUẢ DỊCH VỤ',
        };
        $backUrl = route($routePrefix.'.show', ['item' => $item, ...$this->listQuery($request)]);
        $attachmentUrls = [
            'pdf' => route($routePrefix.'.document', ['item' => $item, 'type' => 'pdf']),
            'image' => route($routePrefix.'.document', ['item' => $item, 'type' => 'image']),
        ];

        // Không lưu bản sao phiếu vào bộ nhớ đệm chung vì chứa dữ liệu y tế.
        return response()->view('print.service-result', compact('record', 'title', 'backUrl', 'attachmentUrls'))
            ->header('Cache-Control', 'private, no-store, no-cache, must-revalidate')
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    // Giữ khóa tài khoản và lượt khám trước khi nhận hoặc ghi kết quả dịch vụ.
    private function lockedItem(Request $request, int $id): array
    {
        $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
        if ($this->isDoctorWorkspace($request)) {
            Doctor::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
            $employee = app(DoctorAccess::class)->doctor($user);
        } else {
            $lockedEmployee = Employee::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
            EmployeeDetail::where('employee_id', $lockedEmployee->id)->lockForUpdate()->first();
            $employee = app(EmployeeAccess::class)->employee($user);
        }
        abort_unless($employee, 403);
        $items = $this->scopedItems($employee, $this->shortcutGroups($request))->whereKey($id);
        $visitId = (clone $items)->join('service_orders', 'service_orders.id', '=', 'service_order_items.service_order_id')->value('service_orders.medical_visit_id');
        abort_unless($visitId, 404);
        $visit = MedicalVisit::with('status')->whereKey($visitId)->lockForUpdate()->firstOrFail();
        $visit->assertServiceDataWritable();
            $record = $items->with(['serviceStatus', 'serviceOrder', 'payable.paymentStatus', 'result'])->lockForUpdate()->firstOrFail();

        return [$record, $employee];
    }

    private function claimLegacyAssignment(ServiceOrderItem $record, Employee|Doctor $executor): void
    {
        if ($executor instanceof Doctor && ! $record->performed_by_doctor_id && $record->performed_by_employee_id) {
            $record->update(['performed_by_doctor_id' => $executor->id]);
            $record->refresh();
        }
    }

    private function ownsAssignment(ServiceOrderItem $record, Employee|Doctor $executor): bool
    {
        return $executor instanceof Doctor
            ? (int) $record->performed_by_doctor_id === (int) $executor->id
            : (int) $record->performed_by_employee_id === (int) $executor->id;
    }

    public function start(Request $request, int $item)
    {
        $routePrefix = $this->routePrefix($request);
        DB::transaction(function () use ($request, $item) {
            [$record, $employee] = $this->lockedItem($request, $item);
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
            $assignment = $employee instanceof Doctor
                ? ['performed_by_doctor_id' => $employee->id]
                : ['performed_by_employee_id' => $employee->id];
            $record->update([
                'service_status_id' => $status->id,
                'started_at' => now(),
                ...$assignment,
            ]);
            $this->audit($request, $record, 'Bắt đầu thực hiện dịch vụ');
        }, 3);
        return redirect()->route($routePrefix.'.show', ['item' => $item, ...$this->listQuery($request)])->with('success', 'Đã bắt đầu thực hiện dịch vụ.');
    }

    public function save(Request $request, int $item)
    {
        $routePrefix = $this->routePrefix($request);
        $data = $request->validate([
            'confirm_result' => ['accepted'],
            'result_version' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/'],
            'technique' => ['nullable', 'string', 'max:2000'],
            'result_conclusion' => ['nullable', 'string', 'max:10000'],
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
            'details_present' => ['sometimes', 'in:1'],
            'details' => ['sometimes', 'array', 'max:30'],
            'details.*' => ['array:name,value'],
            'details.*.name' => ['nullable', 'string', 'max:120'],
            'details.*.value' => ['nullable', function (string $attribute, mixed $value, \Closure $fail): void {
                if ((! is_string($value) && ! is_int($value) && ! is_float($value)) || mb_strlen((string) $value) > 2000) {
                    $fail('Nội dung phải là chữ hoặc số và không quá 2000 ký tự.');
                }
            }],
            'result_pdf' => ClinicalDocument::pdfRules(),
            'result_image' => ClinicalDocument::imageRules(),
        ], [
            'confirm_result.accepted' => 'Vui lòng xác nhận đã kiểm tra thông tin trước khi lưu kết quả.',
        ], [
            'technique' => 'cách thức và vị trí thực hiện',
            'result_conclusion' => 'kết luận dịch vụ',
            'result_summary' => 'mô tả kết quả',
        ]);
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
        // Chuẩn hóa từng mục bổ sung, không tự sinh kết luận hoặc giá trị.
        $details = [];
        foreach ($data['details'] ?? [] as $index => $row) {
            $name = trim($row['name'] ?? '');
            $value = trim((string) ($row['value'] ?? ''));
            if ($name === '' && $value === '') continue;
            if ($name === '' || $value === '') {
                throw ValidationException::withMessages(['details.'.$index => 'Mỗi thông tin cần có tên và nội dung.']);
            }
            $details[] = ['name' => $name, 'value' => $value];
        }
        // Lưu kết quả và tệp cùng giao dịch; bước xác minh vẫn tách riêng.
        ClinicalDocument::transaction(function (ClinicalDocument $documents) use ($request, $item, $data, $parameters, $details) {
            [$record, $employee] = $this->lockedItem($request, $item);
            $this->claimLegacyAssignment($record, $employee);
            if ($record->serviceStatus?->name !== 'PROCESSING' || ! $this->ownsAssignment($record, $employee) || $record->serviceOrder?->status === 'CANCELLED') {
                throw ValidationException::withMessages(['service' => 'Chỉ người đang thực hiện mới được nhập kết quả khi dịch vụ đang xử lý.']);
            }
            // Kiểm tra nhóm từ dữ liệu máy chủ cho mọi yêu cầu, kể cả gửi trực tiếp.
            $groups = $record->medicalService?->serviceGroups()->pluck('service_group_code') ?? collect();
            $group = $groups->count() === 1 ? $groups->first() : null;
            $supportsDetails = in_array($group, ['XN', 'CDHA', 'TDCN', 'TT'], true);
            if (($request->has('parameters') || $request->has('parameters_present'))
                && ! in_array($group, ['XN', 'TDCN'], true)) {
                throw ValidationException::withMessages(['parameters' => 'Dịch vụ này không hỗ trợ bảng chỉ số.']);
            }
            if (($request->has('details') || $request->has('details_present')) && ! $supportsDetails) {
                throw ValidationException::withMessages(['details' => 'Dịch vụ chưa thuộc một nhóm hỗ trợ thông tin bổ sung.']);
            }
            if ($groups->intersect(['XN', 'CDHA', 'TDCN', 'TT'])->isNotEmpty() && ($request->hasFile('result_pdf') || $request->hasFile('result_image'))) {
                throw ValidationException::withMessages(['service' => 'Dịch vụ này nhập kết quả bằng văn bản, chỉ số hoặc thông tin bổ sung.']);
            }
            $result = ServiceResult::where('service_order_item_id', $record->id)->lockForUpdate()->first();
            if ($result?->hasContent() || $result?->verified_by_user_id || $result?->patient_publication_status === 'PUBLISHED') {
                throw ValidationException::withMessages(['service' => 'Kết quả đã được lưu và đã khóa chỉnh sửa.']);
            }
            // Sau khi khóa chỉ định, từ chối nội dung gửi từ biểu mẫu đã bị thay đổi.
            if (! hash_equals(ServiceResult::editVersion($result), $data['result_version'])) {
                throw ValidationException::withMessages(['service' => 'Kết quả đã thay đổi. Vui lòng tải lại trang trước khi lưu.']);
            }
            // Không ghi đè dữ liệu chỉ số cũ có cấu trúc chưa được nhận diện.
            if ($request->has('parameters_present') && $result && ServiceResult::editableParameters($result->result_parameters) === null) {
                throw ValidationException::withMessages(['parameters' => 'Chỉ số cũ có định dạng khác và được giữ nguyên.']);
            }
            $updatesDetails = $request->has('details_present');
            $updatesTechnique = array_key_exists('technique', $data);
            $existingDetails = ServiceResult::editableTechnicalDetails($result?->technical_details);
            if (($updatesDetails || $updatesTechnique) && $existingDetails === null) {
                throw ValidationException::withMessages(['details' => 'Thông tin cũ có định dạng khác và được giữ nguyên.']);
            }
            $paths = $documents->store($request, 'service-results/'.$record->id, ClinicalDocument::RESULT_FILES);
            $values = ['resulted_at' => now(), ...$paths];
            if ($employee instanceof Doctor) {
                $values['verified_by_user_id'] = $employee->user_id;
            }
            foreach (['result_summary', 'result_conclusion'] as $field) {
                if (array_key_exists($field, $data)) {
                    $values[$field] = $data[$field];
                }
            }
            if ($request->has('parameters_present')) $values['result_parameters'] = $parameters;
            if ($updatesDetails || $updatesTechnique) {
                $rows = $updatesDetails ? $details : $existingDetails;
                if ($updatesTechnique) {
                    // Trường kỹ thuật nằm ngoài các dòng bổ sung của biểu mẫu.
                    if (! $updatesDetails) {
                        $index = array_search(ServiceResult::TECHNIQUE_DETAIL_NAME, array_column($rows, 'name'), true);
                        if ($index !== false) {
                            unset($rows[$index]);
                        }
                    }
                    if (filled($data['technique'])) {
                        array_unshift($rows, ['name' => ServiceResult::TECHNIQUE_DETAIL_NAME, 'value' => $data['technique']]);
                    }
                }
                $values['technical_details'] = array_values($rows);
            }
            $content = array_merge($result?->getAttributes() ?? [], $values);
            if (! ServiceResult::hasContentValues($content, $record->id)) {
                throw ValidationException::withMessages(['result_summary' => 'Vui lòng nhập kỹ thuật, kết luận, mô tả hoặc chỉ số kết quả hợp lệ.']);
            }
            if ($result) {
                $result->update($values);
            } else {
                ServiceResult::create(array_merge(['service_order_item_id' => $record->id, 'verified_by_user_id' => null], $values));
            }
            $this->audit($request, $record, 'Lưu kết quả kỹ thuật');
        });
        return redirect()->route($routePrefix.'.show', ['item' => $item, ...$this->listQuery($request)])->with('success', 'Đã lưu kết quả kỹ thuật.');
    }

    public function complete(Request $request, int $item)
    {
        $routePrefix = $this->routePrefix($request);
        DB::transaction(function () use ($request, $item) {
            [$record, $employee] = $this->lockedItem($request, $item);
            $this->claimLegacyAssignment($record, $employee);
            if ($record->serviceStatus?->name !== 'PROCESSING' || ! $record->serviceStatus?->allows_transition || ! $this->ownsAssignment($record, $employee) || $record->serviceOrder?->status === 'CANCELLED') {
                throw ValidationException::withMessages(['service' => 'Dịch vụ không còn ở trạng thái cho phép hoàn tất.']);
            }
            if (! $record->result?->hasContent()) throw ValidationException::withMessages(['service' => 'Cần lưu kết quả trước khi hoàn tất.']);
            $status = ServiceStatus::where('name', 'COMPLETED')->where('is_active', true)->first();
            if (! $status) throw ValidationException::withMessages(['service' => 'Chưa cấu hình trạng thái hoàn thành.']);
            $record->update(['service_status_id' => $status->id, 'completed_at' => now()]);
            $record->setRelation('serviceStatus', $status);
            $progress = app(ServiceOrderProgress::class);
            $progress->publishCompletedResult($record, $request);
            $this->audit($request, $record, 'Hoàn tất dịch vụ');
            // Dùng quy trình chung để cập nhật phiếu và hàng đợi quay lại bác sĩ.
            $progress->synchronize($record->service_order_id);
        }, 3);
        return redirect()->route($routePrefix.'.show', ['item' => $item, ...$this->listQuery($request)])->with('success', 'Đã hoàn tất dịch vụ.');
    }

    public function document(Request $request, int $item, string $type): StreamedResponse
    {
        $employee = $this->executor($request);
        abort_unless(in_array($type, ['pdf', 'image'], true), 404);
        $record = $this->scopedItems($employee, $this->shortcutGroups($request))->with('result')->findOrFail($item);
        abort_unless($record->result, 404);
        $path = $type === 'pdf' ? $record->result->result_pdf_path : $record->result->result_image_path;
        return app(ClinicalDocument::class)->response($path, 'service-results/'.$record->id, $type);
    }

    private function audit(Request $request, ServiceOrderItem $item, string $description): void
    {
        AuditLog::create(['user_id' => $request->user()->id, 'role_name_snapshot' => $request->user()->role?->name, 'action' => 'UPDATE', 'entity_type' => 'service_order_items', 'entity_id' => $item->id, 'description' => $description, 'ip_address' => $request->ip(), 'user_agent' => mb_substr((string) $request->userAgent(), 0, 500), 'performed_at' => now()]);
    }
}
