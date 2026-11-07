<?php
declare(strict_types=1);
require __DIR__.'/F08V2WorkerGate.php';
$schema = 'f08_acceptance_0123456789abcdef';
$run = '0123456789abcdef0123456789abcdef';
$env = ['F08_V2_SCHEMA'=>$schema, 'F08_V2_APPROVED_SCHEMA'=>$schema, 'F08_V2_RUN_ID'=>$run,
    'DB_CONNECTION'=>'mysql', 'DB_HOST'=>'127.0.0.1', 'DB_PORT'=>'3306', 'DB_DATABASE'=>$schema,
    'DB_USERNAME'=>'f08_worker_v2', 'F08_V2_DB_USERNAME'=>'f08_worker_v2', 'DB_PASSWORD'=>'synthetic', 'F08_V2_DB_PASSWORD'=>'synthetic'];
$grantSchema = str_replace('_', '\\_', $schema);
$state = ['database'=>$schema, 'user'=>'f08_worker_v2@localhost', 'role'=>'NONE', 'partial_revokes'=>0,
    'grants'=>['GRANT USAGE ON *.* TO `f08_worker_v2`@`localhost`', 'GRANT SELECT ON `'.$grantSchema.'`.* TO `f08_worker_v2`@`localhost`']];
$cases = [
    'valid mock database ownership'=>[$env,$state,$run,9,true],
    'wrong run owner'=>[$env,$state,str_repeat('f',32),9,false],
    'unknown payable'=>[$env,$state,null,987,false],
    'invalid payable'=>[$env,$state,$run,0,false],
    'environment database conflict'=>[array_replace($env,['DB_DATABASE'=>'mediway']),$state,$run,9,false],
    'old database conflict'=>[array_replace($env,['DB_DATABASE'=>'mediway_f08_isolated_20261105_5978f4ba']),$state,$run,9,false],
    'password conflict'=>[array_replace($env,['DB_PASSWORD'=>'different']),$state,$run,9,false],
    'URL override'=>[array_replace($env,['DB_URL'=>'mysql://other']),$state,$run,9,false],
    'missing approval'=>[array_diff_key($env,['F08_V2_APPROVED_SCHEMA'=>1]),$state,$run,9,false],
    'wrong user'=>[$env,array_replace($state,['user'=>'root@localhost']),$run,9,false],
    'active role'=>[$env,array_replace($state,['role'=>'admin']),$run,9,false],
    'global grants'=>[$env,array_replace($state,['grants'=>['GRANT SELECT ON *.* TO `f08_runner_v2`@`localhost`']]),$run,9,false],
    'worker registry write'=>[$env,array_replace($state,['grants'=>array_merge($state['grants'],['GRANT UPDATE ON `'.$grantSchema.'`.`acceptance_fixtures` TO `f08_worker_v2`@`localhost`'])]),$run,9,false],
    'different database'=>[$env,array_replace($state,['database'=>'mediway']),$run,9,false],
];
foreach ($cases as $name=>[$input,$db,$owner,$id,$expected]) {
    try {
        F08V2WorkerGate::authorize($input,$id,static fn($key,$ignored)=>$key==='connection' ? $db : $owner);
        $actual=true;
    } catch (Throwable) { $actual=false; }
    if ($actual !== $expected) { fwrite(STDERR,"FAIL: {$name}\n"); exit(1); }
    echo "PASS: {$name}\n";
}
