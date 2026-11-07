<?php
declare(strict_types=1);

/** Pure inspection of already-read metadata; never mutates a database. */
final class F08V2Inventory
{
    public static function validateMigrationSpecialty(array $rows): void
    {
        if (count($rows)!==1) throw new RuntimeException('Unexpected migration specialty count');
        $row=$rows[0];
        if (($row['specialty_code'] ?? null)!=='CKDK' || ($row['name'] ?? null)!=='Đa khoa'
            || ($row['status'] ?? null)!=='ACTIVE' || (int)($row['is_general'] ?? 0)!==1) throw new RuntimeException('Unexpected migration specialty');
        foreach (['description','image_path','pre_visit_instructions','notes'] as $column) {
            if (!array_key_exists($column,$row) || $row[$column]!==null) throw new RuntimeException('Unexpected specialty data');
        }
    }

    public static function validate(array $tables, array $expected, array $employeeTypes, array $migrations, array $expectedMigrations, array $migrationSpecialties): void
    {
        if (!$expected || count($expected)!==count(array_unique($expected))
            || array_diff(['migrations','employee_types','acceptance_runs','acceptance_fixtures','specialties'], $expected)) throw new RuntimeException('Invalid approved table manifest');
        $actual=[];
        foreach ($tables as $table) {
            $name=$table['name'] ?? null;
            if (!is_string($name) || !preg_match('/^[a-z0-9_]+$/D',$name) || isset($actual[$name])
                || ($table['type'] ?? null)!=='BASE TABLE' || strtoupper((string)($table['engine'] ?? ''))!=='INNODB'
                || !isset($table['count']) || !is_int($table['count']) || $table['count']<0) throw new RuntimeException('Unsafe table metadata');
            if (!in_array($name,['migrations','employee_types','specialties'],true) && $table['count']!==0) throw new RuntimeException('Unexpected data');
            $actual[$name]=$table['count'];
        }
        $names=array_keys($actual); sort($names); sort($expected);
        if ($names!==$expected) throw new RuntimeException('Schema differs from approved manifest');
        sort($employeeTypes);
        if ($employeeTypes!==['CASHIER','IMAGING_TECHNICIAN','LAB_TECHNICIAN','NURSE','RECEPTIONIST']) throw new RuntimeException('Unexpected employee type catalog');
        if ($actual['employee_types']!==count($employeeTypes) || $actual['migrations']!==count($migrations)) throw new RuntimeException('Inconsistent inventory counts');
        if ($actual['specialties']!==count($migrationSpecialties)) throw new RuntimeException('Inconsistent specialty inventory');
        self::validateMigrationSpecialty($migrationSpecialties);
        sort($migrations); sort($expectedMigrations);
        if (!$expectedMigrations || $migrations!==$expectedMigrations) throw new RuntimeException('Migration history differs from reviewed source');
    }
}
