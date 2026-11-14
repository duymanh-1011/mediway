<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use App\Models\User;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = parent::createApplication();
        $connection = $app['db']->connection();
        $safe = ($connection->getDriverName() === 'sqlite' && $connection->getDatabaseName() === ':memory:')
            || ($connection->getDriverName() === 'mysql' && $connection->getDatabaseName() === 'mediway_codex_test_20261019');
        if (! $safe) {
            throw new \RuntimeException('Dừng kiểm thử: chỉ được phép dùng SQLite :memory: hoặc database mediway_codex_test_20261019, trước khi chạy RefreshDatabase.');
        }

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        if (DB::getDriverName() === 'sqlite') {
            DB::connection()->getPdo()->sqliteCreateFunction(
                'regexp', fn (string $pattern, ?string $value): int => preg_match('~'.str_replace('~', '\\~', $pattern).'~u', $value ?? '') ?: 0, 2
            );
        }
    }

    protected function actingAsEmployeeWorkspace(User $user, string $workspace): static
    {
        $this->withSession(['employee_workspace_mode' => $workspace]);

        return $this->actingAs($user);
    }

    protected function actingAsDoctorWorkspace(User $user, string $workspace = 'examination'): static
    {
        $this->withSession(['doctor_workspace_mode' => $workspace]);

        return $this->actingAs($user);
    }
}
