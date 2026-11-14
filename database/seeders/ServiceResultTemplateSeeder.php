<?php

namespace Database\Seeders;

use App\Models\MedicalService;
use App\Models\ServiceResultTemplate;
use Illuminate\Database\Seeder;

class ServiceResultTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            'Phiếu xét nghiệm' => [
                ['key' => 'specimen', 'label' => 'Loại bệnh phẩm', 'type' => 'text', 'required' => false],
                ['key' => 'measurements', 'label' => 'Các chỉ số xét nghiệm', 'type' => 'measurements', 'required' => true],
                ['key' => 'comment', 'label' => 'Nhận xét', 'type' => 'textarea', 'required' => false],
            ],
            'Phiếu chẩn đoán hình ảnh' => [
                ['key' => 'technique', 'label' => 'Phương pháp / kỹ thuật', 'type' => 'text', 'required' => false],
                ['key' => 'findings', 'label' => 'Mô tả kết quả', 'type' => 'textarea', 'required' => true],
                ['key' => 'conclusion', 'label' => 'Kết luận', 'type' => 'textarea', 'required' => true],
                ['key' => 'recommendation', 'label' => 'Đề nghị (nếu có)', 'type' => 'textarea', 'required' => false],
            ],
            'Phiếu nội soi' => [
                ['key' => 'method', 'label' => 'Phương pháp / phạm vi khảo sát', 'type' => 'text', 'required' => false],
                ['key' => 'findings', 'label' => 'Mô tả hình ảnh', 'type' => 'textarea', 'required' => true],
                ['key' => 'conclusion', 'label' => 'Kết luận', 'type' => 'textarea', 'required' => true],
                ['key' => 'biopsy', 'label' => 'Sinh thiết / can thiệp kèm theo', 'type' => 'textarea', 'required' => false],
                ['key' => 'recommendation', 'label' => 'Hướng xử trí / dặn dò', 'type' => 'textarea', 'required' => false],
            ],
            'Phiếu thăm dò chức năng' => [
                ['key' => 'measurements', 'label' => 'Các chỉ số ghi nhận', 'type' => 'measurements', 'required' => true],
                ['key' => 'interpretation', 'label' => 'Nhận định', 'type' => 'textarea', 'required' => true],
            ],
            'Phiếu thực hiện dịch vụ' => [
                ['key' => 'procedure', 'label' => 'Nội dung thực hiện', 'type' => 'textarea', 'required' => true],
                ['key' => 'findings', 'label' => 'Tình trạng / kết quả sau thực hiện', 'type' => 'textarea', 'required' => false],
                ['key' => 'instructions', 'label' => 'Hướng dẫn sau thực hiện', 'type' => 'textarea', 'required' => false],
            ],
        ];

        $records = [];
        foreach ($templates as $name => $fields) {
            $records[$name] = ServiceResultTemplate::updateOrCreate(
                ['name' => $name],
                ['description' => 'Mẫu có thể điều chỉnh theo quy trình chuyên môn của cơ sở.', 'fields' => $fields, 'is_active' => true]
            );
        }

        $groupTemplate = [
            'XN' => $records['Phiếu xét nghiệm'],
            'CDHA' => $records['Phiếu chẩn đoán hình ảnh'],
            'SA' => $records['Phiếu chẩn đoán hình ảnh'],
            'NS' => $records['Phiếu nội soi'],
            'TDCN' => $records['Phiếu thăm dò chức năng'],
            'TT' => $records['Phiếu thực hiện dịch vụ'],
            'PHCN' => $records['Phiếu thực hiện dịch vụ'],
            'CSSK' => $records['Phiếu thực hiện dịch vụ'],
        ];

        foreach (MedicalService::with('serviceGroups')->get() as $service) {
            $codes = $service->serviceGroups->pluck('service_group_code');
            $template = $codes->count() === 1 ? ($groupTemplate[$codes->first()] ?? null) : null;
            $service->forceFill(['service_result_template_id' => $template?->id])->save();
        }
    }
}
