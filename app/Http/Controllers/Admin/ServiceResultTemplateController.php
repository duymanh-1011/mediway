<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MedicalService;
use App\Models\ServiceResultTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ServiceResultTemplateController extends Controller
{
    public function index(): View
    {
        return view('admin.service-result-templates.index', [
            'templates' => ServiceResultTemplate::withCount('medicalServices')->orderBy('name')->paginate(15),
        ]);
    }

    public function create(): View
    {
        $template = new ServiceResultTemplate(['effective_from' => today(), 'is_active' => true]);
        $sourceId = request()->integer('copy');
        if ($sourceId) {
            $source = ServiceResultTemplate::findOrFail($sourceId);
            $template->name = $this->copyName($source->name);
            $template->description = $source->description;
            $template->fields = $source->fields;
        }

        return view('admin.service-result-templates.form', [
            'template' => $template,
            'services' => $this->serviceOptions(),
            'selectedServiceIds' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $serviceIds = $data['medical_service_ids'];
        unset($data['medical_service_ids']);
        $template = DB::transaction(function () use ($data, $serviceIds): ServiceResultTemplate {
            $this->lockServices($serviceIds);
            $this->assertNoOverlappingAssignments($serviceIds, $data['effective_from'], $data['effective_until']);
            $template = ServiceResultTemplate::create($data);
            $template->medicalServices()->sync($serviceIds);

            return $template;
        }, 3);

        return to_route('admin.service-result-templates.edit', $template)->with('success', 'Đã tạo mẫu phiếu kết quả.');
    }

    public function edit(ServiceResultTemplate $serviceResultTemplate): View
    {
        $serviceResultTemplate->load('medicalServices:id,service_code,name,status');

        return view('admin.service-result-templates.form', [
            'template' => $serviceResultTemplate,
            'services' => $this->serviceOptions(),
            'selectedServiceIds' => $serviceResultTemplate->medicalServices->modelKeys(),
        ]);
    }

    public function update(Request $request, ServiceResultTemplate $serviceResultTemplate): RedirectResponse
    {
        $data = $this->validated($request, $serviceResultTemplate);
        $serviceIds = $data['medical_service_ids'];
        unset($data['medical_service_ids']);
        DB::transaction(function () use ($serviceResultTemplate, $data, $serviceIds): void {
            $currentServiceIds = $serviceResultTemplate->medicalServices()->pluck('medical_services.id')->map(fn ($id): int => (int) $id)->all();
            $this->lockServices(array_values(array_unique([...$currentServiceIds, ...$serviceIds])));
            $this->assertNoOverlappingAssignments($serviceIds, $data['effective_from'], $data['effective_until'], $serviceResultTemplate->id);
            $template = ServiceResultTemplate::whereKey($serviceResultTemplate->id)->lockForUpdate()->firstOrFail();
            $lockedServiceIds = $template->medicalServices()->pluck('medical_services.id')->map(fn ($id): int => (int) $id)->all();
            sort($currentServiceIds);
            sort($lockedServiceIds);
            if ($currentServiceIds !== $lockedServiceIds) {
                throw ValidationException::withMessages(['medical_service_ids' => 'Danh sách dịch vụ vừa được cập nhật bởi người khác. Vui lòng tải lại trang và thử lại.']);
            }
            $template->update($data);
            $template->medicalServices()->sync($serviceIds);
        }, 3);

        return to_route('admin.service-result-templates.index')->with('success', 'Đã cập nhật mẫu phiếu. Phiếu đã lập giữ nguyên nội dung và mẫu tại thời điểm tạo.');
    }

    private function validated(Request $request, ?ServiceResultTemplate $current = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150', Rule::unique('service_result_templates', 'name')->ignore($current?->id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
            'effective_from' => ['required', 'date_format:Y-m-d'],
            'effective_until' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:effective_from'],
            'medical_service_ids' => ['nullable', 'array', 'max:500'],
            'medical_service_ids.*' => ['required', 'integer', 'distinct', 'exists:medical_services,id'],
            'fields' => ['required', 'array', 'min:1', 'max:30'],
            'fields.*' => ['array:key,label,type,required,unit,reference'],
            'fields.*.key' => ['required', 'alpha_dash:ascii', 'max:40', 'distinct'],
            'fields.*.label' => ['required', 'string', 'max:120'],
            'fields.*.type' => ['required', Rule::in(['text', 'textarea', 'number', 'measurements'])],
            'fields.*.required' => ['required', 'boolean'],
            'fields.*.unit' => ['nullable', 'string', 'max:40'],
            'fields.*.reference' => ['nullable', 'string', 'max:120'],
        ]);
        $data['fields'] = array_values($data['fields']);
        $data['is_active'] = (bool) $data['is_active'];
        $data['effective_until'] ??= null;
        $data['medical_service_ids'] = array_values(array_unique(array_map('intval', $data['medical_service_ids'] ?? [])));

        return $data;
    }

    private function serviceOptions()
    {
        return MedicalService::query()->orderBy('name')->get(['id', 'service_code', 'name', 'status']);
    }

    private function lockServices(array $serviceIds): void
    {
        if ($serviceIds === []) return;

        MedicalService::query()->whereIn('id', $serviceIds)->orderBy('id')->lockForUpdate()->get(['id']);
    }

    private function assertNoOverlappingAssignments(array $serviceIds, string $effectiveFrom, ?string $effectiveUntil, ?int $ignoreTemplateId = null): void
    {
        if ($serviceIds === []) return;

        $conflict = DB::table('medical_service_result_templates as assignments')
            ->join('service_result_templates as templates', 'templates.id', '=', 'assignments.service_result_template_id')
            ->whereIn('assignments.medical_service_id', $serviceIds)
            ->when($ignoreTemplateId, fn ($query) => $query->where('templates.id', '!=', $ignoreTemplateId))
            ->where(function ($query) use ($effectiveFrom): void {
                $query->whereNull('templates.effective_until')
                    ->orWhereDate('templates.effective_until', '>=', $effectiveFrom);
            })
            ->when($effectiveUntil !== null, fn ($query) => $query->where(function ($range) use ($effectiveUntil): void {
                $range->whereNull('templates.effective_from')
                    ->orWhereDate('templates.effective_from', '<=', $effectiveUntil);
            }))
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                'medical_service_ids' => 'Một hoặc nhiều dịch vụ đã được gán cho mẫu khác trong khoảng thời gian này. Hãy điều chỉnh thời gian hiệu lực để các mẫu không chồng lấn.',
            ]);
        }
    }

    private function copyName(string $name): string
    {
        $base = mb_substr($name, 0, 130).' - phiên bản mới';
        $candidate = $base;
        $suffix = 2;
        while (ServiceResultTemplate::where('name', $candidate)->exists()) {
            $candidate = mb_substr($base, 0, 140).' '.$suffix++;
        }

        return $candidate;
    }
}
