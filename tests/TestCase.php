<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

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
}
