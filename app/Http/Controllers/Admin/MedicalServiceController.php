<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMedicalServiceRequest;
use App\Http\Requests\Admin\UpdateMedicalServiceRequest;
use App\Models\MedicalService;
use App\Models\ServiceGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MedicalServiceController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'keyword' => ['nullable', 'string', 'max:150'],
            'group_id' => ['nullable', 'integer', 'exists:service_groups,id'],
            'status' => ['nullable', 'in:ACTIVE,INACTIVE'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'gte:min_price'],
        ]);
        $keyword = trim($filters['keyword'] ?? '');
        $serviceGroupId = $filters['group_id'] ?? null;
        $status = $filters['status'] ?? null;
        $minPrice = $filters['min_price'] ?? null;
        $maxPrice = $filters['max_price'] ?? null;
        $medicalServices = MedicalService::query()->with('serviceGroups')
            ->when($keyword !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('service_code', 'like', "%{$keyword}%")->orWhere('name', 'like', "%{$keyword}%")))
            ->when($serviceGroupId, fn ($q) => $q->whereHas('serviceGroups', fn ($q) => $q->whereKey($serviceGroupId)))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($minPrice !== null, fn ($q) => $q->where('price', '>=', $minPrice))
            ->when($maxPrice !== null, fn ($q) => $q->where('price', '<=', $maxPrice))
            ->orderBy('display_order')->orderBy('name')->paginate(10)->withQueryString();
        $serviceGroups = ServiceGroup::query()->orderBy('display_order')->orderBy('name')->get();
        $totalServices = MedicalService::count();
        $activeServices = MedicalService::where('status', 'ACTIVE')->count();
        $inactiveServices = MedicalService::where('status', 'INACTIVE')->count();

        return view('admin.medical-services.index', compact('medicalServices', 'serviceGroups', 'keyword', 'serviceGroupId', 'status', 'minPrice', 'maxPrice', 'totalServices', 'activeServices', 'inactiveServices'));
    }

    public function create(Request $request): View
    {
        $request->validate(['group_id' => ['nullable', 'integer', 'exists:service_groups,id']]);
        $serviceGroups = ServiceGroup::where('status', 'ACTIVE')->orderBy('display_order')->orderBy('name')->get();
        $selectedGroupIds = $serviceGroups->where('id', $request->integer('group_id'))->values()->modelKeys();

        return view('admin.medical-services.create', compact('serviceGroups', 'selectedGroupIds'));
    }

    public function store(StoreMedicalServiceRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $groupIds = $data['service_group_ids'];
        unset($data['service_group_ids']);
        $medicalService = DB::transaction(function () use ($data, $groupIds) {
            $this->ensureNameAvailable($data['name']);
            $service = MedicalService::create($data);
            $service->serviceGroups()->sync($groupIds);

            return $service;
        });

        return to_route('admin.medical-services.show', $medicalService)->with('success', 'Thêm dịch vụ y tế thành công.');
    }

    public function show(MedicalService $medicalService): View
    {
        $medicalService->load('serviceGroups');
        $serviceOrderItemCount = $medicalService->serviceOrderItems()->count();
        $orderedValue = $medicalService->serviceOrderItems()->sum('price_at_order');
        $recentOrderItems = DB::table('service_order_items')
            ->join('service_orders', 'service_order_items.service_order_id', '=', 'service_orders.id')
            ->join('medical_visits', 'service_orders.medical_visit_id', '=', 'medical_visits.id')
            ->join('patients', 'medical_visits.patient_id', '=', 'patients.id')
            ->join('service_statuses', 'service_order_items.service_status_id', '=', 'service_statuses.id')
            ->where('service_order_items.medical_service_id', $medicalService->id)
            ->select('service_order_items.id', 'service_order_items.price_at_order', 'service_order_items.started_at', 'service_order_items.completed_at', 'service_orders.service_order_code', 'service_orders.ordered_at', 'medical_visits.visit_code', 'patients.patient_code', 'patients.full_name as patient_name', 'service_statuses.name as service_status')
            ->orderByDesc('service_orders.ordered_at')->limit(10)->get();

        return view('admin.medical-services.show', compact('medicalService', 'serviceOrderItemCount', 'orderedValue', 'recentOrderItems'));
    }

    public function edit(MedicalService $medicalService): View
    {
        $medicalService->load('serviceGroups');
        $serviceGroups = ServiceGroup::where('status', 'ACTIVE')
            ->orWhereHas('medicalServices', fn ($q) => $q->whereKey($medicalService->id))
            ->orderBy('display_order')->orderBy('name')->get();

        return view('admin.medical-services.edit', compact('medicalService', 'serviceGroups'));
    }

    public function update(UpdateMedicalServiceRequest $request, MedicalService $medicalService): RedirectResponse
    {
        $data = $request->validated();
        $groupIds = $data['service_group_ids'];
        unset($data['service_group_ids']);
        DB::transaction(function () use ($medicalService, $data, $groupIds) {
            $this->ensureNameAvailable($data['name'], $medicalService);
            $service = MedicalService::whereKey($medicalService->id)->lockForUpdate()->firstOrFail();
            $service->update($data);
            $service->serviceGroups()->sync($groupIds);
        });

        return to_route('admin.medical-services.show', $medicalService)->with('success', 'Cập nhật dịch vụ y tế thành công.');
    }

    private function ensureNameAvailable(string $name, ?MedicalService $current = null): void
    {
        /** Keep concurrent application writes on the validation path; the DB unique also protects other writers. */
        ServiceGroup::query()->orderBy('id')->lockForUpdate()->firstOrFail();
        if (MedicalService::where('name', $name)->when($current, fn ($query) => $query->where('id', '!=', $current->id))->exists()) {
            throw ValidationException::withMessages(['name' => 'Tên dịch vụ đã tồn tại. Hãy gán dịch vụ hiện có vào nhóm.']);
        }
    }

    public function destroy(MedicalService $medicalService): RedirectResponse
    {
        $medicalService->update(['status' => 'INACTIVE']);

        return to_route('admin.medical-services.index')->with('success', 'Đã ngừng hoạt động dịch vụ y tế.');
    }

    public function activate(MedicalService $medicalService): RedirectResponse
    {
        $medicalService->update(['status' => 'ACTIVE']);

        return back()->with('success', 'Đã kích hoạt lại dịch vụ y tế.');
    }
}
