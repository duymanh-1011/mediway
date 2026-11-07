<?php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (str_starts_with($path, '/build/') && is_file(__DIR__.'/../../public'.$path)) return false;
require __DIR__.'/../../public/index.php';
