<?php

// Test-only HTTP router: real Laravel kernel, isolated database and per-connection timeout.
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();
$db = Illuminate\Support\Facades\DB::connection();
if ($db->getDriverName() !== 'mysql' || $db->getDatabaseName() !== 'mediway_f08_isolated_20261105_5978f4ba') {
    http_response_code(500);
    exit('Unsafe test database');
}
$db->statement('SET SESSION innodb_lock_wait_timeout = 1');
$request = Illuminate\Http\Request::capture();
if ($request->isMethod('POST') && preg_match('~^nhan-vien/thanh-toan/\d+/ghi-nhan$~', $request->path())) {
    Illuminate\Support\Facades\Event::listen(Illuminate\Database\Events\TransactionBeginning::class, function () {
        file_put_contents(getenv('F08_HTTP_ATTEMPTS_PATH'), "begin\n", FILE_APPEND);
    });
}
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
