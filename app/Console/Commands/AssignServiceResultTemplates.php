<?php

namespace App\Console\Commands;

use App\Models\MedicalService;
use App\Models\ServiceResultTemplate;
use App\Support\ServiceResultTemplateGroupMap;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AssignServiceResultTemplates extends Command
{
    protected $signature = 'medical-services:assign-result-templates
        {--apply : Gán các mẫu khớp rõ ràng; mặc định chỉ xem trước}';

    protected $description = 'Xem trước hoặc gán mẫu kết quả cho dịch vụ chưa có mẫu theo đúng một nhóm dịch vụ đã biết.';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $templates = ServiceResultTemplate::query()
            ->where('is_active', true)
            ->get(['id', 'name'])
            ->keyBy('name');

        $matched = 0;
        $assigned = 0;
        $unmatched = 0;

        MedicalService::query()
            ->whereNull('service_result_template_id')
            ->with('serviceGroups:id,service_group_code')
            ->orderBy('id')
            ->chunkById(100, function ($services) use ($apply, $templates, &$matched, &$assigned, &$unmatched): void {
                foreach ($services as $service) {
                    $codes = $service->serviceGroups->pluck('service_group_code')->all();
                    $templateName = ServiceResultTemplateGroupMap::templateName($codes);
                    $template = $templateName ? $templates->get($templateName) : null;

                    if (! $template) {
                        $unmatched++;
                        $this->line(sprintf(
                            'Cần gán thủ công: %s — %s (nhóm: %s)',
                            $service->service_code,
                            $service->name,
                            $codes === [] ? 'chưa có nhóm' : implode(', ', $codes)
                        ));

                        continue;
                    }

                    $matched++;
                    $this->line(sprintf(
                        '%s: %s — %s → %s',
                        $apply ? 'Sẽ gán' : 'Xem trước',
                        $service->service_code,
                        $service->name,
                        $template->name
                    ));

                    if (! $apply) {
                        continue;
                    }

                    $updated = DB::transaction(function () use ($service, $template): int {
                        $locked = MedicalService::query()
                            ->whereKey($service->id)
                            ->lockForUpdate()
                            ->first();
                        if (! $locked || $locked->service_result_template_id !== null) {
                            return 0;
                        }

                        $locked->update(['service_result_template_id' => $template->id]);

                        return 1;
                    });

                    $assigned += $updated;
                }
            });

        $this->newLine();
        $this->info('Khớp mẫu: '.$matched.'; đã gán: '.$assigned.'; cần gán thủ công: '.$unmatched.'.');
        if (! $apply) {
            $this->comment('Chưa thay đổi dữ liệu. Rà soát danh sách trên, sau đó chạy lại với --apply trên database đã sao lưu nếu đồng ý.');
        }

        return self::SUCCESS;
    }
}
