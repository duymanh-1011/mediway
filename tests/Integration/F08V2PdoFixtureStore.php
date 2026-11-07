<?php
declare(strict_types=1);
require_once __DIR__.'/F08V2SyntheticFixture.php';
require_once __DIR__.'/F08MysqlGrantParser.php';
require_once __DIR__.'/F08V2Inventory.php';

/** One-connection store; not invoked by a runner or CLI. */
final class F08V2PdoFixtureStore implements F08V2FixtureStore
{
    private const TABLES = ['acceptance_runs','roles','specialties','examination_rooms','users','employees','employee_details',
        'patients','doctors','appointment_statuses','visit_statuses','queue_statuses','payment_statuses','doctor_schedules',
        'appointment_time_slots','appointments','medical_visits','payables','payment_transactions','audit_logs'];
    private array $config;
    public function __construct(private PDO $pdo, array $env)
    {
        $this->config = F08V2Safety::validateConnection($env);
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'mysql' || $pdo->inTransaction()
            || !preg_match('/^127\.0\.0\.1 via TCP\/IP$/iD', (string) $pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS))
            || (int) $pdo->query('SELECT @@port')->fetchColumn() !== 3306
            || $pdo->query('SELECT DATABASE()')->fetchColumn() !== $this->config['schema']
            || $pdo->query('SELECT CURRENT_USER()')->fetchColumn() !== 'f08_runner_v2@localhost') throw new RuntimeException('Unsafe fixture connection');
        $role = $pdo->query('SELECT CURRENT_ROLE()')->fetchColumn();
        $partial = $pdo->query('SELECT @@GLOBAL.partial_revokes')->fetchColumn();
        if (!in_array($role, [null,'NONE'], true) || !in_array((string) $partial, ['0','1'], true)
            || !F08MysqlGrantParser::accepts($pdo->query('SHOW GRANTS')->fetchAll(PDO::FETCH_COLUMN), $this->config['schema'], (string) $partial === '1')) {
            throw new RuntimeException('Unsafe fixture privileges');
        }
    }
    public function transaction(callable $work): mixed
    {
        if ($this->pdo->inTransaction()) throw new RuntimeException('Unexpected outer transaction');
        $this->pdo->beginTransaction();
        try {
            // Guard against reusing a populated schema or legacy data. The employee type
            // catalog is inserted by the application's migration and is reference data.
            $tablesQuery = $this->pdo->prepare("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = 'BASE TABLE'");
            $tablesQuery->execute([$this->config['schema']]);
            $tables = $tablesQuery->fetchAll(PDO::FETCH_COLUMN);
            foreach (array_merge(self::TABLES, ['acceptance_fixtures', 'employee_types', 'migrations']) as $required) {
                if (!in_array($required, $tables, true)) throw new RuntimeException('Required fixture table is missing');
            }
            foreach ($tables as $table) {
                if (!is_string($table) || !preg_match('/^[a-z0-9_]+$/D', $table)) throw new RuntimeException('Unknown table identifier');
                if (in_array($table, ['migrations','employee_types','specialties'], true)) continue;
                if ((int) $this->pdo->query('SELECT COUNT(*) FROM `'.$table.'`')->fetchColumn() !== 0) throw new RuntimeException('Fixture schema is not empty');
            }
            $type = $this->pdo->prepare('SELECT COUNT(*) FROM employee_types WHERE code = ?');
            $type->execute(['CASHIER']);
            if ((int) $type->fetchColumn() !== 1) throw new RuntimeException('Missing CASHIER reference type');
            $specialties=$this->pdo->query('SELECT specialty_code,name,status,is_general,description,image_path,pre_visit_instructions,notes FROM specialties')->fetchAll(PDO::FETCH_ASSOC);
            F08V2Inventory::validateMigrationSpecialty($specialties);
            $result = $work();
            $this->pdo->commit();
            return $result;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }
    public function insert(string $table, array $values): int
    {
        if (!$this->pdo->inTransaction() || !in_array($table, self::TABLES, true) || !$values) throw new RuntimeException('Invalid fixture write');
        foreach (array_keys($values) as $key) if (!preg_match('/^[a-z_]+$/D', $key)) throw new RuntimeException('Invalid column');
        $columns = array_map(static fn ($key) => '`'.$key.'`', array_keys($values));
        $sql = 'INSERT INTO `'.$table.'` ('.implode(',', $columns).') VALUES ('.implode(',', array_fill(0, count($values), '?')).')';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($values));
        return $table === 'acceptance_runs' ? 0 : (int) $this->pdo->lastInsertId();
    }
    public function register(string $run, string $type, int $id, string $time): void
    {
        if (!$this->pdo->inTransaction() || $run !== $this->config['run_id'] || !preg_match('/^[a-z_]+$/D', $type) || $id <= 0) throw new RuntimeException('Invalid fixture ownership');
        $stmt = $this->pdo->prepare('INSERT INTO acceptance_fixtures (run_id,entity_type,entity_id,source,created_at) VALUES (?,?,?,?,?)');
        $stmt->execute([$run,$type,$id,'f08_v2_synthetic',$time]);
    }
}
