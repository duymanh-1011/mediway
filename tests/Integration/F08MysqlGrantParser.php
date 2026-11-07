<?php
declare(strict_types=1);

final class F08MysqlGrantParser
{
    public static function acceptsIdentity(string $configured, mixed $authenticated): bool
    {
        if (!is_string($authenticated) || !preg_match('/^[A-Za-z0-9_.-]+@[A-Za-z0-9_.%:-]+$/D', $authenticated)) return false;
        return strcasecmp($configured, 'root') !== 0 && strcasecmp(explode('@', $authenticated, 2)[0], 'root') !== 0;
    }

    public static function accepts(array $grants, string $expected, bool $partialRevokes): bool
    {
        if (!$grants || !preg_match('/^[a-z0-9_]+$/D', $expected)) return false;
        $account = "(?:`[A-Za-z0-9_.%:-]+`|'[A-Za-z0-9_.%:-]+')@(?:`[A-Za-z0-9_.%:-]+`|'[A-Za-z0-9_.%:-]+')";
        $schemaGrant = false;
        foreach ($grants as $grant) {
            if (!is_string($grant)) return false;
            if (preg_match('/^GRANT USAGE ON \*\.\* TO '.$account.'$/iD', $grant)) continue;
            if (!preg_match('/^GRANT (SELECT|INSERT|UPDATE)(?:, (SELECT|INSERT|UPDATE))?(?:, (SELECT|INSERT|UPDATE))? ON `([^`]+)`\.\* TO '.$account.'$/iD', $grant, $m)) return false;
            $schema = $m[4];
            // A bare underscore is a wildcard in MySQL 8.0 unless partial_revokes is ON.
            $decoded = '';
            for ($i = 0; $i < strlen($schema); $i++) {
                if ($schema[$i] === '\\') {
                    if (++$i >= strlen($schema) || $schema[$i] !== '_') return false;
                    $decoded .= '_';
                } elseif ($schema[$i] === '_') {
                    if (!$partialRevokes) return false;
                    $decoded .= '_';
                } elseif (ctype_alnum($schema[$i])) {
                    $decoded .= $schema[$i];
                } else {
                    return false;
                }
            }
            if ($decoded !== $expected) return false;
            $schemaGrant = true;
        }
        return $schemaGrant;
    }
}
