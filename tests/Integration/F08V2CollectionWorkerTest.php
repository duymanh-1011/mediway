<?php
declare(strict_types=1);
require __DIR__.'/F08V2CollectionWorker.php';
// No database is loaded or contacted; the write entrypoint must remain disabled.
try {
    F08V2CollectionWorker::collect([], 1, 1);
    fwrite(STDERR, "FAIL: disabled worker accepted a request\n");
    exit(1);
} catch (LogicException $e) {
    if (!str_starts_with($e->getMessage(), 'BLOCKED:')) throw $e;
    echo "PASS: worker refuses all writes while V2 is incomplete\n";
}
