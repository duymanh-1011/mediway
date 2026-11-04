<?php

// Standalone integration test: it never migrates, clears, or drops a database.
// Run only with DB_CONNECTION=mysql and DB_DATABASE=mediway_f04_test_20261104_01.
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Appointment;
use App\Models\AppointmentStatus;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Employee;
use App\Models\ExaminationRoom;
use App\Models\MedicalVisit;
use App\Models\Patient;
use App\Models\Specialty;
use App\Models\User;
use App\Services\AppointmentReceptionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;

if (DB::getDriverName() !== 'mysql' || DB::getDatabaseName() !== 'mediway_f04_test_20261104_01'
    || config('database.connections.mysql.host') !== '127.0.0.1') {
    throw new RuntimeException('Từ chối: chỉ chạy trên database MySQL kiểm thử riêng đã chỉ định.');
}
$testNow = getenv('RECEPTION_TEST_NOW') ?: Carbon::parse('2030-01-01 09:00:00')->addDays(random_int(1, 2500))->toDateTimeString();
Carbon::setTestNow($testNow);

if (($argv[1] ?? '') === 'worker') {
    [$script, $mode, $appointmentId, $employeeCode, $phase, $ready, $release] = $argv;
    $marked = false;
    DB::connection()->beforeExecuting(function ($sql, $bindings, $connection) use ($phase, $ready, &$marked): void {
        $sql = str_replace('`', '', strtolower($sql));
        if ($phase === 'wait' && ! $marked && str_contains($sql, 'doctor_schedules') && str_contains($sql, 'for update')) {
            if ($connection->transactionLevel() < 1) throw new RuntimeException('Thiếu transaction.');
            $marked = true;
            file_put_contents($ready, 'ready');
        }
    });
    DB::listen(function ($event) use ($phase, $ready, $release, &$marked): void {
        $sql = str_replace('`', '', strtolower($event->sql));
        if ($phase !== 'hold' || $marked || ! str_starts_with($sql, 'insert into medical_visits')) return;
        $marked = true;
        file_put_contents($ready, 'ready');
        $deadline = microtime(true) + 30;
        while (! is_file($release) && microtime(true) < $deadline) { clearstatcache(true, $release); usleep(10000); }
        if (! is_file($release)) throw new RuntimeException('Hết thời gian chờ kết nối thứ hai.');
    });
    try {
        $user = User::where('business_code', $employeeCode)->firstOrFail();
        app(AppointmentReceptionService::class)->checkIn(Appointment::findOrFail($appointmentId), $user, true);
        echo json_encode(['result' => 'success'], JSON_THROW_ON_ERROR);
    } catch (ValidationException $exception) {
        echo json_encode(['result' => 'rejected', 'errors' => array_keys($exception->errors())], JSON_THROW_ON_ERROR);
    }
    exit;
}

$secondEmployee = Employee::where('employee_code', 'NV000002')->firstOrFail();
$secondEmployee->detail->update(['employee_type' => 'RECEPTIONIST']);
$schedule = DoctorSchedule::create([
    'doctor_id' => Doctor::where('doctor_code', 'BS000001')->value('id'),
    'specialty_id' => Specialty::where('specialty_code', 'CKTMH')->value('id'),
    'examination_room_id' => ExaminationRoom::where('room_code', 'PKTMH01')->value('id'),
    'work_date' => today()->toDateString(), 'start_time' => '09:00', 'end_time' => '11:00', 'status' => 'ACTIVE',
]);
$slot = $schedule->appointmentTimeSlots()->create(['start_time' => '09:00', 'end_time' => '09:30', 'max_patients' => 2, 'status' => 'ACTIVE']);
$appointment = Appointment::create([
    'appointment_code' => 'LHRACE'.strtoupper(Str::random(12)), 'patient_id' => Patient::where('patient_code', 'BN000005')->value('id'),
    'doctor_id' => $schedule->doctor_id, 'specialty_id' => $schedule->specialty_id, 'examination_room_id' => $schedule->examination_room_id,
    'appointment_date' => $schedule->work_date, 'appointment_time_slot_id' => $slot->id,
    'appointment_status_id' => AppointmentStatus::where('name', 'BOOKED')->value('id'),
    'checkin_token' => (string) Str::uuid(), 'reason_for_visit' => 'Kiểm thử tiếp nhận đồng thời', 'booked_at' => now(),
]);
$prefix = sys_get_temp_dir().DIRECTORY_SEPARATOR.'mediway-f04-'.Str::uuid();
$ready1 = $prefix.'-1'; $ready2 = $prefix.'-2'; $release = $prefix.'-release';
$env = ['DB_CONNECTION' => 'mysql', 'DB_DATABASE' => 'mediway_f04_test_20261104_01', 'DB_URL' => '', 'APP_ENV' => 'testing', 'RECEPTION_TEST_NOW' => $testNow];
$first = new Process([PHP_BINARY, __FILE__, 'worker', (string) $appointment->id, 'NV000001', 'hold', $ready1, $release], base_path(), $env);
$second = new Process([PHP_BINARY, __FILE__, 'worker', (string) $appointment->id, 'NV000002', 'wait', $ready2, $release], base_path(), $env);
$await = static function (Process $process, string $path): void {
    $deadline = microtime(true) + 20;
    while (! is_file($path) && $process->isRunning() && microtime(true) < $deadline) { clearstatcache(true, $path); usleep(10000); }
    if (! is_file($path)) throw new RuntimeException('Không nhận được tín hiệu: '.$process->getErrorOutput().$process->getOutput());
};
try {
    $first->start(); $await($first, $ready1);
    $second->start(); $await($second, $ready2);
    usleep(250000);
    if (! $second->isRunning()) throw new RuntimeException('Kết nối thứ hai không chờ khóa.');
    file_put_contents($release, 'release');
    $first->wait(); $second->wait();
    if (! $first->isSuccessful() || ! $second->isSuccessful()) throw new RuntimeException($first->getErrorOutput().$second->getErrorOutput());
    $results = [json_decode($first->getOutput(), true, 512, JSON_THROW_ON_ERROR), json_decode($second->getOutput(), true, 512, JSON_THROW_ON_ERROR)];
    $visit = MedicalVisit::where('appointment_id', $appointment->id)->firstOrFail();
    if ($results[0]['result'] !== 'success' || $results[1]['result'] !== 'rejected'
        || MedicalVisit::where('appointment_id', $appointment->id)->count() !== 1
        || $visit->payables()->where('payment_type', 'EXAMINATION_FEE')->count() !== 1
        || $visit->queues()->count() !== 0 || $appointment->fresh()->appointmentStatus?->name !== 'CHECKED_IN') {
        throw new RuntimeException('Dữ liệu hoặc kết quả kiểm thử đồng thời không đúng.');
    }
    echo "ĐẠT: hai nhân viên đồng thời; một tiếp nhận, một bị từ chối; một lượt khám, một khoản phí, chưa cấp queue.\n";
} finally {
    if (! is_file($release)) file_put_contents($release, 'release');
    foreach ([$first, $second] as $process) if ($process->isRunning()) $process->stop(3);
    foreach ([$ready1, $ready2, $release] as $file) if (is_file($file)) unlink($file);
}
