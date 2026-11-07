<?php
// Owner-run preflight only; never prints grants, usernames, passwords or database errors.
try {
    require __DIR__.'/F08MysqlGrantParser.php';
    require __DIR__.'/../../vendor/autoload.php';
    $app = require __DIR__.'/../../bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    $db = Illuminate\Support\Facades\DB::connection();
    $expected = 'mediway_f08_isolated_20261105_5978f4ba';
    if (getenv('F08_REQUIRE_EMPTY_DB_PASSWORD') === '1' && (string) $db->getConfig('password') !== '') {
        throw new RuntimeException('Empty test password requested but Laravel resolves a non-empty password');
    }
    if ($db->getDriverName() !== 'mysql' || $db->getDatabaseName() !== $expected
        || $db->getConfig('host') !== '127.0.0.1' || (int) $db->getConfig('port') !== 3306
        || $db->getConfig('unix_socket')) throw new RuntimeException('Unsafe connection');
    $pdo = $db->getPdo();
    if ($pdo->query('SELECT DATABASE()')->fetchColumn() !== $expected) throw new RuntimeException('Wrong database');
    $authenticated = $pdo->query('SELECT CURRENT_USER()')->fetchColumn();
    if (!F08MysqlGrantParser::acceptsIdentity((string) $db->getConfig('username'), $authenticated)) throw new RuntimeException('Root or unrecognized account is not allowed');
    if (strcasecmp((string) $db->getConfig('username'), 'f08_runner') !== 0
        || strcasecmp(explode('@', $authenticated, 2)[0], 'f08_runner') !== 0) {
        throw new RuntimeException('Connection must authenticate as the designated test account');
    }
    $role = $pdo->query('SELECT CURRENT_ROLE()')->fetchColumn();
    if ($role !== null && strtoupper((string) $role) !== 'NONE') throw new RuntimeException('Active role cannot be audited conservatively');
    $grants = $pdo->query('SHOW GRANTS')->fetchAll(PDO::FETCH_COLUMN);
    $partialValue = $pdo->query('SELECT @@GLOBAL.partial_revokes')->fetchColumn();
    if (!in_array((string) $partialValue, ['0', '1'], true)) throw new RuntimeException('Unknown partial_revokes state');
    $partialRevokes = (string) $partialValue === '1';
    if (!F08MysqlGrantParser::accepts($grants, $expected, $partialRevokes)) throw new RuntimeException('Unsupported or unsafe grants');
    echo "ISOLATED_ACCOUNT_SCOPE_PASS\n";
} catch (Throwable $e) {
    fwrite(STDERR, "BLOCKED: connection, active roles or account scope could not be safely verified.\n");
    exit(1);
}
