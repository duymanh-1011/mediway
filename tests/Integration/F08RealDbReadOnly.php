<?php
// Owner-run aggregate audit only. Does not bootstrap Laravel, open HTTP, or output patient identifiers.
declare(strict_types=1);

try {
    $root = dirname(__DIR__, 2);
    if (is_file($root.'/bootstrap/cache/config.php')) throw new RuntimeException('Cached Laravel configuration requires separate review.');
    require $root.'/vendor/autoload.php';
    if (!is_file($root.'/.env')) throw new RuntimeException('Project .env is missing.');
    Dotenv\Dotenv::createImmutable($root)->safeLoad();
    $get = static fn(string $key, string $default = ''): string => (string) ($_ENV[$key] ?? $_SERVER[$key] ?? getenv($key) ?: $default);
    $host = $get('DB_HOST', '127.0.0.1');
    $port = $get('DB_PORT', '3306');
    $database = $get('DB_DATABASE');
    $user = getenv('F08_READONLY_DB_USERNAME');
    $password = getenv('F08_READONLY_DB_PASSWORD');
    if ($get('DB_CONNECTION') !== 'mysql' || $get('DB_URL') !== '' || $get('DB_SOCKET') !== '') throw new RuntimeException('Only direct MySQL host/port configuration is supported.');
    if (!preg_match('/^[a-zA-Z0-9_.-]+$/D', $host) || !ctype_digit($port) || (int) $port < 1 || (int) $port > 65535 || !preg_match('/^[a-zA-Z0-9_$-]+$/D', $database)) throw new RuntimeException('Unsupported database host, port or name.');
    if ($user === false || $user === '' || $password === false) throw new RuntimeException('Separate read-only MySQL credentials are required.');
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4", $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_TIMEOUT => 5]);
    // Conservatively reject roles, write privileges, and grant option. Do not print grants or account names.
    $role = $pdo->query('SELECT CURRENT_ROLE()')->fetchColumn();
    if ($role !== null && strtoupper((string) $role) !== 'NONE') throw new RuntimeException('Active MySQL roles require separate privilege review.');
    $grants = $pdo->query('SHOW GRANTS')->fetchAll(PDO::FETCH_COLUMN);
    if (!$grants) throw new RuntimeException('Could not verify MySQL privileges.');
    foreach ($grants as $grant) {
        if (!preg_match('/^GRANT (?:USAGE|SELECT) ON /i', (string) $grant) || stripos((string) $grant, 'WITH GRANT OPTION') !== false) throw new RuntimeException('MySQL account has privileges beyond SELECT/USAGE or requires review.');
    }
    $pdo->exec('SET SESSION TRANSACTION READ ONLY');
    if ((int) $pdo->query('SELECT @@session.transaction_read_only')->fetchColumn() !== 1) throw new RuntimeException('Server did not enforce a read-only transaction.');
    $pdo->beginTransaction();
    $sql = [
        'payable_total' => 'SELECT COUNT(*) FROM payables',
        'service_fee_missing_item' => "SELECT COUNT(*) FROM payables WHERE payment_type = 'SERVICE_FEE' AND service_order_item_id IS NULL",
        'service_fee_wrong_visit_or_missing_item' => "SELECT COUNT(*) FROM payables p LEFT JOIN service_order_items i ON i.id=p.service_order_item_id LEFT JOIN service_orders o ON o.id=i.service_order_id WHERE p.payment_type='SERVICE_FEE' AND p.service_order_item_id IS NOT NULL AND (i.id IS NULL OR o.id IS NULL OR o.medical_visit_id<>p.medical_visit_id)",
        'service_fee_amount_differs_from_item' => "SELECT COUNT(*) FROM payables p JOIN service_order_items i ON i.id=p.service_order_item_id WHERE p.payment_type='SERVICE_FEE' AND p.amount<>i.price_at_order",
        'success_transactions_orphaned' => "SELECT COUNT(*) FROM payment_transactions t LEFT JOIN payables p ON p.id=t.payable_id WHERE t.transaction_result='SUCCESS' AND p.id IS NULL",
        'multiple_success_payables' => "SELECT COUNT(*) FROM (SELECT payable_id FROM payment_transactions WHERE transaction_result='SUCCESS' GROUP BY payable_id HAVING COUNT(*)>1) x",
        'success_total_differs_from_payable' => "SELECT COUNT(*) FROM payables p JOIN (SELECT payable_id,SUM(amount) total FROM payment_transactions WHERE transaction_result='SUCCESS' GROUP BY payable_id) t ON t.payable_id=p.id WHERE t.total<>p.amount",
        'paid_status_without_full_success_total' => "SELECT COUNT(*) FROM payables p JOIN payment_statuses s ON s.id=p.payment_status_id LEFT JOIN (SELECT payable_id,SUM(amount) total FROM payment_transactions WHERE transaction_result='SUCCESS' GROUP BY payable_id) t ON t.payable_id=p.id WHERE s.name='PAID' AND (t.total IS NULL OR t.total<>p.amount)",
        'cash_success_exam' => "SELECT COUNT(*) FROM payment_transactions t JOIN payables p ON p.id=t.payable_id WHERE t.transaction_result='SUCCESS' AND t.payment_method='CASH' AND p.payment_type='EXAMINATION_FEE'",
        'cash_success_service' => "SELECT COUNT(*) FROM payment_transactions t JOIN payables p ON p.id=t.payable_id WHERE t.transaction_result='SUCCESS' AND t.payment_method='CASH' AND p.payment_type='SERVICE_FEE'",
        'cash_success_medicine' => "SELECT COUNT(*) FROM payment_transactions t JOIN payables p ON p.id=t.payable_id WHERE t.transaction_result='SUCCESS' AND t.payment_method='CASH' AND p.payment_type='MEDICINE_FEE'",
    ];
    $counts = [];
    foreach ($sql as $name => $query) {
        if (!str_starts_with($query, 'SELECT ') || str_contains($query, ';')) throw new RuntimeException('Query is outside the read-only allowlist.');
        $counts[$name] = (int) $pdo->query($query)->fetchColumn();
    }
    $pdo->rollBack();
    echo json_encode(['status' => 'PASS', 'connection' => 'mysql', 'host' => $host, 'port' => (int) $port, 'database' => $database, 'counts' => $counts,
        'meaning' => 'Aggregate signals only; mismatches are not proof of error or receipt eligibility. No identifiers returned.'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), "\n";
} catch (Throwable $e) {
    // Do not print exception messages: connection errors can include sensitive configuration.
    fwrite(STDERR, "BLOCKED: read-only preflight or aggregate query failed; no sensitive error details are logged. Review connection, privileges and schema privately.\n");
    exit(1);
}
