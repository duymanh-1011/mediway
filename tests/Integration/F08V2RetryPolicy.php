<?php
declare(strict_types=1);

/** Proposed outer retry policy only; not connected to the disabled worker. */
final class F08V2RetryPolicy
{
    public static function mayRetry(string $phase, int $mysqlCode, string $sqlState, int $attempt, int $maxAttempts, bool $rollbackConfirmed): bool
    {
        if ($phase!=='before_commit' || !$rollbackConfirmed || $attempt<1 || $maxAttempts<1 || $maxAttempts>3 || $attempt >= $maxAttempts) return false;
        return ($mysqlCode===1213 && $sqlState==='40001') || ($mysqlCode===1205 && $sqlState==='HY000');
    }
}
