<?php
declare(strict_types=1);
require_once __DIR__.'/F08V2Safety.php';
require_once __DIR__.'/F08V2WorkerPrivileges.php';

/** Read-only authorization gate; the caller must lock/revalidate ownership within its write transaction. */
final class F08V2WorkerGate
{
    public static function authorize(array $env, int $payableId, callable $read): void
    {
        $config = F08V2Safety::validateConnection($env, 'f08_worker_v2');
        if ($payableId <= 0) throw new InvalidArgumentException('Invalid payable ID');
        $state = $read('connection', null);
        if (!is_array($state) || !array_key_exists('role', $state) || ($state['database'] ?? null) !== $config['schema']
            || ($state['user'] ?? null) !== 'f08_worker_v2@localhost'
            || !in_array($state['role'] ?? null, [null, 'NONE'], true)
            || !in_array($state['partial_revokes'] ?? null, [0, 1], true)
            || !F08V2WorkerPrivileges::accepts($state['grants'] ?? [], $config['schema'], ($state['partial_revokes'] ?? null) === 1)) {
            throw new RuntimeException('Unsafe database identity or privileges');
        }
        // Must come from a protected run-to-payable registry in the same database,
        // not from a CLI argument or a guessed business-code prefix.
        $owner = $read('payable_owner', $payableId);
        F08V2Safety::assertOwner($config['run_id'], $owner);
    }
}
