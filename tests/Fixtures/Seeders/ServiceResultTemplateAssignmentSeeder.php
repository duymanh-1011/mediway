<?php

namespace Tests\Fixtures\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ServiceResultTemplateAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        $templates = DB::table('service_result_templates')->pluck('id', 'name');
        $assignments = [
            'Phiếu xét nghiệm' => ['DVXN001', 'DVXN002', 'DVXN003', 'DVXN004', 'DVXN005', 'DVXN006', 'DVXN007'],
            'Phiếu chẩn đoán hình ảnh' => ['DVCD001', 'DVCD002', 'DVCD003', 'DVCD004', 'DVCD005', 'DVCD006', 'DVSA001', 'DVSA002', 'DVSA003'],
            'Phiếu nội soi' => ['DVTDCN002', 'DVNS001', 'DVNS002', 'DVNS003'],
            'Phiếu thăm dò chức năng' => ['DVTDCN001', 'DVTDCN003', 'DVTDCN004', 'DVTDCN005'],
            'Phiếu thực hiện dịch vụ' => ['DVTT001', 'DVTT002', 'DVTT003', 'DVTT004', 'DVPHCN001', 'DVPHCN002', 'DVPHCN003', 'DVCSSK001', 'DVCSSK002', 'DVCSSK003'],
        ];

        DB::table('service_result_templates')->update(['effective_from' => '1970-01-01']);

        foreach ($assignments as $templateName => $serviceCodes) {
            $templateId = $templates[$templateName] ?? null;
            if (! $templateId) {
                throw new RuntimeException('Thiếu mẫu phiếu kiểm thử: '.$templateName);
            }

            foreach ($serviceCodes as $serviceCode) {
                $serviceId = DB::table('medical_services')->where('service_code', $serviceCode)->value('id');
                if (! $serviceId) {
                    throw new RuntimeException('Thiếu dịch vụ kiểm thử: '.$serviceCode);
                }

                DB::table('medical_service_result_templates')->insertOrIgnore([
                    'medical_service_id' => $serviceId,
                    'service_result_template_id' => $templateId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
