<?php
declare(strict_types=1);

/** Conservative policy for future separate worker account, not wired to a runner. */
final class F08V2WorkerPrivileges
{
    private const WRITE_TABLES = ['medical_visits','employees','employee_details','service_order_items',
        'payables','appointments','examination_rooms','payment_transactions','examination_room_queues','audit_logs'];

    public static function accepts(array $grants, string $schema, bool $partialRevokes): bool
    {
        if (!preg_match('/^f08_acceptance_[a-f0-9]{16}$/D', $schema) || !$grants) return false;
        $expected = $partialRevokes ? $schema : str_replace('_', '\\_', $schema);
        $account = '`f08_worker_v2`@`localhost`';
        $hasRead = false;
        foreach ($grants as $grant) {
            if (!is_string($grant)) return false;
            if ($grant === 'GRANT USAGE ON *.* TO '.$account) continue;
            if (!preg_match('/^GRANT ([A-Z, ]+) ON `([^`]+)`\.(\*|`[a-z_]+`) TO `f08_worker_v2`@`localhost`$/D', $grant, $m)) return false;
            if ($m[2] !== $expected) return false;
            $privileges = explode(', ', $m[1]);
            if (!$privileges || count($privileges) !== count(array_unique($privileges))) return false;
            foreach ($privileges as $privilege) if (!in_array($privilege, ['SELECT','INSERT','UPDATE'], true)) return false;
            if ($m[3] === '*') {
                if ($privileges !== ['SELECT']) return false;
                $hasRead = true;
                continue;
            }
            $table = trim($m[3], '`');
            if (!in_array($table, self::WRITE_TABLES, true)) return false;
        }
        return $hasRead;
    }
}
