<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MedicalService;

class AssignServiceResultTemplates extends Command
{
    protected $signature = 'medical-services:assign-result-templates
        {--apply : Không còn hỗ trợ; mẫu phải được gán thủ công trong trang quản trị}';

    protected $description = 'Liệt kê dịch vụ cần được gán mẫu kết quả thủ công trong trang quản trị.';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        if ($apply) {
            $this->error('Đã tắt tự động gán mẫu. Hãy gán dịch vụ thủ công trong trang Quản trị → Mẫu phiếu kết quả.');

            return self::FAILURE;
        }

        $services = MedicalService::query()
            ->where('status', 'ACTIVE')
            ->whereDoesntHave('resultTemplates', fn ($query) => $query
                ->where('service_result_templates.is_active', true)
                ->whereDate('service_result_templates.effective_from', '<=', today()->toDateString())
                ->where(fn ($query) => $query->whereNull('service_result_templates.effective_until')
                    ->orWhereDate('service_result_templates.effective_until', '>=', today()->toDateString())))
            ->orderBy('service_code')
            ->get(['service_code', 'name']);
        foreach ($services as $service) $this->line($service->service_code.' — '.$service->name);
        $this->info('Còn '.$services->count().' dịch vụ đang hoạt động chưa có mẫu hiệu lực hôm nay. Danh sách chỉ để tham khảo, không có dữ liệu nào bị thay đổi.');

        return self::SUCCESS;
    }
}
