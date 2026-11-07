<?php

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$db = Illuminate\Support\Facades\DB::connection();
if ($db->getDriverName() !== 'mysql' || $db->getDatabaseName() !== 'mediway_f08_isolated_20261105_5978f4ba') throw new RuntimeException('Unsafe database');
[$script, $appointment, $marker] = $argv;
$db->beginTransaction();
try {
    $db->table('appointments')->where('id', (int) $appointment)->lockForUpdate()->first();
    file_put_contents($marker.'.ready', 'locked');
    $deadline = microtime(true) + 20;
    while (! file_exists($marker.'.done') && microtime(true) < $deadline) usleep(10000);
} finally {
    $db->rollBack();
}
