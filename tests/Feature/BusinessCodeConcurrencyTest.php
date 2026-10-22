<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class BusinessCodeConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDown();
    }

    public function test_mysql_workers_never_receive_the_same_business_code(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Kiểm tra khóa liên tiến trình trong suite MySQL cô lập.');
        }
        $this->assertSame('mediway_codex_test_20261019', DB::getDatabaseName());
        $script = <<<'PHP'
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (Illuminate\Support\Facades\DB::getDatabaseName() !== 'mediway_codex_test_20261019') {
    throw new RuntimeException('Refuse to allocate test codes on another database.');
}
while (microtime(true) < (float) $argv[1]) { usleep(1000); }
$codes = [];
for ($index = 0; $index < 10; $index++) {
    $codes[] = app(App\Models\BusinessCodeGenerator::class)->next('service_code');
    usleep(10000);
}
echo json_encode($codes, JSON_THROW_ON_ERROR);
PHP;
        $environment = ['APP_ENV' => 'testing', 'DB_CONNECTION' => 'mysql', 'DB_DATABASE' => 'mediway_codex_test_20261019', 'DB_URL' => ''];
        $releaseAt = (string) (microtime(true) + 3);
        $workers = [new Process([PHP_BINARY, '-r', $script, $releaseAt], base_path(), $environment), new Process([PHP_BINARY, '-r', $script, $releaseAt], base_path(), $environment)];
        foreach ($workers as $worker) {
            $worker->start();
        }
        $codes = [];
        foreach ($workers as $worker) {
            $worker->wait();
            $this->assertTrue($worker->isSuccessful(), $worker->getErrorOutput().$worker->getOutput());
            $codes = array_merge($codes, json_decode($worker->getOutput(), true, 512, JSON_THROW_ON_ERROR));
        }
        $this->assertCount(20, $codes);
        $this->assertCount(20, array_unique($codes));
        $this->assertDatabaseHas('business_code_sequences', ['scope' => 'service_code', 'last_value' => 20]);
        $this->assertDatabaseCount('medical_services', 0);
    }
}
