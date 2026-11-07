<?php
declare(strict_types=1);
require_once __DIR__.'/F08V2Safety.php';

/** Registry design for V2. Never creates tables or opens/commits a transaction. */
final class F08V2Registry
{
    public static function assertExamPayable(array $payable): void
    {
        if (($payable['payment_type'] ?? null) !== 'EXAMINATION_FEE'
            || ($payable['service_order_item_id'] ?? null) !== null
            || ($payable['prescription_id'] ?? null) !== null) {
            throw new RuntimeException('Only a standalone examination-fee fixture is supported');
        }
    }

    public static function validateRows(string $runId, int $payableId, mixed $run, mixed $fixture, string $entityType = 'payable'): void
    {
        if ($payableId <= 0 || !is_array($run) || !is_array($fixture)
            || ($run['status'] ?? null) !== 'ACTIVE'
            || ($run['source'] ?? null) !== 'f08_v2_synthetic'
            || ($fixture['entity_type'] ?? null) !== $entityType
            || (string) ($fixture['entity_id'] ?? '') !== (string) $payableId
            || ($fixture['source'] ?? null) !== 'f08_v2_synthetic') {
            throw new RuntimeException('Unrecognized fixture registry entry');
        }
        F08V2Safety::assertOwner($runId, $run['run_id'] ?? null);
        F08V2Safety::assertOwner($runId, $fixture['run_id'] ?? null);
    }

    /** Locks registry rows until the caller's transaction completes. */
    public static function lockPayable(PDO $pdo, string $runId, int $payableId): void
    {
        self::lockEntity($pdo, $runId, 'payable', $payableId);
    }

    public static function lockEntity(PDO $pdo, string $runId, string $entityType, int $entityId): void
    {
        if (!$pdo->inTransaction() || $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'mysql') {
            throw new RuntimeException('MySQL transaction is required');
        }
        if (!preg_match('/^[a-f0-9]{32}$/D', $runId) || $entityId <= 0 || !in_array($entityType, ['payable', 'employee', 'user', 'visit', 'patient', 'appointment'], true)) {
            throw new InvalidArgumentException('Invalid run or payable');
        }
        $runQuery = $pdo->prepare('SELECT run_id, status, source FROM acceptance_runs WHERE run_id = ? FOR SHARE');
        $runQuery->execute([$runId]);
        $fixtureQuery = $pdo->prepare('SELECT run_id, entity_type, entity_id, source FROM acceptance_fixtures WHERE entity_type = ? AND entity_id = ? FOR SHARE');
        $fixtureQuery->execute([$entityType, $entityId]);
        self::validateRows($runId, $entityId, $runQuery->fetch(PDO::FETCH_ASSOC), $fixtureQuery->fetch(PDO::FETCH_ASSOC), $entityType);
    }
}
