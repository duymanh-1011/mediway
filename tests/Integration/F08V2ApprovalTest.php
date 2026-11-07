<?php
declare(strict_types=1);
require __DIR__.'/F08V2Approval.php';
$schema='f08_acceptance_0123456789abcdef'; $run=str_repeat('a',32);
$env=['F08_V2_SCHEMA'=>$schema,'F08_V2_APPROVED_SCHEMA'=>$schema,'F08_V2_RUN_ID'=>$run,'DB_CONNECTION'=>'mysql',
    'DB_HOST'=>'127.0.0.1','DB_PORT'=>'3306','DB_DATABASE'=>$schema,'DB_USERNAME'=>'f08_runner_v2','F08_V2_DB_USERNAME'=>'f08_runner_v2',
    'DB_PASSWORD'=>'synthetic','F08_V2_DB_PASSWORD'=>'synthetic'];
$manifest=['purpose'=>'F08_V2_READONLY_PREFLIGHT','schema'=>$schema,'run_id'=>$run,'fixture_account'=>'f08_runner_v2',
    'worker_account'=>'f08_worker_v2','reviewed_by'=>'owner','tables'=>['migrations','employee_types','acceptance_runs','acceptance_fixtures'],
    'foreign_keys'=>['acceptance_fixtures.run_id>acceptance_runs.run_id'],'migrations'=>['migration_1'=>str_repeat('b',64)]];
$cases=['valid'=>[$manifest,$env,true],
    'schema mismatch'=>[array_replace($manifest,['schema'=>'mediway']),$env,false],
    'wrong run'=>[array_replace($manifest,['run_id'=>str_repeat('c',32)]),$env,false],
    'empty reviewer'=>[array_replace($manifest,['reviewed_by'=>'']),$env,false],
    'empty migration manifest'=>[array_replace($manifest,['migrations'=>[]]),$env,false],
    'invalid foreign key'=>[array_replace($manifest,['foreign_keys'=>['bad;sql']]),$env,false],
    'environment mismatch'=>[$manifest,array_replace($env,['DB_DATABASE'=>'mediway']),false]];
foreach($cases as $name=>[$m,$input,$expected]) {
    $json=json_encode($m,JSON_THROW_ON_ERROR);
    try { F08V2Approval::validate($json,hash('sha256',$json),$input); $actual=true; } catch(Throwable) { $actual=false; }
    if($actual!==$expected) throw new RuntimeException('FAIL: '.$name);
    echo 'PASS: '.$name."\n";
}
$json=json_encode($manifest,JSON_THROW_ON_ERROR);
try { F08V2Approval::validate($json,str_repeat('0',64),$env); throw new LogicException('digest accepted'); }
catch(RuntimeException) { echo "PASS: unapproved digest rejected\n"; }
try { F08V2Approval::verifyMigrationFiles($manifest, dirname(__DIR__,2).'/database/migrations'); throw new LogicException('migration mismatch accepted'); }
catch (RuntimeException) { echo "PASS: migration file hash mismatch rejected\n"; }
