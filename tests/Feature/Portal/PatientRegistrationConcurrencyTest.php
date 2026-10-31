<?php

namespace Tests\Feature\Portal;

use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Illuminate\Foundation\Testing\TestCase;

class PatientRegistrationConcurrencyTest extends TestCase
{
    public function createApplication(): \Illuminate\Foundation\Application
    {
        $app = parent::createApplication();
        $connection = $app['db']->connection();
        if (! (($connection->getDriverName() === 'sqlite' && $connection->getDatabaseName() === ':memory:')
            || ($connection->getDriverName() === 'mysql' && in_array($connection->getDatabaseName(), ['mediway_portal_test_20261029_manh', 'mediway_codex_test_20261019'], true)))) {
            throw new \RuntimeException('Refusing to run outside the isolated test database.');
        }
        return $app;
    }

    public function test_mysql_parallel_registration_preserves_uniqueness_and_transactions(): void
    {
        if (DB::getDriverName() !== 'mysql' || DB::getDatabaseName() !== 'mediway_portal_test_20261029_manh') {
            $this->markTestSkipped('Cần database MySQL kiểm thử riêng mediway_portal_test_20261029_manh.');
        }
        Artisan::call('migrate', ['--force' => true]);
        Role::firstOrCreate(['name' => 'Patient'], ['status' => 'ACTIVE']);
        do {
            $number = random_int(1000000, 9999997);
            $phones = ['098'.$number, '098'.($number + 1), '098'.($number + 2)];
        } while (User::whereIn('phone', $phones)->exists());

        $this->runPair($phones[0], $phones[0]);
        $this->assertSame(1, User::where('phone', $phones[0])->count());
        $this->assertSame(1, Patient::whereHas('user', fn ($query) => $query->where('phone', $phones[0]))->count());
        $this->runPair($phones[1], $phones[2]);
        $users = User::whereIn('phone', $phones)->get();
        $patients = Patient::whereIn('user_id', $users->modelKeys())->with('user')->get();
        $this->assertCount(3, $users);
        $this->assertCount(3, $patients);
        $this->assertCount(3, $users->pluck('business_code')->unique());
        $this->assertCount(3, $patients->pluck('patient_code')->unique());
        foreach ($patients as $patient) {
            $this->assertSame($patient->user->business_code, $patient->patient_code);
        }
    }

    private function runPair(string $firstPhone, string $secondPhone): void
    {
        $prefix = storage_path('framework/testing/portal-registration-'.Str::uuid());
        if (! is_dir(dirname($prefix))) {
            mkdir(dirname($prefix), 0777, true);
        }
        $script = <<<'PHP'
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (Illuminate\Support\Facades\DB::getDatabaseName() !== 'mediway_portal_test_20261029_manh') {
    throw new RuntimeException('Invalid isolated test database.');
}
$first = $argv[2] === 'first';
$ready = $argv[3];
$release = $argv[4];
$marked = false;
Illuminate\Support\Facades\DB::connection()->beforeExecuting(function ($query) use ($first, $ready, &$marked) {
    $sql = str_replace('`', '', strtolower($query));
    if (! $first && ! $marked && str_contains($sql, 'from roles') && str_contains($sql, 'for update')) {
        $marked = true;
        file_put_contents($ready, 'ready');
    }
});
Illuminate\Support\Facades\DB::listen(function ($event) use ($first, $ready, $release, &$marked) {
    $sql = str_replace('`', '', strtolower($event->sql));
    if (! $first || $marked || ! str_starts_with($sql, 'insert into users')) return;
    $marked = true;
    file_put_contents($ready, 'ready');
    $deadline = microtime(true) + 30;
    while (! is_file($release) && microtime(true) < $deadline) { clearstatcache(true, $release); usleep(10000); }
    if (! is_file($release)) throw new RuntimeException('Barrier timeout.');
});
$request = Illuminate\Http\Request::create('/tai-khoan/dang-ky', 'POST', ['full_name' => 'Kiểm thử', 'phone' => $argv[1], 'password' => 'AnToan@123', 'password_confirmation' => 'AnToan@123'], [], [], ['HTTP_ACCEPT' => 'application/json']);
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request);
echo json_encode(['status' => $response->getStatusCode()], JSON_THROW_ON_ERROR);
$kernel->terminate($request, $response);
PHP;
        $env = ['APP_ENV' => 'testing', 'DB_CONNECTION' => 'mysql', 'DB_HOST' => '127.0.0.1', 'DB_PORT' => '3306', 'DB_DATABASE' => 'mediway_portal_test_20261029_manh', 'DB_USERNAME' => 'root', 'DB_PASSWORD' => '', 'DB_URL' => '', 'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array', 'BCRYPT_ROUNDS' => '4'];
        $release = $prefix.'-release';
        $firstReady = $prefix.'-first';
        $secondReady = $prefix.'-second';
        $workers = [
            new Process([PHP_BINARY, '-r', $script, $firstPhone, 'first', $firstReady, $release], base_path(), $env),
            new Process([PHP_BINARY, '-r', $script, $secondPhone, 'second', $secondReady, $release], base_path(), $env),
        ];
        try {
            $workers[0]->start();
            $this->awaitMarker($workers[0], $firstReady);
            $workers[1]->start();
            $this->awaitMarker($workers[1], $secondReady);
            $this->assertTrue($workers[1]->isRunning(), 'Yêu cầu thứ hai phải chờ transaction đầu tiên.');
            file_put_contents($release, 'release');
            foreach ($workers as $worker) {
                $worker->wait();
                $this->assertTrue($worker->isSuccessful(), $worker->getErrorOutput());
                $response = json_decode($worker->getOutput(), true, 512, JSON_THROW_ON_ERROR);
                $this->assertSame(302, $response['status']);
            }
        } finally {
            file_put_contents($release, 'release');
            foreach ($workers as $worker) {
                if ($worker->isRunning()) $worker->stop(3);
            }
        }
    }

    private function awaitMarker(Process $worker, string $marker): void
    {
        $deadline = microtime(true) + 25;
        while (! is_file($marker) && $worker->isRunning() && microtime(true) < $deadline) {
            clearstatcache(true, $marker);
            usleep(10000);
        }
        $this->assertFileExists($marker, $worker->getErrorOutput());
    }
}
