<?php
declare(strict_types=1);
require __DIR__.'/F08V2Inventory.php';
$expected=['migrations','employee_types','acceptance_runs','acceptance_fixtures','payables','specialties'];
$tables=[];
foreach($expected as $name) $tables[]=['name'=>$name,'type'=>'BASE TABLE','engine'=>'InnoDB','count'=>$name==='employee_types'?5:(in_array($name,['migrations','specialties'],true)?1:0)];
$types=['CASHIER','IMAGING_TECHNICIAN','LAB_TECHNICIAN','NURSE','RECEPTIONIST'];
$specialties=[['specialty_code'=>'CKDK','name'=>'Đa khoa','status'=>'ACTIVE','is_general'=>1,'description'=>null,'image_path'=>null,'pre_visit_instructions'=>null,'notes'=>null]];
$test=static function($name,$rows,$manifest,$catalog,$migrations,$files,$pass) {
    global $specialties;
    try { F08V2Inventory::validate($rows,$manifest,$catalog,$migrations,$files,$specialties); $actual=true; } catch(Throwable) { $actual=false; }
    if($actual!==$pass) throw new RuntimeException('FAIL: '.$name);
    echo 'PASS: '.$name."\n";
};
$test('clean inventory',$tables,$expected,$types,['001'],['001'],true);
$dirty=$tables; $dirty[4]['count']=1; $test('foreign data rejected',$dirty,$expected,$types,['001'],['001'],false);
$wrong=$tables; $wrong[4]['engine']='MyISAM'; $test('non-transactional engine rejected',$wrong,$expected,$types,['001'],['001'],false);
$test('missing table rejected',array_slice($tables,0,4),$expected,$types,['001'],['001'],false);
$test('unexpected table rejected',array_merge($tables,[['name'=>'unexpected','type'=>'BASE TABLE','engine'=>'InnoDB','count'=>0]]),$expected,$types,['001'],['001'],false);
$test('unknown catalog rejected',$tables,$expected,['CASHIER'],['001'],['001'],false);
$test('migration mismatch rejected',$tables,$expected,$types,['001'],['002'],false);
$test('missing schema cannot satisfy post-provisioning',[],$expected,[],[],['001'],false);
$badSpecialty=$specialties; $badSpecialty[0]['notes']='unexpected';
try { F08V2Inventory::validateMigrationSpecialty($badSpecialty); throw new LogicException('Unexpected data accepted'); }
catch (RuntimeException) { echo "PASS: unexpected specialty data rejected\n"; }
