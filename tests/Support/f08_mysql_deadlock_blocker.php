<?php

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$db = Illuminate\Support\Facades\DB::connection();
if ($db->getDriverName() !== 'mysql' || $db->getDatabaseName() !== 'mediway_f08_isolated_20261105_5978f4ba') {
    throw new RuntimeException('Unsafe database');
}
[$script, $appointment, $visit, $marker, $extra] = $argv;
$db->beginTransaction();
try {
    // These synthetic rows make this transaction heavier so MySQL preferentially rolls back the payment worker.
    foreach (explode(',', $extra) as $id) {
        $db->table('appointments')->where('id', (int) $id)->update(['patient_notes' => 'deadlock fixture']);
    }
    $db->table('appointments')->where('id', (int) $appointment)->lockForUpdate()->first();
    file_put_contents($marker.'.ready', 'locked');
    $deadline = microtime(true) + 10;
    while (! file_exists($marker.'.visit') && microtime(true) < $deadline) usleep(10000);
    if (! file_exists($marker.'.visit')) throw new RuntimeException('Visit lock synchronization timeout');
    file_put_contents($marker.'.waiting', 'waiting');
    $db->table('medical_visits')->where('id', (int) $visit)->lockForUpdate()->first();
    $db->rollBack();
    echo 'released';
} catch (Throwable $e) {
    if ($db->transactionLevel() > 0) $db->rollBack();
    if (str_contains($e->getMessage(), '1213')) {
        echo 'blocker_deadlock_victim';
    } else {
        fwrite(STDERR, $e->getMessage());
        exit(1);
    }
}
