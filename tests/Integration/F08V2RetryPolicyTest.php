<?php
declare(strict_types=1);
require __DIR__.'/F08V2RetryPolicy.php';
$cases=[
    ['deadlock', 'before_commit',1213,'40001',1,3,true,true],
    ['lock timeout', 'before_commit',1205,'HY000',2,3,true,true],
    ['exhausted', 'before_commit',1213,'40001',3,3,true,false],
    ['unconfirmed rollback', 'before_commit',1213,'40001',1,3,false,false],
    ['unknown commit', 'during_commit',1213,'40001',1,3,true,false],
    ['validation', 'before_commit',0,'',1,3,true,false],
    ['other SQL', 'before_commit',1062,'23000',1,3,true,false],
    ['too many retries', 'before_commit',1213,'40001',1,4,true,false],
];
foreach($cases as [$name,$phase,$code,$state,$attempt,$max,$rolledBack,$expected]) {
    if(F08V2RetryPolicy::mayRetry($phase,$code,$state,$attempt,$max,$rolledBack)!==$expected) throw new RuntimeException('FAIL: '.$name);
    echo 'PASS: '.$name."\n";
}
