<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentStatus;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\ExaminationRoom;
use App\Models\MedicalVisit;
use App\Models\Patient;
use App\Models\Specialty;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class AppointmentSlotConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDown();
    }

    public function test_mysql_serializes_overlapping_slot_creation_and_capacity_changes_with_booking(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Cần MySQL và hai kết nối độc lập để xác minh khóa hàng.');
        }
        $this->assertSame('mediway_codex_test_20261019', DB::getDatabaseName());
        $this->seed(DatabaseSeeder::class);
        foreach (['overlap', 'capacity', 'duplicate', 'duplicate_checkin'] as $index => $scenario) {
            $schedule = DoctorSchedule::create([
                'doctor_id' => Doctor::where('doctor_code', 'BS000001')->value('id'),
                'specialty_id' => Specialty::where('specialty_code', 'CKTMH')->value('id'),
                'examination_room_id' => ExaminationRoom::where('room_code', 'PKTMH01')->value('id'),
                'work_date' => $scenario === 'duplicate_checkin' ? today()->toDateString() : now()->addDays($index + 1)->toDateString(),
                'start_time' => '09:00:00', 'end_time' => '11:00:00', 'status' => 'ACTIVE',
            ]);
            $data = ['start_time' => '09:00', 'end_time' => '09:30', 'max_patients' => 1, 'status' => 'ACTIVE'];
            $first = ['uri' => route('admin.doctor-schedules.time-slots.store', $schedule), 'method' => 'POST', 'data' => $data, 'pause' => 'insert'];
            $second = ['uri' => $first['uri'], 'method' => 'POST', 'data' => [...$data, 'start_time' => '09:15', 'end_time' => '09:45'], 'pause' => 'lock'];
            if ($scenario === 'capacity') {
                $slot = $schedule->appointmentTimeSlots()->create(['start_time' => '09:00:00', 'end_time' => '09:30:00', 'max_patients' => 2, 'status' => 'ACTIVE']);
                Appointment::create([
                    'appointment_code' => 'LHRACEINITIAL', 'patient_id' => Patient::where('patient_code', 'BN000001')->value('id'),
                    'doctor_id' => $schedule->doctor_id, 'specialty_id' => $schedule->specialty_id,
                    'examination_room_id' => $schedule->examination_room_id, 'appointment_date' => $schedule->work_date,
                    'appointment_time_slot_id' => $slot->id, 'appointment_status_id' => AppointmentStatus::where('name', 'BOOKED')->value('id'),
                    'checkin_token' => (string) Str::uuid(), 'reason_for_visit' => 'Kiểm thử sức chứa', 'booked_at' => now(),
                ]);
                $first = ['uri' => route('admin.appointment-time-slots.update', $slot), 'method' => 'PUT', 'data' => $data, 'pause' => 'count'];
                $second = ['uri' => route('admin.appointments.store'), 'method' => 'POST', 'data' => [
                    'patient_id' => Patient::where('patient_code', 'BN000005')->value('id'),
                    'appointment_time_slot_id' => $slot->id, 'reason_for_visit' => 'Đặt lịch đồng thời',
                ], 'pause' => 'lock'];
            }
            if (in_array($scenario, ['duplicate', 'duplicate_checkin'], true)) {
                $slot = $schedule->appointmentTimeSlots()->create(['start_time' => '09:00:00', 'end_time' => '09:30:00', 'max_patients' => 10, 'status' => 'ACTIVE']);
                $first = ['uri' => route('admin.appointments.store'), 'method' => 'POST', 'data' => [
                    'patient_id' => Patient::where('patient_code', 'BN000005')->value('id'),
                    'appointment_time_slot_id' => $slot->id, 'reason_for_visit' => 'Đặt lịch không trùng',
                ], 'pause' => 'appointment_insert'];
                $second = [...$first, 'pause' => 'lock'];
                if ($scenario === 'duplicate_checkin') {
                    $existing = Appointment::create([
                        ...$first['data'], 'appointment_code' => 'LHRACERECEPTION', 'doctor_id' => $schedule->doctor_id,
                        'specialty_id' => $schedule->specialty_id, 'examination_room_id' => $schedule->examination_room_id,
                        'appointment_date' => $schedule->work_date, 'checkin_token' => (string) Str::uuid(),
                        'appointment_status_id' => AppointmentStatus::where('name', 'BOOKED')->value('id'), 'booked_at' => today()->setTime(8, 0),
                    ]);
                    $duplicate = $existing->replicate();
                    $duplicate->appointment_code = 'LHRACERECEPTIONDUP';
                    $duplicate->checkin_token = (string) Str::uuid();
                    $duplicate->save();
                    $first = ['uri' => route('admin.appointments.check-in', $existing), 'method' => 'POST', 'data' => [], 'pause' => 'visit_insert'];
                    $second = ['uri' => route('admin.appointments.check-in', $duplicate), 'method' => 'POST', 'data' => [], 'pause' => 'lock'];
                    $first['test_now'] = today()->setTime(9, 0)->toDateTimeString();
                    $second['test_now'] = $first['test_now'];
                }
            }
            $this->runConcurrentRequests($first, $second);
            if ($scenario === 'overlap') {
                $this->assertSame(1, $schedule->appointmentTimeSlots()->count());
            } elseif ($scenario === 'capacity') {
                $this->assertSame(1, $slot->fresh()->max_patients);
                $this->assertSame(1, $slot->appointments()->count());
            } elseif ($scenario === 'duplicate') {
                $this->assertSame(1, $slot->appointments()->count());
            } else {
                $this->assertSame(1, MedicalVisit::whereIn('appointment_id', [$existing->id, $duplicate->id])->count());
            }
        }
    }

    /** @param array{uri: string, method: string, data: array<string, mixed>, pause: string} $first
     * @param  array{uri: string, method: string, data: array<string, mixed>, pause: string}  $second
     */
    private function runConcurrentRequests(array $first, array $second): void
    {
        File::ensureDirectoryExists(storage_path('framework/testing'));
        $prefix = storage_path('framework/testing/slot-concurrency-'.Str::uuid());
        $release = $prefix.'-release';
        $firstReady = $prefix.'-first';
        $secondReady = $prefix.'-second';
        $script = <<<'PHP'
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (Illuminate\Support\Facades\DB::getDatabaseName() !== 'mediway_codex_test_20261019') {
    throw new RuntimeException('Refuse to run concurrency test outside the isolated test database.');
}
$payload = json_decode($argv[1], true, 512, JSON_THROW_ON_ERROR);
if (isset($payload['test_now'])) Carbon\Carbon::setTestNow($payload['test_now']);
$ready = $argv[2];
$release = $argv[3];
$marked = false;
Illuminate\Support\Facades\DB::connection()->beforeExecuting(function ($query, $bindings, $connection) use ($payload, $ready, &$marked): void {
    $sql = str_replace('`', '', strtolower($query));
    if ($payload['pause'] === 'lock' && ! $marked && str_contains($sql, 'doctor_schedules') && str_contains($sql, 'for update')) {
        if ($connection->transactionLevel() < 1) {
            throw new RuntimeException('Parent lock must be held inside a transaction.');
        }
        $marked = true;
        file_put_contents($ready, 'ready');
    }
});
Illuminate\Support\Facades\DB::listen(function ($event) use ($payload, $ready, $release, &$marked): void {
    $sql = str_replace('`', '', strtolower($event->sql));
    $matches = ($payload['pause'] === 'insert' && str_starts_with($sql, 'insert into appointment_time_slots'))
        || ($payload['pause'] === 'count' && str_contains($sql, 'count(*)') && str_contains($sql, 'from appointments'))
        || ($payload['pause'] === 'appointment_insert' && str_starts_with($sql, 'insert into appointments'))
        || ($payload['pause'] === 'visit_insert' && str_starts_with($sql, 'insert into medical_visits'));
    if ($marked || ! $matches) {
        return;
    }
    $marked = true;
    file_put_contents($ready, 'ready');
    $deadline = microtime(true) + 30;
    while (! is_file($release) && microtime(true) < $deadline) {
        clearstatcache(true, $release);
        usleep(10000);
    }
    if (! is_file($release)) {
        throw new RuntimeException('Timed out waiting for the concurrent request.');
    }
});
Illuminate\Support\Facades\Auth::setUser(App\Models\User::where('business_code', 'QT000001')->firstOrFail());
$request = Illuminate\Http\Request::create($payload['uri'], $payload['method'], $payload['data'], [], [], ['HTTP_ACCEPT' => 'application/json']);
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request);
echo json_encode(['status' => $response->getStatusCode(), 'body' => json_decode($response->getContent(), true)], JSON_THROW_ON_ERROR);
$kernel->terminate($request, $response);
PHP;
        $environment = ['APP_ENV' => 'testing', 'DB_CONNECTION' => 'mysql', 'DB_DATABASE' => 'mediway_codex_test_20261019',
            'DB_URL' => '', 'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array', 'BCRYPT_ROUNDS' => '4', 'PAO_DISABLE' => '1'];
        $workers = [
            new Process([PHP_BINARY, '-r', $script, json_encode($first, JSON_THROW_ON_ERROR), $firstReady, $release], base_path(), $environment),
            new Process([PHP_BINARY, '-r', $script, json_encode($second, JSON_THROW_ON_ERROR), $secondReady, $release], base_path(), $environment),
        ];
        try {
            $workers[0]->start();
            $this->awaitMarker($workers[0], $firstReady);
            $workers[1]->start();
            $this->awaitMarker($workers[1], $secondReady);
            $this->assertTrue($workers[1]->isRunning(), 'Request thứ hai phải chờ khóa lịch bác sĩ.');
            File::put($release, 'release');
            $responses = [];
            foreach ($workers as $worker) {
                $worker->wait();
                $this->assertTrue($worker->isSuccessful(), $worker->getErrorOutput().$worker->getOutput());
                $responses[] = json_decode($worker->getOutput(), true, 512, JSON_THROW_ON_ERROR);
            }
            $this->assertSame(302, $responses[0]['status'], json_encode($responses[0]));
            $this->assertSame(422, $responses[1]['status'], json_encode($responses[1]));
        } finally {
            File::put($release, 'release');
            foreach ($workers as $worker) {
                if ($worker->isRunning()) {
                    $worker->stop(3);
                }
            }
            File::delete([$release, $firstReady, $secondReady]);
        }
    }

    private function awaitMarker(Process $worker, string $marker): void
    {
        $deadline = microtime(true) + 20;
        while (! is_file($marker) && $worker->isRunning() && microtime(true) < $deadline) {
            clearstatcache(true, $marker);
            usleep(10000);
        }

        $this->assertFileExists($marker, $worker->getErrorOutput().$worker->getOutput());
    }
}
