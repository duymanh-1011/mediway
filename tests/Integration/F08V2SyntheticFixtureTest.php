<?php
declare(strict_types=1);
require __DIR__.'/F08V2SyntheticFixture.php';
final class FakeF08Store implements F08V2FixtureStore
{
    public array $rows = []; public array $registry = []; public bool $active = false; public int $next = 701; public int $failAt = 0; public bool $failRegistration = false;
    public bool $breakFk = false; public bool $duplicateUser = false; public bool $zeroId = false;
    public function transaction(callable $work): mixed {
        $this->active=true;
        try { $result=$work(); $this->active=false; return $result; }
        catch (Throwable $e) { $this->rows=[]; $this->registry=[]; $this->active=false; throw $e; }
    }
    public function insert(string $table, array $values): int {
        if (!$this->active) throw new RuntimeException('outside transaction');
        if ($this->failAt && count($this->rows) === $this->failAt) throw new RuntimeException('injected failure');
        if ($this->breakFk && $table==='payables') $values['medical_visit_id']=999999;
        if ($this->duplicateUser && $table==='users') {
            foreach ($this->rows as $row) if ($row['table']==='users') { $values['business_code']=$row['values']['business_code']; break; }
        }
        $fks=['users'=>['role_id'=>'roles'],'employees'=>['user_id'=>'users'],'employee_details'=>['employee_id'=>'employees'],
            'doctors'=>['user_id'=>'users','specialty_id'=>'specialties'],'examination_rooms'=>['specialty_id'=>'specialties'],
            'doctor_schedules'=>['doctor_id'=>'doctors','specialty_id'=>'specialties','examination_room_id'=>'examination_rooms'],
            'appointment_time_slots'=>['doctor_schedule_id'=>'doctor_schedules'],
            'appointments'=>['patient_id'=>'patients','doctor_id'=>'doctors','specialty_id'=>'specialties','examination_room_id'=>'examination_rooms','appointment_time_slot_id'=>'appointment_time_slots','appointment_status_id'=>'appointment_statuses'],
            'medical_visits'=>['appointment_id'=>'appointments','patient_id'=>'patients','doctor_id'=>'doctors','specialty_id'=>'specialties','examination_room_id'=>'examination_rooms','visit_status_id'=>'visit_statuses'],
            'payables'=>['medical_visit_id'=>'medical_visits','payment_status_id'=>'payment_statuses'],
            'payment_transactions'=>['payable_id'=>'payables','collected_by_employee_id'=>'employees'],'audit_logs'=>['user_id'=>'users']];
        foreach ($fks[$table] ?? [] as $column=>$target) {
            $ref=$values[$column] ?? null;
            if (!isset($this->rows[$ref]) || $this->rows[$ref]['table']!==$target) throw new RuntimeException('mock foreign key violation');
        }
        $unique=['users'=>['business_code','phone'],'roles'=>['name'],'patients'=>['patient_code'],'doctors'=>['doctor_code'],
            'appointments'=>['appointment_code','checkin_token'],'medical_visits'=>['visit_code','appointment_id'],'payables'=>['payable_code'],'payment_transactions'=>['transaction_code']];
        foreach ($unique[$table] ?? [] as $column) foreach ($this->rows as $row) {
            if ($row['table']===$table && isset($values[$column]) && ($row['values'][$column] ?? null)===$values[$column]) throw new RuntimeException('mock unique violation');
        }
        if ($this->zeroId && $table==='appointments') return 0;
        $id=$this->next++; $this->rows[$id]=['table'=>$table,'values'=>$values]; return $id;
    }
    public function register(string $run,string $type,int $id,string $time): void {
        if (!$this->active || !isset($this->rows[$id])) throw new RuntimeException('registration outside transaction');
        if ($this->failRegistration) throw new RuntimeException('injected registry failure');
        $this->registry[$id]=[$run,$type,$time];
    }
}
$schema='f08_acceptance_0123456789abcdef'; $run='0123456789abcdef0123456789abcdef';
$env=['F08_V2_SCHEMA'=>$schema,'F08_V2_APPROVED_SCHEMA'=>$schema,'F08_V2_RUN_ID'=>$run,
    'DB_CONNECTION'=>'mysql','DB_HOST'=>'127.0.0.1','DB_PORT'=>'3306','DB_DATABASE'=>$schema,
    'DB_USERNAME'=>'f08_runner_v2','F08_V2_DB_USERNAME'=>'f08_runner_v2','DB_PASSWORD'=>'synthetic','F08_V2_DB_PASSWORD'=>'synthetic'];
$store=new FakeF08Store(); $result=F08V2SyntheticFixture::create($env,$store,'2026-11-06');
foreach (['user_id','employee_id','patient_id','appointment_id','medical_visit_id','payable_id'] as $key) {
    if (!isset($result[$key],$store->registry[$result[$key]]) || $result[$key] < 701) throw new RuntimeException('Missing metadata or registry');
}
if ($result['run_id']!==$run || count($result['cashiers'])!==2 || count($result['cases'])!==5) throw new RuntimeException('Missing cases');
foreach ($store->registry as $entry) if ($entry[0]!==$run) throw new RuntimeException('Foreign run');
if (count($store->registry)!==count($store->rows)-1) throw new RuntimeException('Unregistered fixture');
$payable=$store->rows[$result['payable_id']]['values']; $visit=$store->rows[$result['medical_visit_id']]['values'];
if ($payable['medical_visit_id']!==$result['medical_visit_id'] || $visit['patient_id']!==$result['patient_id'] || $visit['appointment_id']!==$result['appointment_id']) throw new RuntimeException('Foreign key mismatch');
$occupied=[];
foreach (['unpaid_today'=>'UNPAID','paid_today'=>'PAID','cancelled_today'=>'CANCELLED','unpaid_future'=>'UNPAID','unpaid_past'=>'UNPAID'] as $case=>$status) {
    $item=$store->rows[$result['cases'][$case]['payable_id']]['values'];
    if (($store->rows[$item['payment_status_id']]['values']['name'] ?? null)!==$status) throw new RuntimeException('Wrong case status');
    $expectedDate=match($case) { 'unpaid_future'=>'2026-11-07', 'unpaid_past'=>'2026-11-05', default=>'2026-11-06' };
    if ($store->rows[$result['cases'][$case]['appointment_id']]['values']['appointment_date']!==$expectedDate) throw new RuntimeException('Wrong case date');
    if (($result['cases'][$case]['transaction_id'] !== null) !== ($status==='PAID')) throw new RuntimeException('Unexpected transaction metadata');
    $appointment=$store->rows[$result['cases'][$case]['appointment_id']]['values'];
    $slot=$store->rows[$appointment['appointment_time_slot_id']]['values'];
    $key=$appointment['doctor_id'].'|'.$appointment['appointment_date'].'|'.$slot['start_time'];
    if (isset($occupied[$key])) throw new RuntimeException('Duplicate synthetic doctor slot');
    $occupied[$key]=true;
}
$paidCase=$result['cases']['paid_today'];
$paid=$store->rows[$paidCase['payable_id']]['values'];
$transaction=$store->rows[$paidCase['transaction_id']]['values'];
$audit=$store->rows[$paidCase['audit_id']]['values'];
F08V2SyntheticFixture::validatePaid($paid,$transaction,$audit,$paidCase['payable_id'],$paidCase['transaction_id'],$result['employee_id'],$result['user_id']);
if (isset($audit['updated_at']) || !preg_match('/^0[0-9]{9}$/', $store->rows[$result['user_id']]['values']['phone'])) throw new RuntimeException('Invalid audit schema or cashier phone');
foreach (['amount','payment_method','transaction_result','collected_by_employee_id'] as $column) {
    $invalid=$transaction; $invalid[$column]= $column==='collected_by_employee_id' ? -1 : 'INVALID';
    try { F08V2SyntheticFixture::validatePaid($paid,$invalid,$audit,$paidCase['payable_id'],$paidCase['transaction_id'],$result['employee_id'],$result['user_id']); throw new LogicException('Invalid paid accepted'); }
    catch (RuntimeException $e) { if ($e->getMessage()!=='Inconsistent synthetic PAID scenario') throw $e; }
}
$invalidAudit=$audit; $invalidAudit['new_values']='{}';
try { F08V2SyntheticFixture::validatePaid($paid,$transaction,$invalidAudit,$paidCase['payable_id'],$paidCase['transaction_id'],$result['employee_id'],$result['user_id']); throw new LogicException('Invalid audit accepted'); }
catch (RuntimeException $e) { if ($e->getMessage()!=='Inconsistent synthetic PAID scenario') throw $e; }
$bad=new FakeF08Store(); $bad->failAt=9;
try { F08V2SyntheticFixture::create($env,$bad,'2026-11-06'); throw new RuntimeException('Missing injected failure'); }
catch (RuntimeException $e) { if ($e->getMessage()!=='injected failure' || $bad->rows || $bad->registry) throw $e; }
$badRegistry=new FakeF08Store(); $badRegistry->failRegistration=true;
try { F08V2SyntheticFixture::create($env,$badRegistry,'2026-11-06'); throw new RuntimeException('Missing injected registry failure'); }
catch (RuntimeException $e) { if ($e->getMessage()!=='injected registry failure' || $badRegistry->rows || $badRegistry->registry) throw $e; }
foreach (['breakFk'=>'mock foreign key violation','duplicateUser'=>'mock unique violation','zeroId'=>'Fixture insert did not return an ID'] as $flag=>$message) {
    $invalidStore=new FakeF08Store(); $invalidStore->$flag=true;
    try { F08V2SyntheticFixture::create($env,$invalidStore,'2026-11-06'); throw new LogicException('Invalid fixture accepted'); }
    catch (RuntimeException $e) { if ($e->getMessage()!==$message || $invalidStore->rows || $invalidStore->registry) throw $e; }
}
$foreignEnv=$env; $foreignEnv['F08_V2_RUN_ID']='wrong-run';
try { F08V2SyntheticFixture::create($foreignEnv,new FakeF08Store(),'2026-11-06'); throw new LogicException('Foreign run accepted'); }
catch (InvalidArgumentException) {}
$source=file_get_contents(__DIR__.'/F08V2SyntheticFixture.php');
if (str_contains($source,'latest(') || str_contains($source,'where(') || str_contains($source,'mediway_f08_isolated')) throw new RuntimeException('Unexpected legacy access');
echo "PASS: synthetic metadata, dynamic foreign keys, run registry and mock rollback\n";
