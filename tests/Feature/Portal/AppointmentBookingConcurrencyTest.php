<?php

namespace Tests\Feature\Portal;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\ExaminationRoom;
use App\Models\Patient;
use App\Models\Specialty;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class AppointmentBookingConcurrencyTest extends TestCase
{
    /*
    |--------------------------------------------------------------------------
    | Database test riêng
    |--------------------------------------------------------------------------
    |
    | TUYỆT ĐỐI không đổi thành "mediway".
    |
    | migrate:fresh chỉ được phép chạy sau khi test xác nhận connection
    | hiện tại đang trỏ chính xác vào database bên dưới.
    |
    */

    private const TEST_DATABASE =
    'mediway_codex_test_20261019';

    public function test_two_patients_cannot_overbook_the_last_slot(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Concurrency thật cần MySQL
        |--------------------------------------------------------------------------
        |
        | PHPUnit mặc định của project đang dùng SQLite :memory:.
        | SQLite không kiểm chứng SELECT ... FOR UPDATE giống MySQL.
        |
        */

        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped(
                'Concurrency test cần MySQL và hai kết nối độc lập.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Chốt an toàn database
        |--------------------------------------------------------------------------
        |
        | Không có bất kỳ migrate:fresh nào được chạy trước đoạn kiểm tra này.
        |
        */

        $this->assertSame(
            self::TEST_DATABASE,
            DB::getDatabaseName(),
            'Từ chối chạy concurrency test ngoài database test riêng.'
        );

        /*
        |--------------------------------------------------------------------------
        | Chuẩn bị database test
        |--------------------------------------------------------------------------
        |
        | Chỉ chạy sau khi đã chắc chắn DB đúng là:
        | mediway_codex_test_20261019
        |
        */

        $this->artisan(
            'migrate:fresh',
            [
                '--seed' => true,
            ]
        )->assertExitCode(0);

        /*
        |--------------------------------------------------------------------------
        | Lấy dữ liệu chuẩn từ Seeder
        |--------------------------------------------------------------------------
        */

        $doctor =
            Doctor::query()
            ->where(
                'doctor_code',
                'BS000001'
            )
            ->firstOrFail();

        $specialty =
            Specialty::query()
            ->where(
                'specialty_code',
                'CKTMH'
            )
            ->firstOrFail();

        $room =
            ExaminationRoom::query()
            ->where(
                'room_code',
                'PKTMH01'
            )
            ->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | Tạo lịch khám test
        |--------------------------------------------------------------------------
        |
        | Slot chỉ có đúng 1 chỗ.
        |
        */

        $schedule =
            DoctorSchedule::create([
                'doctor_id' =>
                $doctor->id,

                'specialty_id' =>
                $specialty->id,

                'examination_room_id' =>
                $room->id,

                'work_date' =>
                today()
                    ->addDay()
                    ->toDateString(),

                'start_time' =>
                '09:00:00',

                'end_time' =>
                '11:00:00',

                'status' =>
                'ACTIVE',
            ]);

        $slot =
            $schedule
            ->appointmentTimeSlots()
            ->create([
                'start_time' =>
                '09:00:00',

                'end_time' =>
                '09:30:00',

                'max_patients' =>
                1,

                'status' =>
                'ACTIVE',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Request 1 - BN000001
        |--------------------------------------------------------------------------
        |
        | Request đầu sẽ được giữ lại ngay sau INSERT appointments,
        | trước khi transaction commit.
        |
        */

        $first = [
            'user_code' =>
            'BN000001',

            'uri' =>
            route(
                'patient.appointments.store'
            ),

            'method' =>
            'POST',

            'data' => [
                'appointment_time_slot_id' =>
                $slot->id,

                'reason_for_visit' =>
                'Đau họng và ho nhiều.',
            ],

            'pause' =>
            'appointment_insert',
        ];

        /*
        |--------------------------------------------------------------------------
        | Request 2 - BN000002
        |--------------------------------------------------------------------------
        |
        | Chạy khi request 1 vẫn đang giữ transaction.
        |
        | Nó sẽ đi tới SELECT ... FOR UPDATE và phải chờ request 1.
        |
        */

        $second = [
            'user_code' =>
            'BN000002',

            'uri' =>
            route(
                'patient.appointments.store'
            ),

            'method' =>
            'POST',

            'data' => [
                'appointment_time_slot_id' =>
                $slot->id,

                'reason_for_visit' =>
                'Đau đầu kéo dài.',
            ],

            'pause' =>
            'lock',
        ];

        /*
        |--------------------------------------------------------------------------
        | Chạy 2 request đồng thời
        |--------------------------------------------------------------------------
        */

        $responses =
            $this->runConcurrentRequests(
                $first,
                $second
            );

        /*
        |--------------------------------------------------------------------------
        | Kiểm tra số lịch thực tế
        |--------------------------------------------------------------------------
        |
        | Dù có 2 request tranh suất cuối thì chỉ được có đúng 1 appointment.
        |
        */

        $appointments =
            Appointment::query()
            ->where(
                'appointment_time_slot_id',
                $slot->id
            )
            ->get();

        $this->assertCount(
            1,
            $appointments
        );

        /*
        |--------------------------------------------------------------------------
        | Người lấy được slot phải là một trong hai bệnh nhân
        |--------------------------------------------------------------------------
        */

        $allowedPatientIds = [
            Patient::query()
                ->where(
                    'patient_code',
                    'BN000001'
                )
                ->value(
                    'id'
                ),

            Patient::query()
                ->where(
                    'patient_code',
                    'BN000002'
                )
                ->value(
                    'id'
                ),
        ];

        $this->assertContains(
            $appointments
                ->first()
                ->patient_id,
            $allowedPatientIds
        );

        /*
        |--------------------------------------------------------------------------
        | Một request thành công, request còn lại bị từ chối
        |--------------------------------------------------------------------------
        |
        | Request thành công:
        | 302 redirect tới trang xác nhận.
        |
        | Request thất bại:
        | 422 validation khi gửi Accept: application/json.
        |
        */

        $statuses =
            collect(
                $responses
            )
            ->pluck(
                'status'
            )
            ->sort()
            ->values()
            ->all();

        $this->assertSame(
            [
                302,
                422,
            ],
            $statuses
        );

        /*
        |--------------------------------------------------------------------------
        | Chốt capacity
        |--------------------------------------------------------------------------
        */

        $this->assertSame(
            1,
            Appointment::query()
                ->where(
                    'appointment_time_slot_id',
                    $slot->id
                )
                ->count()
        );
    }

    /**
     * @param array{
     *     user_code:string,
     *     uri:string,
     *     method:string,
     *     data:array<string,mixed>,
     *     pause:string
     * } $first
     *
     * @param array{
     *     user_code:string,
     *     uri:string,
     *     method:string,
     *     data:array<string,mixed>,
     *     pause:string
     * } $second
     *
     * @return array<int,array<string,mixed>>
     */
    private function runConcurrentRequests(
        array $first,
        array $second
    ): array {
        /*
        |--------------------------------------------------------------------------
        | Thư mục marker
        |--------------------------------------------------------------------------
        */

        File::ensureDirectoryExists(
            storage_path(
                'framework/testing'
            )
        );

        $prefix =
            storage_path(
                'framework/testing/'
                    .
                    'portal-booking-concurrency-'
                    .
                    Str::uuid()
            );

        $release =
            $prefix
            .
            '-release';

        $firstReady =
            $prefix
            .
            '-first';

        $secondReady =
            $prefix
            .
            '-second';

        /*
        |--------------------------------------------------------------------------
        | Script cho từng PHP process độc lập
        |--------------------------------------------------------------------------
        */

        $script = <<<'PHP'
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';

$app
    ->make(
        Illuminate\Contracts\Console\Kernel::class
    )
    ->bootstrap();

/*
|--------------------------------------------------------------------------
| Chốt an toàn database ở worker
|--------------------------------------------------------------------------
*/

if (
    Illuminate\Support\Facades\DB::getDriverName()
    !==
    'mysql'
) {
    throw new RuntimeException(
        'Concurrency worker requires MySQL.'
    );
}

if (
    Illuminate\Support\Facades\DB::getDatabaseName()
    !==
    'mediway_codex_test_20261019'
) {
    throw new RuntimeException(
        'Refuse to run outside isolated test database.'
    );
}

$payload =
    json_decode(
        $argv[1],
        true,
        512,
        JSON_THROW_ON_ERROR
    );

$ready =
    $argv[2];

$release =
    $argv[3];

$marked =
    false;

/*
|--------------------------------------------------------------------------
| Marker cho request thứ hai
|--------------------------------------------------------------------------
|
| Marker được tạo ngay trước câu SELECT ... FOR UPDATE trên doctor_schedules.
|
*/

Illuminate\Support\Facades\DB::connection()
    ->beforeExecuting(
        function (
            $query,
            $bindings,
            $connection
        ) use (
            $payload,
            $ready,
            &$marked
        ): void {
            $sql =
                str_replace(
                    '`',
                    '',
                    strtolower(
                        $query
                    )
                );

            if (
                $payload['pause']
                ===
                'lock'
                &&
                ! $marked
                &&
                str_contains(
                    $sql,
                    'doctor_schedules'
                )
                &&
                str_contains(
                    $sql,
                    'for update'
                )
            ) {
                if (
                    $connection
                        ->transactionLevel()
                    <
                    1
                ) {
                    throw new RuntimeException(
                        'FOR UPDATE must run inside a transaction.'
                    );
                }

                $marked =
                    true;

                file_put_contents(
                    $ready,
                    'ready'
                );
            }
        }
    );

/*
|--------------------------------------------------------------------------
| Marker cho request đầu
|--------------------------------------------------------------------------
|
| Sau khi INSERT appointments chạy xong nhưng transaction chưa commit,
| request đầu sẽ đứng chờ file release.
|
*/

Illuminate\Support\Facades\DB::listen(
    function (
        $event
    ) use (
        $payload,
        $ready,
        $release,
        &$marked
    ): void {
        $sql =
            str_replace(
                '`',
                '',
                strtolower(
                    $event->sql
                )
            );

        $matches =
            $payload['pause']
            ===
            'appointment_insert'
            &&
            str_starts_with(
                $sql,
                'insert into appointments'
            );

        if (
            $marked
            ||
            ! $matches
        ) {
            return;
        }

        $marked =
            true;

        file_put_contents(
            $ready,
            'ready'
        );

        $deadline =
            microtime(true)
            +
            30;

        while (
            ! is_file(
                $release
            )
            &&
            microtime(true)
            <
            $deadline
        ) {
            clearstatcache(
                true,
                $release
            );

            usleep(
                10000
            );
        }

        if (
            ! is_file(
                $release
            )
        ) {
            throw new RuntimeException(
                'Timed out waiting for concurrent request.'
            );
        }
    }
);

/*
|--------------------------------------------------------------------------
| Đăng nhập đúng Patient của worker
|--------------------------------------------------------------------------
*/

$user =
    App\Models\User::query()
        ->where(
            'business_code',
            $payload['user_code']
        )
        ->firstOrFail();

Illuminate\Support\Facades\Auth::setUser(
    $user
);

/*
|--------------------------------------------------------------------------
| Tạo HTTP request
|--------------------------------------------------------------------------
*/

$request =
    Illuminate\Http\Request::create(
        $payload['uri'],
        $payload['method'],
        $payload['data'],
        [],
        [],
        [
            'HTTP_ACCEPT'
                =>
                'application/json',
        ]
    );

/*
|--------------------------------------------------------------------------
| Gửi qua Laravel HTTP Kernel
|--------------------------------------------------------------------------
*/

$kernel =
    $app->make(
        Illuminate\Contracts\Http\Kernel::class
    );

$response =
    $kernel->handle(
        $request
    );

/*
|--------------------------------------------------------------------------
| Trả kết quả cho process cha
|--------------------------------------------------------------------------
*/

echo json_encode(
    [
        'status'
            =>
            $response
                ->getStatusCode(),

        'content'
            =>
            $response
                ->getContent(),
    ],
    JSON_THROW_ON_ERROR
);

$kernel->terminate(
    $request,
    $response
);
PHP;

        /*
        |--------------------------------------------------------------------------
        | Environment của worker
        |--------------------------------------------------------------------------
        |
        | Không sử dụng DB mediway.
        |
        */

        $environment = [
            'APP_ENV' =>
            'testing',

            'DB_CONNECTION' =>
            'mysql',

            'DB_DATABASE' =>
            self::TEST_DATABASE,

            'DB_URL' =>
            '',

            'CACHE_STORE' =>
            'array',

            'SESSION_DRIVER' =>
            'array',

            'BCRYPT_ROUNDS' =>
            '4',

            'PAO_DISABLE' =>
            '1',
        ];

        /*
        |--------------------------------------------------------------------------
        | Hai process độc lập
        |--------------------------------------------------------------------------
        */

        $workers = [
            new Process(
                [
                    PHP_BINARY,
                    '-r',
                    $script,
                    json_encode(
                        $first,
                        JSON_THROW_ON_ERROR
                    ),
                    $firstReady,
                    $release,
                ],
                base_path(),
                $environment
            ),

            new Process(
                [
                    PHP_BINARY,
                    '-r',
                    $script,
                    json_encode(
                        $second,
                        JSON_THROW_ON_ERROR
                    ),
                    $secondReady,
                    $release,
                ],
                base_path(),
                $environment
            ),
        ];

        foreach (
            $workers
            as
            $worker
        ) {
            $worker->setTimeout(
                45
            );
        }

        try {
            /*
            |--------------------------------------------------------------------------
            | Bắt đầu request 1
            |--------------------------------------------------------------------------
            */

            $workers[0]
                ->start();

            $this->awaitMarker(
                $workers[0],
                $firstReady
            );

            /*
            |--------------------------------------------------------------------------
            | Request 1 đã INSERT nhưng chưa commit
            |--------------------------------------------------------------------------
            |
            | Bây giờ mới cho request 2 chạy.
            |
            */

            $workers[1]
                ->start();

            $this->awaitMarker(
                $workers[1],
                $secondReady
            );

            /*
            |--------------------------------------------------------------------------
            | Request 2 phải vẫn đang sống
            |--------------------------------------------------------------------------
            |
            | Nó đã tới vùng lock khi request 1 chưa commit.
            |
            */

            $this->assertTrue(
                $workers[1]
                    ->isRunning(),
                'Request thứ hai phải chờ request đầu giải phóng lock.'
            );

            /*
            |--------------------------------------------------------------------------
            | Cho request 1 tiếp tục và commit
            |--------------------------------------------------------------------------
            */

            file_put_contents(
                $release,
                'release'
            );

            /*
            |--------------------------------------------------------------------------
            | Chờ cả hai worker hoàn tất
            |--------------------------------------------------------------------------
            */

            foreach (
                $workers
                as
                $worker
            ) {
                $worker->wait();

                if (
                    ! $worker
                        ->isSuccessful()
                ) {
                    $this->fail(
                        "Concurrent worker failed:\n"
                            .
                            $worker
                            ->getErrorOutput()
                            .
                            "\n"
                            .
                            $worker
                            ->getOutput()
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Đọc response
            |--------------------------------------------------------------------------
            */

            $responses =
                [];

            foreach (
                $workers
                as
                $worker
            ) {
                $output =
                    trim(
                        $worker
                            ->getOutput()
                    );

                $this->assertNotSame(
                    '',
                    $output,
                    'Concurrency worker không trả response.'
                );

                $responses[] =
                    json_decode(
                        $output,
                        true,
                        512,
                        JSON_THROW_ON_ERROR
                    );
            }

            return $responses;
        } finally {
            /*
            |--------------------------------------------------------------------------
            | Xóa marker
            |--------------------------------------------------------------------------
            */

            foreach (
                [
                    $release,
                    $firstReady,
                    $secondReady,
                ]
                as
                $file
            ) {
                if (
                    File::exists(
                        $file
                    )
                ) {
                    File::delete(
                        $file
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Không để worker treo
            |--------------------------------------------------------------------------
            */

            foreach (
                $workers
                as
                $worker
            ) {
                if (
                    $worker->isRunning()
                ) {
                    $worker->stop(
                        1
                    );
                }
            }
        }
    }

    private function awaitMarker(
        Process $worker,
        string $marker
    ): void {
        $deadline =
            microtime(true)
            +
            30;

        while (
            microtime(true)
            <
            $deadline
        ) {
            clearstatcache(
                true,
                $marker
            );

            if (
                File::exists(
                    $marker
                )
            ) {
                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Worker chết trước khi tới marker
            |--------------------------------------------------------------------------
            */

            if (
                ! $worker
                    ->isRunning()
            ) {
                $this->fail(
                    "Worker stopped before reaching concurrency marker:\n"
                        .
                        $worker
                        ->getErrorOutput()
                        .
                        "\n"
                        .
                        $worker
                        ->getOutput()
                );
            }

            usleep(
                10000
            );
        }

        $this->fail(
            'Timed out waiting for concurrency marker.'
        );
    }
}
