<?php
declare(strict_types=1);
require_once __DIR__.'/F08V2Safety.php';
require_once __DIR__.'/F08MysqlGrantParser.php';
require_once __DIR__.'/F08V2Inventory.php';

/** Read-only building block; caller must independently review manifest and owner approval. */
final class F08V2ReadOnlyPreflight
{
    public static function inspect(PDO $pdo, array $env, array $approvedTables, array $reviewedMigrations, array $approvedForeignKeys, ?callable $stage = null): void
    {
        if ($stage !== null) $stage('CONNECTION_IDENTITY');
        $config=F08V2Safety::validateConnection($env);
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME)!=='mysql' || $pdo->inTransaction()
            || !preg_match('/^127\.0\.0\.1 via TCP\/IP$/iD',(string)$pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS))
            || (int)$pdo->query('SELECT @@port')->fetchColumn()!==3306
            || $pdo->query('SELECT DATABASE()')->fetchColumn()!==$config['schema']
            || $pdo->query('SELECT CURRENT_USER()')->fetchColumn()!=='f08_runner_v2@localhost') throw new RuntimeException('Unsafe MySQL identity');
        if ($stage !== null) $stage('ROLE_AND_GRANTS');
        $role=$pdo->query('SELECT CURRENT_ROLE()')->fetchColumn();
        $partial=$pdo->query('SELECT @@GLOBAL.partial_revokes')->fetchColumn();
        if (!in_array($role,[null,'NONE'],true) || !in_array((string)$partial,['0','1'],true)
            || !F08MysqlGrantParser::accepts($pdo->query('SHOW GRANTS')->fetchAll(PDO::FETCH_COLUMN),$config['schema'],(string)$partial==='1')) throw new RuntimeException('Unsafe grants');
        if ($stage !== null) $stage('SCHEMA_INVENTORY_ENGINE_MIGRATIONS');
        $q=$pdo->prepare('SELECT TABLE_NAME,TABLE_TYPE,ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = ?');
        $q->execute([$config['schema']]);
        $tables=[];
        foreach($q->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $name=$row['TABLE_NAME'] ?? null;
            // Validate against a reviewed allowlist before using an identifier in SQL.
            if (!is_string($name) || !preg_match('/^[a-z0-9_]+$/D',$name) || !in_array($name,$approvedTables,true)) throw new RuntimeException('Unexpected object');
            $tables[]=['name'=>$name,'type'=>$row['TABLE_TYPE'] ?? null,'engine'=>$row['ENGINE'] ?? null,
                'count'=>(int)$pdo->query('SELECT COUNT(*) FROM `'.$name.'`')->fetchColumn()];
        }
        $types=$pdo->query('SELECT code FROM employee_types')->fetchAll(PDO::FETCH_COLUMN);
        $migrations=$pdo->query('SELECT migration FROM migrations')->fetchAll(PDO::FETCH_COLUMN);
        $specialties=$pdo->query('SELECT specialty_code,name,status,is_general,description,image_path,pre_visit_instructions,notes FROM specialties')->fetchAll(PDO::FETCH_ASSOC);
        F08V2Inventory::validate($tables,$approvedTables,$types,$migrations,$reviewedMigrations,$specialties);
        if ($stage !== null) $stage('FOREIGN_KEYS');
        $fk=$pdo->prepare('SELECT TABLE_SCHEMA,TABLE_NAME,COLUMN_NAME,REFERENCED_TABLE_SCHEMA,REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND REFERENCED_TABLE_NAME IS NOT NULL');
        $fk->execute([$config['schema']]);
        $actual=[];
        foreach ($fk->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (($row['TABLE_SCHEMA'] ?? null)!==$config['schema'] || ($row['REFERENCED_TABLE_SCHEMA'] ?? null)!==$config['schema']) throw new RuntimeException('Cross-schema foreign key');
            $actual[]=($row['TABLE_NAME'] ?? '').'.'.($row['COLUMN_NAME'] ?? '').'>'.($row['REFERENCED_TABLE_NAME'] ?? '').'.'.($row['REFERENCED_COLUMN_NAME'] ?? '');
        }
        sort($actual); sort($approvedForeignKeys);
        if ($actual!==$approvedForeignKeys) throw new RuntimeException('Foreign keys differ from approved manifest');
        if ($stage !== null) $stage('INDIRECT_WRITERS_VISIBILITY');
        // A zero count is not proof of absence: MySQL filters these views by account
        // privileges. This inspection must be repeated by a separately reviewed
        // metadata account before the provisioning package can be accepted.
        foreach (['TRIGGERS'=>'TRIGGER_SCHEMA','ROUTINES'=>'ROUTINE_SCHEMA','EVENTS'=>'EVENT_SCHEMA'] as $table=>$column) {
            $objects=$pdo->prepare('SELECT COUNT(*) FROM information_schema.'.$table.' WHERE '.$column.' = ?');
            $objects->execute([$config['schema']]);
            if ((int)$objects->fetchColumn()!==0) throw new RuntimeException('Unexpected indirect writer');
        }
        throw new RuntimeException('Metadata visibility and concurrent writer exclusion are not yet proven');
    }
}
