<?php

namespace App\Console\Commands;

use App\Models\WorkflowIntegrityRepair;
use Illuminate\Console\Command;
use RuntimeException;

class RepairWorkflowIntegrity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mediway:repair-workflow-integrity {--apply : Áp dụng sau khi đối chiếu, sao lưu và ghi nhật ký}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kiểm tra khoản thu thiếu và hàng đợi của lượt khám đã hoàn tất; mặc định chỉ xem trước';

    /**
     * Execute the console command.
     */
    public function handle(WorkflowIntegrityRepair $repair): int
    {
        if (! $this->option('apply')) {
            $this->line(json_encode($repair->inspect(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            $this->info('Chỉ xem trước, chưa thay đổi dữ liệu.');

            return self::SUCCESS;
        }
        try {
            $result = $repair->apply();
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
        $this->info('Đã khôi phục '.$result['payables_created'].' khoản thu; đóng '.$result['queues_completed'].' hàng đợi.');
        if ($result['backup_path']) {
            $this->line('Bản sao lưu riêng tư: '.$result['backup_path']);
        }

        return self::SUCCESS;
    }
}
