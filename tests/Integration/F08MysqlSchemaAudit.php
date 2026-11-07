<?php
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
if (DB::getDriverName() !== 'mysql' || DB::getDatabaseName() !== 'mediway_f08_isolated_20261105_5978f4ba') throw new RuntimeException('Unsafe database');
$indexes = DB::select("SELECT INDEX_NAME, COUNT(*) AS cols, SUM(COLUMN_NAME = 'transaction_code') AS codecols FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payment_transactions' AND NON_UNIQUE = 0 GROUP BY INDEX_NAME");
$unique = collect($indexes)->contains(fn ($i) => (int) $i->cols === 1 && (int) $i->codecols === 1);
$orphans = DB::table('payment_transactions as t')->leftJoin('payables as p', 'p.id', '=', 't.payable_id')->whereNull('p.id')->count();
if (! $unique || $orphans !== 0) throw new RuntimeException('Unique index missing or orphan transactions found');
echo "Unique transaction_code index: PASS; orphan transactions: $orphans\n";
