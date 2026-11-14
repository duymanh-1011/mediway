<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceResultTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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
        return view('admin.service-result-templates.form', ['template' => new ServiceResultTemplate]);
    }

    public function store(Request $request): RedirectResponse
    {
        $template = ServiceResultTemplate::create($this->validated($request));

        return to_route('admin.service-result-templates.edit', $template)->with('success', 'Đã tạo mẫu phiếu kết quả.');
    }

    public function edit(ServiceResultTemplate $serviceResultTemplate): View
    {
        return view('admin.service-result-templates.form', ['template' => $serviceResultTemplate]);
    }

    public function update(Request $request, ServiceResultTemplate $serviceResultTemplate): RedirectResponse
    {
        $data = $this->validated($request, $serviceResultTemplate);
        DB::transaction(function () use ($serviceResultTemplate, $data): void {
            ServiceResultTemplate::whereKey($serviceResultTemplate->id)->lockForUpdate()->firstOrFail()->update($data);
        });

        return to_route('admin.service-result-templates.index')->with('success', 'Đã cập nhật mẫu phiếu. Phiếu đã lập giữ nguyên nội dung và mẫu tại thời điểm tạo.');
    }

    private function validated(Request $request, ?ServiceResultTemplate $current = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150', Rule::unique('service_result_templates', 'name')->ignore($current?->id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
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

        return $data;
    }
}
