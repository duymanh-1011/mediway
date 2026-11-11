<?php

namespace Tests\Feature;

use App\Models\DoctorSchedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/** Kiểm tra khóa thật trên MySQL thử nghiệm, không dùng cơ sở dữ liệu chính. */
class DoctorScheduleMySqlConcurrencyTest extends TestCase
{
    public function test_two_concurrent_imports_cannot_create_the_same_shift(): void
    {
        if (DB::getDriverName() !== 'mysql' || DB::getDatabaseName() !== 'mediway_codex_schedule_20261110') {
            $this->markTestSkipped('Cần cơ sở dữ liệu MySQL thử nghiệm dành riêng cho lịch.');
        }

        $token = 'schedule-race-'.Str::uuid();
        $prefix = storage_path('framework/testing/'.$token);
        if (! is_dir(dirname($prefix))) mkdir(dirname($prefix), 0777, true);
        $firstReady = $prefix.'-first';
        $secondReady = $prefix.'-second';
        $release = $prefix.'-release';
        $script = <<<'PHP'
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (Illuminate\Support\Facades\DB::getDatabaseName() !== 'mediway_codex_schedule_20261110') throw new RuntimeException('Sai cơ sở dữ liệu thử nghiệm.');
[$script, $role, $ready, $release, $note] = $argv;
$marked = false;
if ($role === 'second') {
    Illuminate\Support\Facades\DB::connection()->beforeExecuting(function ($query) use ($ready, &$marked) {
        $sql = strtolower(str_replace('`', '', $query));
        if (! $marked && str_contains($sql, 'from doctors') && str_contains($sql, 'for update')) { $marked = true; file_put_contents($ready, 'ready'); }
    });
} else {
    Illuminate\Support\Facades\DB::listen(function ($event) use ($ready, $release, &$marked) {
        $sql = strtolower(str_replace('`', '', $event->sql));
        if ($marked || ! str_contains($sql, 'from doctors') || ! str_contains($sql, 'for update')) return;
        $marked = true; file_put_contents($ready, 'ready');
        $deadline = microtime(true) + 20;
        while (! is_file($release) && microtime(true) < $deadline) { clearstatcache(true, $release); usleep(10000); }
        if (! is_file($release)) throw new RuntimeException('Hết thời gian chờ kiểm thử.');
    });
}
try {
    $result = app(App\Services\DoctorScheduleImportService::class)->import([2 => ['BS000001', '15/12/2040', 'Sáng', 'CKTMH', 'PKTMH01', $note]]);
    echo json_encode(['result' => 'created', 'data' => $result], JSON_THROW_ON_ERROR);
} catch (Illuminate\Validation\ValidationException $e) {
    echo json_encode(['result' => 'conflict', 'errors' => $e->errors()], JSON_THROW_ON_ERROR);
}
PHP;
        $env = ['APP_ENV' => 'testing', 'DB_CONNECTION' => 'mysql', 'DB_DATABASE' => 'mediway_codex_schedule_20261110', 'DB_URL' => '', 'CACHE_STORE' => 'array'];
        $workers = [
            new Process([PHP_BINARY, '-d', 'extension=zip', '-r', $script, 'first', $firstReady, $release, $token], base_path(), $env),
            new Process([PHP_BINARY, '-d', 'extension=zip', '-r', $script, 'second', $secondReady, $release, $token], base_path(), $env),
        ];
        try {
            $workers[0]->start();
            $this->awaitMarker($workers[0], $firstReady);
            $workers[1]->start();
            $this->awaitMarker($workers[1], $secondReady);
            $this->assertTrue($workers[1]->isRunning(), 'Lượt nhập thứ hai phải chờ khóa bác sĩ.');
            file_put_contents($release, 'release');
            foreach ($workers as $worker) {
                $worker->wait();
                $this->assertTrue($worker->isSuccessful(), $worker->getErrorOutput());
            }
            $this->assertSame('created', json_decode($workers[0]->getOutput(), true)['result']);
            $this->assertSame('conflict', json_decode($workers[1]->getOutput(), true)['result']);
            $this->assertSame(1, DoctorSchedule::where('notes', $token)->count());
            $this->assertSame(9, DoctorSchedule::where('notes', $token)->firstOrFail()->appointmentTimeSlots()->count());
        } finally {
            file_put_contents($release, 'release');
            foreach ($workers as $worker) if ($worker->isRunning()) $worker->stop(2);
            foreach (DoctorSchedule::where('notes', $token)->get() as $schedule) {
                $schedule->appointmentTimeSlots()->delete();
                $schedule->delete();
            }
            foreach ([$firstReady, $secondReady, $release] as $file) if (is_file($file)) unlink($file);
        }
    }

    private function awaitMarker(Process $worker, string $path): void
    {
        $deadline = microtime(true) + 20;
        while (! is_file($path) && $worker->isRunning() && microtime(true) < $deadline) {
            clearstatcache(true, $path);
            usleep(10000);
        }
        $this->assertFileExists($path, $worker->getErrorOutput().$worker->getOutput());
    }
}
