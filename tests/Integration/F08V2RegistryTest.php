<?php
declare(strict_types=1);
require __DIR__.'/F08V2Registry.php';
$id = str_repeat('a', 32);
$run = ['run_id'=>$id,'status'=>'ACTIVE','source'=>'f08_v2_synthetic'];
$fixture = ['run_id'=>$id,'entity_type'=>'payable','entity_id'=>27,'source'=>'f08_v2_synthetic'];
$cases = [
    'matching registry'=>[$run,$fixture,27,true],
    'wrong run'=>[$run,array_replace($fixture,['run_id'=>str_repeat('b',32)]),27,false],
    'wrong id'=>[$run,$fixture,28,false],
    'wrong type'=>[$run,array_replace($fixture,['entity_type'=>'visit']),27,false],
    'wrong source'=>[$run,array_replace($fixture,['source'=>'external']),27,false],
    'inactive run'=>[array_replace($run,['status'=>'CLOSED']),$fixture,27,false],
    'missing fixture'=>[$run,false,27,false],
    'missing run'=>[false,$fixture,27,false],
];
foreach ($cases as $name=>[$r,$f,$payable,$expected]) {
    try { F08V2Registry::validateRows($id,$payable,$r,$f); $actual=true; }
    catch (Throwable) { $actual=false; }
    if ($actual !== $expected) { fwrite(STDERR,"FAIL: {$name}\n"); exit(1); }
    echo "PASS: {$name}\n";
}
$employee = ['run_id'=>$id,'entity_type'=>'employee','entity_id'=>42,'source'=>'f08_v2_synthetic'];
F08V2Registry::validateRows($id,42,$run,$employee,'employee');
try { F08V2Registry::validateRows($id,42,$run,$employee,'payable'); fwrite(STDERR,"FAIL: entity isolation\n"); exit(1); }
catch (RuntimeException) { echo "PASS: employee/payable isolation\n"; }
F08V2Registry::assertExamPayable(['payment_type'=>'EXAMINATION_FEE', 'service_order_item_id'=>null, 'prescription_id'=>null]);
foreach ([['payment_type'=>'SERVICE_FEE'], ['payment_type'=>'MEDICINE_FEE'], ['payment_type'=>'EXAMINATION_FEE','service_order_item_id'=>123]] as $unsafe) {
    try { F08V2Registry::assertExamPayable($unsafe); fwrite(STDERR,"FAIL: unsupported payable\n"); exit(1); }
    catch (RuntimeException) {}
}
echo "PASS: only standalone examination fees supported\n";
