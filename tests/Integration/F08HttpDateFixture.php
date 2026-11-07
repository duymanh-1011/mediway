<?php
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
if (DB::getDriverName() !== 'mysql' || DB::getDatabaseName() !== 'mediway_f08_isolated_20261105_5978f4ba') throw new RuntimeException('Unsafe database');
$id = (int) ($argv[1] ?? 0);
$date = $argv[2] ?? '';
if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) throw new RuntimeException('Invalid date');
$record = DB::table('payables as p')->join('medical_visits as v', 'v.id', '=', 'p.medical_visit_id')->join('appointments as a', 'a.id', '=', 'v.appointment_id')->where('p.id', $id)->select('p.payable_code', 'a.id as appointment_id')->first();
if (! $record || ! preg_match('/^H[0-9A-Z]{10}$/', $record->payable_code)) throw new RuntimeException('Not an isolated HTTP fixture');
DB::table('appointments')->where('id', $record->appointment_id)->update(['appointment_date' => $date]);
echo "OK\n";
