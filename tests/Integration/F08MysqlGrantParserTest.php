<?php
declare(strict_types=1);
require __DIR__.'/F08MysqlGrantParser.php';
$schema = 'mediway_f08_isolated_20261105_5978f4ba';
$escaped = str_replace('_', '\\_', $schema);
$usage = 'GRANT USAGE ON *.* TO `runner`@`localhost`';
$grant = static fn(string $name): string => 'GRANT SELECT, INSERT, UPDATE ON `'.$name.'`.* TO `runner`@`localhost`';
$cases = [
    'escaped schema' => [[$usage, $grant($escaped)], false, true],
    'unescaped schema, literal mode' => [[$usage, $grant($schema)], true, true],
    'unescaped wildcard unsafe' => [[$usage, $grant($schema)], false, false],
    'global privilege' => [[$usage, 'GRANT SELECT ON *.* TO `runner`@`localhost`', $grant($escaped)], false, false],
    'other schema' => [[$usage, $grant('mediway'), $grant($escaped)], false, false],
    'grant option' => [[$usage, $grant($escaped).' WITH GRANT OPTION'], false, false],
    'role' => [[$usage, 'GRANT `manager`@`%` TO `runner`@`localhost`', $grant($escaped)], false, false],
    'unknown grant' => [[$usage, 'GRANT SOMETHING STRANGE', $grant($escaped)], false, false],
    'partial escaping' => [[$usage, $grant('mediway\\_f08_isolated_20261105_5978f4ba')], false, false],
    'wildcard percent' => [[$usage, $grant('mediway%')], true, false],
    'unknown privilege' => [[$usage, 'GRANT CREATE USER ON `'.$escaped.'`.* TO `runner`@`localhost`'], false, false],
    'no schema grant' => [[$usage], false, false],
];
foreach ($cases as $name => [$grants, $partial, $expected]) {
    if (F08MysqlGrantParser::accepts($grants, $schema, $partial) !== $expected) {
        fwrite(STDERR, "FAIL: {$name}\n"); exit(1);
    }
    echo "PASS: {$name}\n";
}
foreach ([['runner','runner@localhost',true], ['root','runner@localhost',false], ['runner','root@localhost',false], ['runner','',false]] as [$configured, $authenticated, $expected]) {
    if (F08MysqlGrantParser::acceptsIdentity($configured, $authenticated) !== $expected) { fwrite(STDERR, "FAIL: identity\n"); exit(1); }
}
echo "PASS: 4 identity cases\n";
