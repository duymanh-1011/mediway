<?php

// Test-only HTTP router: hold Admin before its first visit lock to exercise CASHIER winning.
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();
$db = Illuminate\Support\Facades\DB::connection();
if ($db->getDriverName() !== 'mysql' || $db->getDatabaseName() !== 'mediway_f08_isolated_20261105_5978f4ba') {
    http_response_code(500);
    exit('Unsafe test database');
}
$request = Illuminate\Http\Request::capture();
if ($request->isMethod('POST') && preg_match('~^admin/payables/\d+/payment-transactions$~', $request->path())) {
    $delayed = false;
    $db->beforeExecuting(function ($sql) use (&$delayed) {
        if (! $delayed && str_contains(strtolower($sql), 'medical_visits') && str_contains(strtolower($sql), 'for update')) {
            $delayed = true;
            usleep(800000);
        }
    });
}
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
