<?php
declare(strict_types=1);

/** Pure validation only. No connection, schema creation, migration or fixture writes. */
final class F08V2Safety
{
    public static function validateConfig(array $env): array
    {
        $schema = $env['F08_V2_SCHEMA'] ?? null;
        $approved = $env['F08_V2_APPROVED_SCHEMA'] ?? null;
        $run = $env['F08_V2_RUN_ID'] ?? null;
        if (!is_string($schema) || !preg_match('/^f08_acceptance_[a-f0-9]{16}$/D', $schema)
            || !is_string($approved) || !hash_equals($schema, $approved)
            || !is_string($run) || !preg_match('/^[a-f0-9]{32}$/D', $run)) {
            throw new InvalidArgumentException('Unsafe or unapproved F08 V2 configuration');
        }
        return ['schema' => $schema, 'run_id' => $run];
    }

    public static function owns(string $run, mixed $owner): bool
    {
        return preg_match('/^[a-f0-9]{32}$/D', $run) === 1
            && is_string($owner) && preg_match('/^[a-f0-9]{32}$/D', $owner) === 1
            && hash_equals($run, $owner);
    }

    public static function assertOwner(string $run, mixed $owner): void
    {
        if (!self::owns($run, $owner)) throw new RuntimeException('Fixture does not belong to this run');
    }

    public static function validateConnection(array $env, string $account = 'f08_runner_v2'): array
    {
        $config = self::validateConfig($env);
        if (!in_array($account, ['f08_runner_v2','f08_worker_v2'], true)
            || ($env['DB_CONNECTION'] ?? null) !== 'mysql'
            || ($env['DB_HOST'] ?? null) !== '127.0.0.1'
            || (string) ($env['DB_PORT'] ?? '') !== '3306'
            || ($env['DB_DATABASE'] ?? null) !== $config['schema']
            || ($env['DB_USERNAME'] ?? null) !== $account
            || ($env['F08_V2_DB_USERNAME'] ?? null) !== $account
            || !array_key_exists('DB_PASSWORD', $env)
            || !array_key_exists('F08_V2_DB_PASSWORD', $env)
            || !is_string($env['DB_PASSWORD']) || !is_string($env['F08_V2_DB_PASSWORD'])
            || !hash_equals($env['F08_V2_DB_PASSWORD'], $env['DB_PASSWORD'])
            || !in_array($env['DB_URL'] ?? '', ['', null], true)
            || !in_array($env['DB_SOCKET'] ?? '', ['', null], true)) {
            throw new InvalidArgumentException('Unsafe or conflicting database configuration');
        }
        return $config;
    }
}
