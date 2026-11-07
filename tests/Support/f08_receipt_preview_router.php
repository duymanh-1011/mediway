<?php

// Serve only the three explicitly named synthetic HTML fixtures to localhost.
$files = [
    '/exam' => 'f08-receipt-a4-01M6P2CKETTMCQWXKATPGQ6Q96.html',
    '/service' => 'f08-receipt-a4-01M6P2CKPMVSKFBMGBVZQXY148.html',
    '/prescription' => 'f08-receipt-a4-01M6P2EF801BKJ8JEWTAHVFXWM.html',
];
$name = $files[parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)] ?? null;
if (! $name) { http_response_code(404); exit; }
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store');
readfile(__DIR__.'/../../storage/app/'.$name);
