<?php
declare(strict_types=1);
require __DIR__.'/F08V2InstancePolicy.php';
$uuid='01234567-89ab-cdef-0123-456789abcdef';
$plan=['schema'=>'f08_acceptance_0123456789abcdef','host'=>'127.0.0.1','port'=>3307,
    'metadata_user'=>'f08_v2_metadata','owner_review_reference'=>'reviewed-record','server_uuid'=>$uuid,'real_datadir'=>'C:\\isolated\\mysql'];
$account='`f08_v2_metadata`@`localhost`';
$state=['uuid'=>$uuid,'port'=>3307,'current_user'=>'f08_v2_metadata@localhost','role'=>'NONE','real_datadir'=>'C:\\isolated\\mysql',
    'schemas'=>['mysql','information_schema','performance_schema','sys'],
    'grants'=>['GRANT USAGE ON *.* TO '.$account,'GRANT SHOW DATABASES ON *.* TO '.$account]];
$cases=[
    'isolated mock'=>[$plan,$state,true],
    'production schema present'=>[$plan,array_replace($state,['schemas'=>['mysql','mediway']]),false],
    'old schema present'=>[$plan,array_replace($state,['schemas'=>['mysql','mediway_f08_isolated_20261105_5978f4ba']]),false],
    'new schema already exists'=>[$plan,array_replace($state,['schemas'=>['mysql',$plan['schema']]]),false],
    'production port'=>[array_replace($plan,['port'=>3306]),$state,false],
    'wrong uuid'=>[$plan,array_replace($state,['uuid'=>'ffffffff-ffff-ffff-ffff-ffffffffffff']),false],
    'production datadir'=>[array_replace($plan,['real_datadir'=>'C:\\laragon\\data\\mysql-8']),array_replace($state,['real_datadir'=>'C:\\laragon\\data\\mysql-8']),false],
    'wrong account'=>[$plan,array_replace($state,['current_user'=>'root@localhost']),false],
    'write grant'=>[$plan,array_replace($state,['grants'=>['GRANT ALL PRIVILEGES ON *.* TO '.$account]]),false],
    'no approval reference'=>[array_replace($plan,['owner_review_reference'=>'']),$state,false],
];
foreach($cases as $name=>[$p,$s,$expected]) {
    try { F08V2InstancePolicy::validate($p,$s); $actual=true; } catch(Throwable) { $actual=false; }
    if($actual!==$expected) throw new RuntimeException('FAIL: '.$name);
    echo 'PASS: '.$name."\n";
}
