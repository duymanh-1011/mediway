<?php
declare(strict_types=1);
require __DIR__.'/F08V2WorkerPrivileges.php';
$schema='f08_acceptance_0123456789abcdef'; $escaped=str_replace('_','\\_',$schema);
$account='`f08_worker_v2`@`localhost`';
$read='GRANT SELECT ON `'.$escaped.'`.* TO '.$account;
$usage='GRANT USAGE ON *.* TO '.$account;
$cases=[
    'scoped worker'=>[[$usage,$read,'GRANT INSERT, UPDATE ON `'.$escaped.'`.`payables` TO '.$account],true],
    'registry insert'=>[[$usage,$read,'GRANT INSERT ON `'.$escaped.'`.`acceptance_fixtures` TO '.$account],false],
    'run update'=>[[$usage,$read,'GRANT UPDATE ON `'.$escaped.'`.`acceptance_runs` TO '.$account],false],
    'global privileges'=>[['GRANT SELECT ON *.* TO '.$account,$read],false],
    'old schema'=>[[$usage,$read,'GRANT UPDATE ON `mediway`.* TO '.$account],false],
    'grant option'=>[[$usage,$read.' WITH GRANT OPTION'],false],
    'role'=>[[$usage,$read,'GRANT `role`@`%` TO '.$account],false],
    'wildcard schema'=>[[$usage,'GRANT SELECT ON `'.$schema.'`.* TO '.$account],false],
    'unknown grant'=>[[$usage,$read,'GRANT WHATEVER'],false],
];
foreach($cases as $name=>[$grants,$expected]) {
    if(F08V2WorkerPrivileges::accepts($grants,$schema,false)!==$expected) { fwrite(STDERR,"FAIL: {$name}\n"); exit(1); }
    echo "PASS: {$name}\n";
}
