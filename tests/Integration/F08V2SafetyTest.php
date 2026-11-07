<?php
declare(strict_types=1);
require __DIR__.'/F08V2Safety.php';
$schema = 'f08_acceptance_0123456789abcdef';
$run = '0123456789abcdef0123456789abcdef';
$base = ['F08_V2_SCHEMA' => $schema, 'F08_V2_APPROVED_SCHEMA' => $schema, 'F08_V2_RUN_ID' => $run];
$cases = [
    'valid approved' => [$base, true],
    'production' => [array_replace($base, ['F08_V2_SCHEMA' => 'mediway', 'F08_V2_APPROVED_SCHEMA' => 'mediway']), false],
    'old schema' => [array_replace($base, ['F08_V2_SCHEMA' => 'mediway_f08_isolated_20261105_5978f4ba', 'F08_V2_APPROVED_SCHEMA' => 'mediway_f08_isolated_20261105_5978f4ba']), false],
    'approval mismatch' => [array_replace($base, ['F08_V2_APPROVED_SCHEMA' => 'f08_acceptance_ffffffffffffffff']), false],
    'missing approval' => [array_diff_key($base, ['F08_V2_APPROVED_SCHEMA' => 1]), false],
    'sql metacharacters' => [array_replace($base, ['F08_V2_SCHEMA' => $schema.';DROP', 'F08_V2_APPROVED_SCHEMA' => $schema.';DROP']), false],
    'bad run id' => [array_replace($base, ['F08_V2_RUN_ID' => '123']), false],
];
foreach ($cases as $name => [$env, $expected]) {
    try { F08V2Safety::validateConfig($env); $actual = true; } catch (InvalidArgumentException) { $actual = false; }
    if ($actual !== $expected) { fwrite(STDERR, "FAIL: {$name}\n"); exit(1); }
    echo "PASS: {$name}\n";
}
foreach ([[$run, $run, true], [$run, str_repeat('f', 32), false], [$run, null, false], ['bad', 'bad', false]] as [$a, $b, $expected]) {
    if (F08V2Safety::owns($a, $b) !== $expected) { fwrite(STDERR, "FAIL: ownership\n"); exit(1); }
}
try { F08V2Safety::assertOwner($run, str_repeat('f', 32)); fwrite(STDERR, "FAIL: assertOwner\n"); exit(1); }
catch (RuntimeException) { echo "PASS: ownership checks and rejection\n"; }
