<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignGroupServicesRequest;
use App\Http\Requests\Admin\StoreServiceGroupRequest;
use App\Http\Requests\Admin\UpdateServiceGroupRequest;
use App\Models\MedicalService;
use App\Models\ServiceGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ServiceGroupController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['keyword' => ['nullable', 'string', 'max:150'], 'status' => ['nullable', 'in:ACTIVE,INACTIVE']]);
        $keyword = trim($filters['keyword'] ?? '');
        $status = $filters['status'] ?? null;
        $serviceGroups = ServiceGroup::query()->withCount('medicalServices')
            ->when($keyword !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('service_group_code', 'like', "%{$keyword}%")->orWhere('name', 'like', "%{$keyword}%")))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderBy('display_order')->orderBy('name')->paginate(10)->withQueryString();
        $totalServiceGroups = ServiceGroup::count();
        $activeServiceGroups = ServiceGroup::where('status', 'ACTIVE')->count();
        $inactiveServiceGroups = ServiceGroup::where('status', 'INACTIVE')->count();

        return view('admin.service-groups.index', compact('serviceGroups', 'keyword', 'status', 'totalServiceGroups', 'activeServiceGroups', 'inactiveServiceGroups'));
    }

    public function create(): View
    {
        return view('admin.service-groups.create');
    }

    public function store(StoreServiceGroupRequest $request): RedirectResponse
    {
        return to_route('admin.service-groups.show', ServiceGroup::create($request->validated()))->with('success', 'Thêm nhóm dịch vụ thành công.');
    }

    public function show(Request $request, ServiceGroup $serviceGroup): View
    {
        $filters = $request->validate(['keyword' => ['nullable', 'string', 'max:150'], 'status' => ['nullable', 'in:ACTIVE,INACTIVE']]);
        $totalServices = $serviceGroup->medicalServices()->count();
        $activeServices = $serviceGroup->medicalServices()->where('status', 'ACTIVE')->count();
        $inactiveServices = $serviceGroup->medicalServices()->where('status', 'INACTIVE')->count();
        $medicalServices = $serviceGroup->medicalServices()
            ->when($filters['keyword'] ?? null, fn ($q, $keyword) => $q->where(fn ($q) => $q->where('name', 'like', "%{$keyword}%")->orWhere('service_code', 'like', "%{$keyword}%")))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->orderBy('display_order')->orderBy('name')->paginate(10)->withQueryString();
        $availableServices = MedicalService::where('status', 'ACTIVE')
            ->whereDoesntHave('serviceGroups', fn ($q) => $q->whereKey($serviceGroup->id))->orderBy('name')->get();

        return view('admin.service-groups.show', compact('serviceGroup', 'totalServices', 'activeServices', 'inactiveServices', 'medicalServices', 'availableServices'));
    }

    public function assign(AssignGroupServicesRequest $request, ServiceGroup $serviceGroup): RedirectResponse
    {
        DB::transaction(function () use ($request, $serviceGroup) {
            $group = ServiceGroup::whereKey($serviceGroup->id)->lockForUpdate()->firstOrFail();
            abort_unless($group->status === 'ACTIVE', 422, 'Nhóm dịch vụ đã ngừng hoạt động.');
            $group->medicalServices()->syncWithoutDetaching($request->validated('medical_service_ids'));
        });

        return back()->with('success', 'Đã gán dịch vụ vào nhóm. Các nhóm khác của dịch vụ được giữ nguyên.');
    }

    public function edit(ServiceGroup $serviceGroup): View
    {
        return view('admin.service-groups.edit', compact('serviceGroup'));
    }

    public function update(UpdateServiceGroupRequest $request, ServiceGroup $serviceGroup): RedirectResponse
    {
        $serviceGroup->update($request->validated());

        return to_route('admin.service-groups.show', $serviceGroup)->with('success', 'Cập nhật nhóm dịch vụ thành công.');
    }

    public function destroy(ServiceGroup $serviceGroup): RedirectResponse
    {
        $serviceGroup->update(['status' => 'INACTIVE']);

        return to_route('admin.service-groups.index')->with('success', 'Đã ngừng hoạt động nhóm dịch vụ.');
    }

    public function activate(ServiceGroup $serviceGroup): RedirectResponse
    {
        $serviceGroup->update(['status' => 'ACTIVE']);

        return back()->with('success', 'Đã kích hoạt lại nhóm dịch vụ.');
    }
}
